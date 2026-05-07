<?php

namespace App\Http\Controllers\Orders\Branch;

use App\Http\Controllers\Controller;
use App\Models\Distribution\Branch;
use App\Modules\Delivery\Services\DeliveryDomainService;
use App\Modules\Delivery\Services\SmartDispatchService;
use App\Modules\Orders\Services\OrdersDomainService;
use App\Models\Notifications\WebAlert;
use App\Models\Orders\Order;
use App\Services\Lookup\LookupService;
use App\Services\Notifications\WebAlertService;
use App\Services\Orders\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchOrderController extends Controller
{
    public function __construct(
        private readonly OrdersDomainService $ordersDomainService,
        private readonly DeliveryDomainService $deliveryDomainService,
        private readonly SmartDispatchService $smartDispatchService,
        private readonly OrderService $orderService,
        private readonly WebAlertService $webAlertService,
    ) {}

    public function index(Request $request): View
    {
        $branch = $this->currentBranch();
        $status = (string) $request->query('status', '');
        $delayedOrdersCount = $this->delayedOrdersQuery($branch)->count();

        $orders = $this->ordersDomainService->ordersQuery()
            ->with(['supplier', 'distributor', 'buyer', 'items.product', 'latestPayment.paymentMethod', 'latestPayment.account'])
            ->where('branch_id', $branch->id)
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('branch.orders.index', compact('orders', 'branch', 'delayedOrdersCount'));
    }

    public function show(Order $order): View
    {
        $branch = $this->currentBranch();
        abort_unless($order->branch_id === $branch->id, 404);

        $order->load(['supplier', 'distributor', 'buyer', 'items.product', 'creator', 'latestPayment.paymentMethod', 'latestPayment.account']);
        $distributors = $this->deliveryDomainService->distributorsQuery()
            ->where('branch_id', $branch->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return view('branch.orders.show', compact('order', 'branch', 'distributors'));
    }

    public function changeStatus(Request $request, Order $order): RedirectResponse
    {
        $branch = $this->currentBranch();
        abort_unless($order->branch_id === $branch->id, 404);
        $lookupService = app(LookupService::class);

        $data = $request->validate([
            'status' => ['required', Rule::in($this->branchManageableStatuses($lookupService))],
        ]);

        try {
            $this->orderService->changeStatus($order, $data['status']);
        } catch (\Throwable $e) {
            return back()->withErrors(['order_status' => $e->getMessage()]);
        }

        return back()->with('success', 'تم تحديث حالة الطلب بنجاح.');
    }

    public function reject(Request $request, Order $order): RedirectResponse
    {
        $branch = $this->currentBranch();
        abort_unless($order->branch_id === $branch->id, 404);

        $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->orderService->changeStatus($order, 'cancelled');

        return back()->with('success', 'تم رفض الطلب وتحويل حالته إلى ملغي.');
    }

    public function assignDistributor(Request $request, Order $order): RedirectResponse
    {
        $branch = $this->currentBranch();
        abort_unless($order->branch_id === $branch->id, 404);

        $data = $request->validate([
            'distributor_id' => ['nullable', 'integer', 'exists:distributors,id'],
        ]);

        $distributorId = isset($data['distributor_id']) ? (int) $data['distributor_id'] : null;
        if ($distributorId !== null && $distributorId > 0) {
            $isValidDistributor = $this->deliveryDomainService->distributorsQuery()
                ->where('branch_id', $branch->id)
                ->whereKey($distributorId)
                ->exists();

            if (! $isValidDistributor) {
                return back()->withErrors(['distributor_id' => 'المندوب المحدد لا يتبع هذا الفرع.']);
            }
        }

        $this->orderService->assignDistributor($order, $distributorId);

        return back()->with('success', 'تم تعيين المندوب بنجاح.');
    }

    public function smartDispatch(Order $order): RedirectResponse
    {
        $branch = $this->currentBranch();
        abort_unless($order->branch_id === $branch->id, 404);

        $selection = $this->smartDispatchService->autoAssignForBranch($order, (int) $branch->id);
        if (! $selection) {
            return back()->withErrors(['distributor_id' => 'لا يوجد مندوب نشط متاح للتوزيع الذكي.']);
        }

        return back()->with('success', 'تم التوزيع الذكي للطلب على المندوب: ' . $selection->distributorName);
    }

    public function generateDelayAlerts(): RedirectResponse
    {
        $branch = $this->currentBranch();
        $accountId = (int) (Auth::guard('branch')->id() ?? 0);
        if ($accountId <= 0) {
            abort(403);
        }

        $created = 0;
        $today = now()->toDateString();

        $delayedOrders = $this->delayedOrdersQuery($branch)->get();

        foreach ($delayedOrders as $order) {
            $alreadyExistsToday = WebAlert::query()
                ->where('recipient_type', 'branch_account')
                ->where('recipient_id', $accountId)
                ->whereDate('created_at', $today)
                ->where('title', 'تنبيه تأخير الطلبات')
                ->where('body', 'الطلب #' . $order->id . ' متأخر ويحتاج متابعة فورية.')
                ->exists();

            if ($alreadyExistsToday) {
                continue;
            }

            $this->webAlertService->create(
                'branch_account',
                $accountId,
                'تنبيه تأخير الطلبات',
                'الطلب #' . $order->id . ' متأخر ويحتاج متابعة فورية.',
                [
                    'type' => 'branch_order_delay_alert',
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'delay_hours' => (int) now()->diffInHours($order->updated_at),
                ]
            );

            $created++;
        }

        return back()->with('success', 'تم توليد ' . $created . ' تنبيه تأخير للطلبات المتأخرة.');
    }

    private function delayedOrdersQuery(Branch $branch)
    {
        $delayHours = (int) env('BRANCH_DELAY_ALERT_HOURS', 6);

        return $this->ordersDomainService->ordersQuery()
            ->where('branch_id', $branch->id)
            ->whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_APPROVED,
                Order::STATUS_ASSIGNED,
                Order::STATUS_OUT_FOR_DELIVERY,
            ])
            ->where('updated_at', '<=', now()->subHours(max($delayHours, 1)));
    }

    private function branchManageableStatuses(LookupService $lookupService): array
    {
        $allowed = [
            Order::STATUS_APPROVED,
            Order::STATUS_ASSIGNED,
            Order::STATUS_OUT_FOR_DELIVERY,
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED,
        ];

        return array_values(array_intersect($lookupService->orderStatuses(), $allowed));
    }

    private function currentBranch(): Branch
    {
        return $this->deliveryDomainService->branchesQuery()
            ->where('phone', Auth::user()->phone)
            ->where('status', 'active')
            ->firstOrFail();
    }
}
