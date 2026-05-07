<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Modules\Delivery\Services\DeliveryDomainService;
use App\Modules\Delivery\Services\SmartDispatchService;
use App\Modules\Orders\Services\OrdersDomainService;
use App\Models\Orders\Order;
use App\Services\Orders\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDeliveryController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrdersDomainService $ordersDomainService,
        private readonly DeliveryDomainService $deliveryDomainService,
        private readonly SmartDispatchService $smartDispatchService,
    ) {}

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $branchId = (int) $request->query('branch_id', 0);
        $distributorId = (int) $request->query('distributor_id', 0);

        $activeStatuses = [
            Order::STATUS_PENDING,
            Order::STATUS_APPROVED,
            Order::STATUS_ASSIGNED,
            Order::STATUS_OUT_FOR_DELIVERY,
        ];

        $orders = $this->ordersDomainService->ordersQuery()
            ->with([
                'supplier:id,owner_name,business_name',
                'branch:id,name,supplier_id',
                'distributor:id,name,supplier_id,branch_id,status',
            ])
            ->whereIn('status', $activeStatuses)
            ->when(in_array($status, $activeStatuses, true), fn(Builder $query) => $query->where('status', $status))
            ->when($branchId > 0, fn(Builder $query) => $query->where('branch_id', $branchId))
            ->when($distributorId > 0, fn(Builder $query) => $query->where('distributor_id', $distributorId))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $orderIds = $orders->getCollection()->pluck('id')->all();
        $latestLocationIds = $this->smartDispatchService->latestLocationIdsByOrderIds($orderIds);

        $latestLocations = $latestLocationIds === []
            ? collect()
            : $this->deliveryDomainService->locationLogsQuery()
            ->whereIn('distributor_location_logs.id', $latestLocationIds)
            ->get()
            ->keyBy('order_id');

        $orderSupplierIds = $orders->getCollection()
            ->pluck('supplier_id')
            ->filter()
            ->unique()
            ->all();

        $distributorsBySupplier = $this->deliveryDomainService->distributorsQuery()
            ->whereIn('supplier_id', $orderSupplierIds)
            ->where('status', Account::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'supplier_id', 'name'])
            ->groupBy('supplier_id');

        $distributorTasks = $this->deliveryDomainService->distributorsQuery()
            ->with(['branch:id,name'])
            ->withCount(['orders as active_orders_count' => function (Builder $query) use ($activeStatuses): void {
                $query->whereIn('status', $activeStatuses);
            }])
            ->where('status', Account::STATUS_ACTIVE)
            ->having('active_orders_count', '>', 0)
            ->orderByDesc('active_orders_count')
            ->limit(20)
            ->get(['id', 'name', 'branch_id', 'supplier_id']);

        $branches = $this->deliveryDomainService->branchesQuery()->orderBy('name')->get(['id', 'name']);
        $distributorsFilter = $this->deliveryDomainService->distributorsQuery()->orderBy('name')->get(['id', 'name']);

        $stats = [
            'active_orders' => $this->ordersDomainService->ordersQuery()->whereIn('status', $activeStatuses)->count(),
            'out_for_delivery' => $this->ordersDomainService->ordersQuery()->where('status', Order::STATUS_OUT_FOR_DELIVERY)->count(),
            'without_distributor' => $this->ordersDomainService->ordersQuery()
                ->whereIn('status', $activeStatuses)
                ->whereNull('distributor_id')
                ->count(),
        ];

        return view('admin.delivery.index', compact(
            'orders',
            'latestLocations',
            'distributorsBySupplier',
            'distributorTasks',
            'branches',
            'distributorsFilter',
            'stats'
        ));
    }

    public function assignDistributor(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'distributor_id' => ['nullable', 'integer', 'exists:distributors,id'],
        ]);

        $distributorId = isset($data['distributor_id']) && (int) $data['distributor_id'] > 0
            ? (int) $data['distributor_id']
            : null;

        if ($distributorId !== null) {
            $distributor = $this->deliveryDomainService->distributorsQuery()->findOrFail($distributorId);

            if ((int) $distributor->supplier_id !== (int) $order->supplier_id) {
                return back()->with('error', 'المندوب لا يتبع نفس الوكيل الخاص بالطلب.');
            }

            if ($distributor->status !== Account::STATUS_ACTIVE) {
                return back()->with('error', 'لا يمكن تعيين مندوب غير نشط.');
            }
        }

        $this->orderService->assignDistributor($order, $distributorId);

        return back()->with('success', 'تم تحديث تعيين المندوب بنجاح.');
    }
}
