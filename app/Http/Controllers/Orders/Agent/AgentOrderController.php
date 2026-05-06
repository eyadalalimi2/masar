<?php

namespace App\Http\Controllers\Orders\Agent;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Product;
use App\Models\Customer\Customer;
use App\Models\Distribution\Branch;
use App\Modules\Delivery\Services\DeliveryDomainService;
use App\Modules\Orders\Services\OrdersDomainService;
use App\Models\Notifications\WebAlert;
use App\Models\Orders\Order;
use App\Services\Lookup\LookupService;
use App\Http\Requests\Orders\OrderRequest;
use App\Services\Notifications\WebAlertService;
use App\Services\Orders\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AgentOrderController extends Controller
{
    public function __construct(
        private readonly OrdersDomainService $ordersDomainService,
        private readonly DeliveryDomainService $deliveryDomainService,
        private readonly OrderService $orderService,
        private readonly WebAlertService $webAlertService,
    ) {}

    public function index(Request $request): View
    {
        $supplierId = Auth::guard('agent')->user()->supplier->id;
        $agentId = (int) (Auth::guard('agent')->id() ?? 0);
        $status = (string) $request->get('status', '');

        $delayedOrdersCount = $this->delayedOrdersQuery((int) $supplierId)->count();
        $delayAlertsTodayCount = WebAlert::query()
            ->where('recipient_type', 'agent')
            ->where('recipient_id', $agentId)
            ->whereDate('created_at', now()->toDateString())
            ->where('title', 'تنبيه تأخير طلبات الوكيل')
            ->count();

        $orders = $this->ordersDomainService->ordersQuery()
            ->with(['branch', 'distributor', 'buyer', 'items.product', 'items.productUnit.unit', 'items.productVariant.variantValue.type', 'latestPayment.paymentMethod', 'latestPayment.account'])
            ->where('supplier_id', $supplierId)
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('agent.orders.index', compact('orders', 'delayedOrdersCount', 'delayAlertsTodayCount'));
    }

    public function create(): View
    {
        $supplierId = Auth::guard('agent')->user()->supplier->id;

        $products = Product::with(['productUnits.unit', 'productVariants.variantValue.type', 'productVariants.variantUnits'])
            ->where('supplier_id', $supplierId)
            ->where('status', 'active')
            ->get();
        $branches = Branch::where('supplier_id', $supplierId)->where('status', 'active')->get();
        $distributors = $this->deliveryDomainService->distributorsQuery()
            ->where('supplier_id', $supplierId)
            ->where('status', 'active')
            ->get();
        $customers = Customer::query()->where('status', 'active')->orderBy('name')->get();

        return view('agent.orders.create', compact('products', 'branches', 'distributors', 'customers'));
    }

    public function store(OrderRequest $request): RedirectResponse
    {
        $supplierId = Auth::guard('agent')->user()->supplier->id;
        $payload = $request->validated();
        $payload['supplier_id'] = $supplierId;
        $payload['created_by_agent_id'] = (int) Auth::guard('agent')->id();

        if (! empty($payload['branch_id'])) {
            Branch::where('supplier_id', $supplierId)->findOrFail($payload['branch_id']);
        }

        if (! empty($payload['distributor_id'])) {
            $this->deliveryDomainService->distributorsQuery()->where('supplier_id', $supplierId)->findOrFail($payload['distributor_id']);
        }

        $this->orderService->createOrder($payload);

        return redirect()->route('agent.orders.index')->with('success', 'تم إنشاء الطلب بنجاح.');
    }

    public function show(Order $order): View
    {
        $supplierId = Auth::guard('agent')->user()->supplier->id;
        abort_unless($order->supplier_id === $supplierId, 404);

        $order->load(['branch', 'distributor', 'buyer', 'items.product', 'items.productUnit.unit', 'items.productVariant.variantValue.type', 'creator', 'latestPayment.paymentMethod', 'latestPayment.account']);
        $distributors = $this->deliveryDomainService->distributorsQuery()
            ->where('supplier_id', $supplierId)
            ->where('status', 'active')
            ->get();

        return view('agent.orders.show', compact('order', 'distributors'));
    }

    public function assignDistributor(Request $request, Order $order): RedirectResponse
    {
        $supplierId = Auth::guard('agent')->user()->supplier->id;
        abort_unless($order->supplier_id === $supplierId, 404);

        $data = $request->validate([
            'distributor_id' => ['nullable', 'exists:distributors,id'],
        ], [
            'distributor_id.exists' => 'المندوب غير موجود.',
        ]);

        if (! empty($data['distributor_id'])) {
            $this->deliveryDomainService->distributorsQuery()->where('supplier_id', $supplierId)->findOrFail($data['distributor_id']);
        }

        $this->orderService->assignDistributor($order, $data['distributor_id'] ?? null);

        return back()->with('success', 'تم تحديث المندوب بنجاح.');
    }

    public function smartDispatch(Order $order): RedirectResponse
    {
        $supplierId = (int) Auth::guard('agent')->user()->supplier->id;
        abort_unless((int) $order->supplier_id === $supplierId, 404);

        $distributor = $this->deliveryDomainService->distributorsQuery()
            ->where('supplier_id', $supplierId)
            ->where('status', 'active')
            ->withCount([
                'orders as active_orders_count' => function ($query) {
                    $query->whereIn('status', [
                        Order::STATUS_ASSIGNED,
                        Order::STATUS_OUT_FOR_DELIVERY,
                    ]);
                },
            ])
            ->orderBy('active_orders_count')
            ->orderBy('id')
            ->first();

        if (! $distributor) {
            return back()->withErrors(['distributor_id' => 'لا يوجد مندوب نشط متاح للتوزيع الذكي.']);
        }

        $this->orderService->assignDistributor($order, (int) $distributor->id);

        return back()->with('success', 'تم التوزيع الذكي للطلب على المندوب: ' . $distributor->name);
    }

    public function generateDelayAlerts(): RedirectResponse
    {
        $supplierId = (int) Auth::guard('agent')->user()->supplier->id;
        $agentId = (int) (Auth::guard('agent')->id() ?? 0);

        if ($agentId <= 0) {
            abort(403);
        }

        $created = 0;
        $today = now()->toDateString();

        $delayedOrders = $this->delayedOrdersQuery($supplierId)->get();
        foreach ($delayedOrders as $order) {
            $body = 'الطلب #' . $order->id . ' متأخر ويحتاج متابعة الوكيل.';

            $exists = WebAlert::query()
                ->where('recipient_type', 'agent')
                ->where('recipient_id', $agentId)
                ->whereDate('created_at', $today)
                ->where('title', 'تنبيه تأخير طلبات الوكيل')
                ->where('body', $body)
                ->exists();

            if ($exists) {
                continue;
            }

            $this->webAlertService->create(
                'agent',
                $agentId,
                'تنبيه تأخير طلبات الوكيل',
                $body,
                [
                    'type' => 'agent_order_delay_alert',
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'delay_hours' => (int) now()->diffInHours($order->updated_at),
                ]
            );

            $created++;
        }

        return back()->with('success', 'تم توليد ' . $created . ' تنبيه تأخير للطلبات المتأخرة.');
    }

    public function changeStatus(Request $request, Order $order): RedirectResponse
    {
        $supplierId = Auth::guard('agent')->user()->supplier->id;
        abort_unless($order->supplier_id === $supplierId, 404);
        $lookupService = app(LookupService::class);

        $data = $request->validate([
            'status' => ['required', \Illuminate\Validation\Rule::in($lookupService->orderStatuses())],
        ]);

        $this->orderService->changeStatus($order, $data['status']);

        return back()->with('success', 'تم تحديث حالة الطلب.');
    }

    private function delayedOrdersQuery(int $supplierId)
    {
        $delayHours = max((int) env('AGENT_ORDER_DELAY_HOURS', 8), 1);

        return $this->ordersDomainService->ordersQuery()
            ->where('supplier_id', $supplierId)
            ->whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_APPROVED,
                Order::STATUS_ASSIGNED,
                Order::STATUS_OUT_FOR_DELIVERY,
            ])
            ->where('updated_at', '<=', now()->subHours($delayHours));
    }
}
