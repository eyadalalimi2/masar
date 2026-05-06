<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Modules\Accounting\Services\AccountingDomainService;
use App\Modules\Delivery\Services\DeliveryDomainService;
use App\Modules\Inventory\Services\InventoryDomainService;
use App\Models\Notifications\WebAlert;
use App\Modules\Orders\Services\OrdersDomainService;
use App\Models\Orders\Order;
use App\Modules\Pos\Services\PosDomainService;
use App\Modules\Users\Services\UsersDomainService;
use App\Services\Notifications\WebAlertService;
use App\Services\Operations\OperationalMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly OrdersDomainService $ordersDomainService,
        private readonly DeliveryDomainService $deliveryDomainService,
        private readonly UsersDomainService $usersDomainService,
        private readonly InventoryDomainService $inventoryDomainService,
        private readonly AccountingDomainService $accountingDomainService,
        private readonly PosDomainService $posDomainService,
        private readonly WebAlertService $webAlertService,
        private readonly OperationalMonitoringService $operationalMonitoringService,
    ) {}

    public function index(): View
    {
        $adminId = (int) (Auth::guard('admin')->id() ?? 0);
        $delayHours = max((int) env('ADMIN_ORDER_DELAY_HOURS', 10), 1);

        $delayedOrdersCount = (int) $this->ordersDomainService->ordersQuery()
            ->whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_APPROVED,
                Order::STATUS_ASSIGNED,
                Order::STATUS_OUT_FOR_DELIVERY,
            ])
            ->where('updated_at', '<=', now()->subHours($delayHours))
            ->count();

        $delayedByStage = [
            'supplier_stage_delays' => (int) $this->ordersDomainService->ordersQuery()
                ->where('status', Order::STATUS_PENDING)
                ->where('updated_at', '<=', now()->subHours($delayHours))
                ->count(),
            'branch_stage_delays' => (int) $this->ordersDomainService->ordersQuery()
                ->whereIn('status', [
                    Order::STATUS_APPROVED,
                    Order::STATUS_ASSIGNED,
                ])
                ->whereNotNull('branch_id')
                ->where('updated_at', '<=', now()->subHours($delayHours))
                ->count(),
            'delivery_stage_delays' => (int) $this->ordersDomainService->ordersQuery()
                ->whereIn('status', [Order::STATUS_OUT_FOR_DELIVERY])
                ->whereNotNull('distributor_id')
                ->where('updated_at', '<=', now()->subHours($delayHours))
                ->count(),
        ];

        $adminDelayAlertsTodayCount = 0;
        if ($adminId > 0 && $delayedOrdersCount > 0) {
            $title = 'تنبيه تأخير على لوحة الإدارة';
            $existsToday = WebAlert::query()
                ->where('recipient_type', 'admin')
                ->where('recipient_id', $adminId)
                ->whereDate('created_at', now()->toDateString())
                ->where('title', $title)
                ->exists();

            if (! $existsToday) {
                $this->webAlertService->create(
                    'admin',
                    $adminId,
                    $title,
                    'يوجد ' . $delayedOrdersCount . ' طلب متأخر يحتاج متابعة إدارية.',
                    [
                        'type' => 'admin_dashboard_delay_alert',
                        'delayed_orders_count' => $delayedOrdersCount,
                        'delay_hours_threshold' => $delayHours,
                    ]
                );
            }
        }

        if ($adminId > 0) {
            $adminDelayAlertsTodayCount = (int) WebAlert::query()
                ->where('recipient_type', 'admin')
                ->where('recipient_id', $adminId)
                ->whereDate('created_at', now()->toDateString())
                ->whereIn('title', ['تنبيه تأخير على لوحة الإدارة', 'تنبيه تأخير طلبات النظام'])
                ->count();
        }

        $stats = [
            'suppliers_count' => $this->usersDomainService->suppliersQuery()->count(),
            'branches_count' => $this->deliveryDomainService->branchesQuery()->count(),
            'distributors_count' => $this->deliveryDomainService->distributorsQuery()->count(),
            'commercial_stores_count' => $this->usersDomainService->customersQuery()->where('type', 'retail_store')->count(),
            'workshops_count' => $this->usersDomainService->customersQuery()->where('type', 'workshop')->count(),
            'products_count' => $this->inventoryDomainService->productsQuery()->count(),
            'orders_count' => $this->ordersDomainService->ordersQuery()->count(),
            'today_orders_count' => $this->ordersDomainService->ordersQuery()->whereDate('created_at', now()->toDateString())->count(),
            'pending_orders_count' => $this->ordersDomainService->ordersQuery()->whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_APPROVED,
                Order::STATUS_ASSIGNED,
                Order::STATUS_OUT_FOR_DELIVERY,
            ])->count(),
            'today_sales' => (float) $this->ordersDomainService->ordersQuery()->whereDate('created_at', now()->toDateString())->sum(DB::raw('COALESCE(payable_total, total_price)')),
            'payments_paid' => (float) $this->accountingDomainService->paymentsQuery()->where('status', 'paid')->sum('amount'),
            'delayed_orders_count' => $delayedOrdersCount,
            'admin_delay_alerts_today_count' => $adminDelayAlertsTodayCount,
            'supplier_stage_delays' => $delayedByStage['supplier_stage_delays'],
            'branch_stage_delays' => $delayedByStage['branch_stage_delays'],
            'delivery_stage_delays' => $delayedByStage['delivery_stage_delays'],
            'active_users_count' => $this->usersDomainService->agentsQuery()->where('status', Account::STATUS_ACTIVE)->count()
                + $this->usersDomainService->branchAccountsQuery()->where('status', Account::STATUS_ACTIVE)->count()
                + $this->usersDomainService->distributorAccountsQuery()->where('status', Account::STATUS_ACTIVE)->count()
                + $this->posDomainService->posAccountsQuery()->where('status', Account::STATUS_ACTIVE)->count()
                + $this->usersDomainService->customersQuery()->where('status', Account::STATUS_ACTIVE)->count(),
            'important_alerts_count' => WebAlert::query()
                ->whereNull('read_at')
                ->where(function ($query) {
                    $query->where('title', 'like', '%هام%')
                        ->orWhere('title', 'like', '%تحذير%')
                        ->orWhere('body', 'like', '%هام%')
                        ->orWhere('body', 'like', '%تحذير%');
                })
                ->count(),
        ];

        $importantAlerts = WebAlert::query()
            ->whereNull('read_at')
            ->where(function ($query) {
                $query->where('title', 'like', '%هام%')
                    ->orWhere('title', 'like', '%تحذير%')
                    ->orWhere('body', 'like', '%هام%')
                    ->orWhere('body', 'like', '%تحذير%');
            })
            ->latest()
            ->limit(6)
            ->get(['id', 'title', 'body', 'recipient_type', 'created_at']);

        $latestOrders = $this->ordersDomainService->ordersQuery()
            ->with(['supplier:id,business_name,owner_name,agent_image'])
            ->latest()
            ->limit(8)
            ->get(['id', 'supplier_id', 'snapshot_customer_name', 'total_price', 'payable_total', 'status', 'created_at']);

        $criticalDelayedOrders = $this->ordersDomainService->ordersQuery()
            ->with(['supplier:id,business_name,owner_name'])
            ->whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_APPROVED,
                Order::STATUS_ASSIGNED,
                Order::STATUS_OUT_FOR_DELIVERY,
            ])
            ->where('updated_at', '<=', now()->subHours($delayHours))
            ->orderBy('updated_at')
            ->limit(8)
            ->get(['id', 'supplier_id', 'snapshot_customer_name', 'status', 'updated_at']);

        $latestUsers = collect()
            ->concat($this->usersDomainService->agentsQuery()->latest()->limit(8)->get(['id', 'name', 'phone', 'created_at'])->map(fn($u) => (object) [
                'id' => $u->id,
                'name' => $u->name,
                'phone' => $u->phone,
                'role' => 'supplier',
                'created_at' => $u->created_at,
            ]))
            ->concat($this->usersDomainService->branchAccountsQuery()->latest()->limit(8)->get(['id', 'name', 'phone', 'created_at'])->map(fn($u) => (object) [
                'id' => $u->id,
                'name' => $u->name,
                'phone' => $u->phone,
                'role' => 'branch',
                'created_at' => $u->created_at,
            ]))
            ->concat($this->usersDomainService->distributorAccountsQuery()->latest()->limit(8)->get(['id', 'name', 'phone', 'created_at'])->map(fn($u) => (object) [
                'id' => $u->id,
                'name' => $u->name,
                'phone' => $u->phone,
                'role' => 'distributor',
                'created_at' => $u->created_at,
            ]))
            ->sortByDesc('created_at')
            ->take(8)
            ->values();

        $realtime = $this->buildRealtimeMetrics();

        return view('admin.dashboard', compact('stats', 'latestOrders', 'latestUsers', 'importantAlerts', 'criticalDelayedOrders', 'realtime'));
    }

    public function liveMetrics(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'metrics' => $this->buildRealtimeMetrics(),
        ]);
    }

    public function advancedMetrics(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'advanced_bi' => $this->buildAdvancedBiMetrics(),
            'monitoring' => $this->buildRealtimeMonitoringSnapshot(),
            'kpi_contract' => config('kpi.contract', []),
        ]);
    }

    private function buildRealtimeMetrics(): array
    {
        $delayHours = max((int) env('ADMIN_ORDER_DELAY_HOURS', 10), 1);

        return [
            'active_orders_now' => (int) $this->ordersDomainService->ordersQuery()
                ->whereIn('status', [
                    Order::STATUS_PENDING,
                    Order::STATUS_APPROVED,
                    Order::STATUS_ASSIGNED,
                    Order::STATUS_OUT_FOR_DELIVERY,
                ])
                ->count(),
            'out_for_delivery_now' => (int) $this->ordersDomainService->ordersQuery()
                ->where('status', Order::STATUS_OUT_FOR_DELIVERY)
                ->count(),
            'delivered_today' => (int) $this->ordersDomainService->ordersQuery()
                ->where('status', Order::STATUS_DELIVERED)
                ->whereDate('updated_at', now()->toDateString())
                ->count(),
            'sales_today' => (float) $this->ordersDomainService->ordersQuery()
                ->whereDate('created_at', now()->toDateString())
                ->sum(DB::raw('COALESCE(payable_total, total_price)')),
            'new_users_today' => (int) (
                $this->usersDomainService->agentsQuery()->whereDate('created_at', now()->toDateString())->count()
                + $this->usersDomainService->branchAccountsQuery()->whereDate('created_at', now()->toDateString())->count()
                + $this->usersDomainService->distributorAccountsQuery()->whereDate('created_at', now()->toDateString())->count()
                + $this->posDomainService->posAccountsQuery()->whereDate('created_at', now()->toDateString())->count()
                + $this->usersDomainService->customersQuery()->whereDate('created_at', now()->toDateString())->count()
            ),
            'delayed_orders_now' => (int) $this->ordersDomainService->ordersQuery()
                ->whereIn('status', [
                    Order::STATUS_PENDING,
                    Order::STATUS_APPROVED,
                    Order::STATUS_ASSIGNED,
                    Order::STATUS_OUT_FOR_DELIVERY,
                ])
                ->where('updated_at', '<=', now()->subHours($delayHours))
                ->count(),
        ];
    }

    private function buildAdvancedBiMetrics(): array
    {
        return Cache::remember('admin:dashboard:advanced-bi:v1', now()->addMinutes(5), function (): array {
            $deliveredLast7 = $this->ordersDomainService->ordersQuery()
                ->where('status', Order::STATUS_DELIVERED)
                ->where('updated_at', '>=', now()->subDays(7));

            $deliveredPrev7 = $this->ordersDomainService->ordersQuery()
                ->where('status', Order::STATUS_DELIVERED)
                ->whereBetween('updated_at', [now()->subDays(14), now()->subDays(7)]);

            $salesLast7 = (float) (clone $deliveredLast7)->sum(DB::raw('COALESCE(payable_total, total_price)'));
            $salesPrev7 = (float) (clone $deliveredPrev7)->sum(DB::raw('COALESCE(payable_total, total_price)'));

            $ordersLast7 = (int) (clone $deliveredLast7)->count();
            $ordersPrev7 = (int) (clone $deliveredPrev7)->count();

            $pendingTotal = (int) $this->ordersDomainService->ordersQuery()->whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_APPROVED,
                Order::STATUS_ASSIGNED,
                Order::STATUS_OUT_FOR_DELIVERY,
            ])->count();
            $slaOnTime = (int) $this->ordersDomainService->ordersQuery()
                ->where('status', Order::STATUS_DELIVERED)
                ->where('updated_at', '>=', now()->subDays(30))
                ->where('updated_at', '<=', DB::raw('DATE_ADD(created_at, INTERVAL 24 HOUR)'))
                ->count();
            $slaDelivered = (int) $this->ordersDomainService->ordersQuery()
                ->where('status', Order::STATUS_DELIVERED)
                ->where('updated_at', '>=', now()->subDays(30))
                ->count();

            $customerGrowthCurrent = (int) $this->usersDomainService->customersQuery()->whereDate('created_at', '>=', now()->subDays(30)->toDateString())->count();
            $customerGrowthPrevious = (int) $this->usersDomainService->customersQuery()
                ->whereBetween('created_at', [now()->subDays(60)->toDateString(), now()->subDays(30)->toDateString()])
                ->count();

            return [
                'sales_7d' => $salesLast7,
                'sales_growth_percent_7d' => $salesPrev7 > 0 ? round((($salesLast7 - $salesPrev7) / $salesPrev7) * 100, 2) : 0.0,
                'delivered_orders_7d' => $ordersLast7,
                'orders_growth_percent_7d' => $ordersPrev7 > 0 ? round((($ordersLast7 - $ordersPrev7) / $ordersPrev7) * 100, 2) : 0.0,
                'pending_orders_total' => $pendingTotal,
                'sla_on_time_percent_30d' => $slaDelivered > 0 ? round(($slaOnTime / $slaDelivered) * 100, 2) : 0.0,
                'customer_growth_30d' => $customerGrowthCurrent,
                'customer_growth_delta_30d' => $customerGrowthCurrent - $customerGrowthPrevious,
            ];
        });
    }

    private function buildRealtimeMonitoringSnapshot(): array
    {
        $ttlSeconds = max((int) config('operations.thresholds.monitoring_snapshot_ttl_seconds', 30), 5);

        return Cache::remember('admin:dashboard:monitoring:v2', now()->addSeconds($ttlSeconds), function (): array {
            $snapshot = $this->operationalMonitoringService->snapshot(true);
            $metrics = (array) ($snapshot['metrics'] ?? []);

            return array_merge($metrics, [
                'health' => (array) ($snapshot['health'] ?? []),
                'thresholds' => (array) ($snapshot['thresholds'] ?? []),
            ]);
        });
    }
}
