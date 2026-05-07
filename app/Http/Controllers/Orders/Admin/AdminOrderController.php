<?php

namespace App\Http\Controllers\Orders\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notifications\WebAlert;
use App\Modules\Delivery\Services\SmartDispatchService;
use App\Modules\Orders\Services\OrdersDomainService;
use App\Models\Orders\Order;
use App\Services\Lookup\LookupService;
use App\Services\Notifications\WebAlertService;
use App\Services\Orders\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminOrderController extends Controller
{
    public function __construct(
        private readonly OrdersDomainService $ordersDomainService,
        private readonly OrderService $orderService,
        private readonly WebAlertService $webAlertService,
        private readonly SmartDispatchService $smartDispatchService,
    ) {}

    public function index(Request $request): View
    {
        $adminId = (int) (Auth::guard('admin')->id() ?? 0);
        $status = (string) $request->get('status', '');
        $delayedOnly = (bool) $request->boolean('delayed_only');
        $trashed = (string) $request->get('trashed', '');

        $delayedOrdersCount = $this->delayedOrdersQuery()->count();
        $delayAlertsTodayCount = WebAlert::query()
            ->where('recipient_type', 'admin')
            ->where('recipient_id', $adminId)
            ->whereDate('created_at', now()->toDateString())
            ->where('title', 'تنبيه تأخير طلبات النظام')
            ->count();

        $orders = $this->ordersDomainService->ordersQuery()
            ->with(['supplier', 'branch', 'distributor', 'buyer', 'items.product'])
            ->when($trashed === 'all', function ($query) {
                $query->withTrashed();
            })
            ->when($trashed === 'only', function ($query) {
                $query->onlyTrashed();
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($delayedOnly, function ($query) {
                $query->where('updated_at', '<=', now()->subHours(max((int) env('ADMIN_ORDER_DELAY_HOURS', 10), 1)))
                    ->whereIn('status', [
                        Order::STATUS_PENDING,
                        Order::STATUS_APPROVED,
                        Order::STATUS_ASSIGNED,
                        Order::STATUS_OUT_FOR_DELIVERY,
                    ]);
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.orders.index', compact('orders', 'delayedOrdersCount', 'delayAlertsTodayCount', 'delayedOnly'));
    }

    public function show(Order $order): View
    {
        $order->load(['supplier', 'branch', 'distributor', 'buyer', 'items.product', 'creator']);

        return view('admin.orders.show', compact('order'));
    }

    public function smartDispatch(Order $order): RedirectResponse
    {
        if (! $order->supplier_id) {
            return back()->withErrors(['order' => 'لا يمكن التوزيع الذكي لطلب بدون وكيل محدد.']);
        }

        $selection = $this->smartDispatchService->autoAssignForAdmin($order);
        if (! $selection) {
            return back()->withErrors(['distributor_id' => 'لا يوجد مندوب نشط متاح للتوزيع الذكي لهذا الطلب.']);
        }

        return back()->with('success', 'تم التوزيع الذكي على المندوب: ' . $selection->distributorName);
    }

    public function generateDelayAlerts(): RedirectResponse
    {
        $adminId = (int) (Auth::guard('admin')->id() ?? 0);
        if ($adminId <= 0) {
            abort(403);
        }

        $today = now()->toDateString();
        $created = 0;

        foreach ($this->delayedOrdersQuery()->get() as $order) {
            $body = 'الطلب #' . $order->id . ' متأخر على مستوى النظام ويحتاج متابعة فورية.';

            $exists = WebAlert::query()
                ->where('recipient_type', 'admin')
                ->where('recipient_id', $adminId)
                ->whereDate('created_at', $today)
                ->where('title', 'تنبيه تأخير طلبات النظام')
                ->where('body', $body)
                ->exists();

            if ($exists) {
                continue;
            }

            $this->webAlertService->create(
                'admin',
                $adminId,
                'تنبيه تأخير طلبات النظام',
                $body,
                [
                    'type' => 'admin_order_delay_alert',
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'delay_hours' => (int) now()->diffInHours($order->updated_at),
                ]
            );

            $created++;
        }

        return back()->with('success', 'تم توليد ' . $created . ' تنبيه تأخير لطلبات النظام.');
    }

    public function changeStatus(Request $request, Order $order): RedirectResponse
    {
        $lookupService = app(LookupService::class);

        $data = $request->validate([
            'status' => ['required', Rule::in($lookupService->orderStatuses())],
        ]);

        $this->orderService->changeStatus($order, $data['status']);

        return back()->with('success', 'تم تحديث حالة الطلب.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $order->delete();

        return redirect()->route('admin.orders.index')->with('success', 'تم حذف الطلب بنجاح.');
    }

    public function restore(int $order): RedirectResponse
    {
        $model = Order::withTrashed()->findOrFail($order);
        if ($model->trashed()) {
            $model->restore();
        }

        return redirect()->route('admin.orders.index')->with('success', 'تم استرجاع الطلب بنجاح.');
    }

    public function forceDelete(int $order): RedirectResponse
    {
        $model = Order::withTrashed()->findOrFail($order);
        $model->forceDelete();

        return redirect()->route('admin.orders.index')->with('success', 'تم الحذف النهائي للطلب بنجاح.');
    }

    private function delayedOrdersQuery()
    {
        $delayHours = max((int) env('ADMIN_ORDER_DELAY_HOURS', 10), 1);

        return $this->ordersDomainService->ordersQuery()
            ->whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_APPROVED,
                Order::STATUS_ASSIGNED,
                Order::STATUS_OUT_FOR_DELIVERY,
            ])
            ->where('updated_at', '<=', now()->subHours($delayHours));
    }
}
