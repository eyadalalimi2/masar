<?php

namespace App\Services\Reports;

use App\Models\Orders\Order;
use App\Models\Orders\Order as CustomerOrder;
use App\Models\Supplier\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportService
{
    private const REPORT_CACHE_MINUTES = 10;

    public function getSalesSummary(?int $supplierId = null, array $filters = []): array
    {
        return Cache::remember(
            $this->cacheKey('sales_summary', $supplierId, $filters),
            $this->cacheTtl(),
            function () use ($supplierId, $filters): array {
                $query = $this->applyOrderFilters($this->ordersQuery($supplierId), $filters)->where('status', Order::STATUS_DELIVERED);

                $totalSales = $this->sumOrderRevenue($query);
                $ordersCount = (int) $query->count();

                return [
                    'total_sales' => $totalSales,
                    'orders_count' => $ordersCount,
                    'average_order' => $ordersCount > 0 ? $totalSales / $ordersCount : 0,
                ];
            }
        );
    }

    public function getOrdersStats(?int $supplierId = null, array $filters = []): array
    {
        $orders = $this->applyOrderFilters($this->ordersQuery($supplierId), $filters)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'pending' => (int) ($orders['pending'] ?? 0),
            'delivered' => (int) ($orders['delivered'] ?? 0),
            'cancelled' => (int) ($orders['cancelled'] ?? 0),
        ];
    }

    public function getTopProducts(?int $supplierId = null, int $limit = 8, array $filters = []): Collection
    {
        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.status', Order::STATUS_DELIVERED)
            ->when($supplierId !== null, function ($query) use ($supplierId) {
                $query->where('orders.supplier_id', $supplierId);
            })
            ->select(
                'order_items.product_id',
                'products.name',
                DB::raw('SUM(order_items.quantity) as sold_quantity'),
                DB::raw('SUM(order_items.total) as revenue')
            )
            ->groupBy('order_items.product_id', 'products.name')
            ->orderByDesc('sold_quantity')
            ->limit($limit);

        return $this->applyOrderFilters($query, $filters, 'orders')->get();
    }

    public function getTopDistributors(?int $supplierId = null, int $limit = 8, array $filters = []): Collection
    {
        $query = DB::table('orders')
            ->join('distributors', 'distributors.id', '=', 'orders.distributor_id')
            ->where('orders.status', Order::STATUS_DELIVERED)
            ->when($supplierId !== null, function ($query) use ($supplierId) {
                $query->where('orders.supplier_id', $supplierId);
            })
            ->select(
                'distributors.id',
                'distributors.name',
                DB::raw('COUNT(orders.id) as orders_count'),
                DB::raw('SUM(' . $this->orderRevenueExpression('orders') . ') as revenue')
            )
            ->groupBy('distributors.id', 'distributors.name')
            ->orderByDesc('orders_count')
            ->orderByDesc('revenue')
            ->limit($limit);

        return $this->applyOrderFilters($query, $filters, 'orders')->get();
    }

    public function getRevenueReport(?int $supplierId = null, array $filters = []): array
    {
        return Cache::remember(
            $this->cacheKey('revenue_report', $supplierId, $filters),
            $this->cacheTtl(),
            function () use ($supplierId, $filters): array {
                $startDaily = Carbon::today()->subDays(29);

                $dailyRaw = $this->applyOrderFilters($this->ordersQuery($supplierId), $filters)
                    ->where('status', Order::STATUS_DELIVERED)
                    ->whereDate('created_at', '>=', $startDaily)
                    ->selectRaw('DATE(created_at) as day, SUM(' . $this->orderRevenueExpression() . ') as total')
                    ->groupBy('day')
                    ->pluck('total', 'day');

                $dailyLabels = [];
                $dailyValues = [];

                for ($i = 0; $i < 30; $i++) {
                    $date = $startDaily->copy()->addDays($i);
                    $key = $date->toDateString();
                    $dailyLabels[] = $date->format('m/d');
                    $dailyValues[] = (float) ($dailyRaw[$key] ?? 0);
                }

                $startMonth = Carbon::today()->startOfMonth()->subMonths(11);

                $monthlyRaw = $this->applyOrderFilters($this->ordersQuery($supplierId), $filters)
                    ->where('status', Order::STATUS_DELIVERED)
                    ->whereDate('created_at', '>=', $startMonth)
                    ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, SUM(" . $this->orderRevenueExpression() . ") as total")
                    ->groupBy('month_key')
                    ->pluck('total', 'month_key');

                $monthlyLabels = [];
                $monthlyValues = [];

                for ($i = 0; $i < 12; $i++) {
                    $month = $startMonth->copy()->addMonths($i);
                    $key = $month->format('Y-m');
                    $monthlyLabels[] = $month->format('Y/m');
                    $monthlyValues[] = (float) ($monthlyRaw[$key] ?? 0);
                }

                return [
                    'daily' => [
                        'labels' => $dailyLabels,
                        'values' => $dailyValues,
                    ],
                    'monthly' => [
                        'labels' => $monthlyLabels,
                        'values' => $monthlyValues,
                    ],
                ];
            }
        );
    }

    public function getCustomerDebtReport(?int $supplierId = null, array $filters = []): array
    {
        $accounts = DB::table('accounts')
            ->leftJoin('customers', 'customers.id', '=', 'accounts.owner_id')
            ->where('accounts.account_type', 'customer')
            ->where('accounts.balance', '>', 0)
            ->select('accounts.*', 'customers.phone as customer_phone');

        if ($supplierId !== null) {
            $customerIds = $this->applyOrderFilters($this->ordersQuery($supplierId), $filters)
                ->whereNotNull('customer_id')
                ->distinct()
                ->pluck('customer_id');

            $accounts->whereIn('accounts.owner_id', $customerIds);
        }

        $debtors = $accounts->orderByDesc('balance')->get();

        return [
            'debtors_count' => $debtors->count(),
            'total_debt' => (float) $debtors->sum('balance'),
            'debtors' => $debtors,
        ];
    }

    public function getDashboardCards(?int $supplierId = null, array $filters = []): array
    {
        return Cache::remember(
            $this->cacheKey('dashboard_cards', $supplierId, $filters),
            $this->cacheTtl(),
            function () use ($supplierId, $filters): array {
                $ordersQuery = $this->applyOrderFilters($this->ordersQuery($supplierId), $filters);

                return [
                    'total_orders' => (int) $ordersQuery->count(),
                    'total_revenue' => $this->sumOrderRevenue(
                        $this->applyOrderFilters($this->ordersQuery($supplierId), $filters)
                            ->where('status', Order::STATUS_DELIVERED)
                    ),
                    'customers_count' => (int) $this->applyOrderFilters($this->ordersQuery($supplierId), $filters)
                        ->distinct('snapshot_customer_phone')
                        ->count('snapshot_customer_phone'),
                    'agents_count' => $supplierId === null
                        ? (int) Supplier::count()
                        : 1,
                ];
            }
        );
    }

    public function getCoverageInsights(int $supplierId, array $filters = []): array
    {
        return Cache::remember(
            $this->cacheKey('coverage_insights', $supplierId, $filters),
            $this->cacheTtl(),
            function () use ($supplierId, $filters): array {
                $fromDate = Carbon::now()->subDays(30);
                $filterFromDate = $this->normalizeDate($filters['from_date'] ?? null);
                if ($filterFromDate !== null) {
                    $fromDate = Carbon::parse($filterFromDate);
                }

                $branchPerformance = DB::table('branches')
                    ->leftJoin('orders', function ($join) {
                        $join->on('orders.branch_id', '=', 'branches.id')
                            ->where('orders.status', '=', Order::STATUS_DELIVERED);
                    })
                    ->where('branches.supplier_id', $supplierId)
                    ->select(
                        'branches.id',
                        'branches.name',
                        'branches.gps_location',
                        DB::raw('COUNT(orders.id) as delivered_orders'),
                        DB::raw('COALESCE(SUM(' . $this->orderRevenueExpression('orders') . '), 0) as delivered_revenue')
                    )
                    ->groupBy('branches.id', 'branches.name', 'branches.gps_location')
                    ->orderByDesc('delivered_orders');

                $branchPerformance = $this->applyOrderFilters($branchPerformance, $filters, 'orders')->get();

                $activeAreas = DB::table('orders')
                    ->where('supplier_id', $supplierId)
                    ->where('status', Order::STATUS_DELIVERED)
                    ->whereNotNull('snapshot_customer_address')
                    ->where('snapshot_customer_address', '!=', '')
                    ->select(
                        'snapshot_customer_address as customer_address',
                        DB::raw('COUNT(id) as delivered_orders'),
                        DB::raw('SUM(' . $this->orderRevenueExpression() . ') as delivered_revenue')
                    )
                    ->groupBy('snapshot_customer_address')
                    ->orderByDesc('delivered_orders')
                    ->limit(12);

                $activeAreas = $this->applyOrderFilters($activeAreas, $filters, 'orders')->get();

                $underperformingBranches = DB::table('branches')
                    ->leftJoin('orders', function ($join) use ($fromDate) {
                        $join->on('orders.branch_id', '=', 'branches.id')
                            ->where('orders.status', '=', Order::STATUS_DELIVERED)
                            ->where('orders.created_at', '>=', $fromDate);
                    })
                    ->where('branches.supplier_id', $supplierId)
                    ->where('branches.status', 'active')
                    ->select('branches.id', 'branches.name', DB::raw('COUNT(orders.id) as delivered_orders_30d'))
                    ->groupBy('branches.id', 'branches.name')
                    ->havingRaw('COUNT(orders.id) = 0');

                $underperformingBranches = $this->applyOrderFilters($underperformingBranches, $filters, 'orders')->get();

                $expansionOpportunities = DB::table('orders')
                    ->where('supplier_id', $supplierId)
                    ->where('created_at', '>=', $fromDate)
                    ->whereNotNull('snapshot_customer_address')
                    ->where('snapshot_customer_address', '!=', '')
                    ->select('snapshot_customer_address as customer_address', DB::raw('COUNT(id) as orders_count'))
                    ->groupBy('snapshot_customer_address')
                    ->orderByDesc('orders_count')
                    ->limit(8);

                $expansionOpportunities = $this->applyOrderFilters($expansionOpportunities, $filters, 'orders')->get();

                return [
                    'branch_performance' => $branchPerformance,
                    'active_areas' => $activeAreas,
                    'underperforming_branches' => $underperformingBranches,
                    'expansion_opportunities' => $expansionOpportunities,
                    'uncovered_areas' => $this->getUncoveredAreas($supplierId, $filters),
                ];
            }
        );
    }

    public function getDemandForecast(int $supplierId, array $filters = []): array
    {
        return Cache::remember(
            $this->cacheKey('demand_forecast', $supplierId, $filters),
            $this->cacheTtl(),
            function () use ($supplierId, $filters): array {
                $currentStart = Carbon::now()->subDays(30);
                $previousStart = Carbon::now()->subDays(60);

                $currentOrders = (int) $this->applyOrderFilters($this->ordersQuery($supplierId), $filters)
                    ->where('status', Order::STATUS_DELIVERED)
                    ->where('created_at', '>=', $currentStart)
                    ->count();

                $previousOrders = (int) $this->applyOrderFilters($this->ordersQuery($supplierId), $filters)
                    ->where('status', Order::STATUS_DELIVERED)
                    ->whereBetween('created_at', [$previousStart, $currentStart])
                    ->count();

                $currentRevenue = $this->sumOrderRevenue(
                    $this->applyOrderFilters($this->ordersQuery($supplierId), $filters)
                        ->where('status', Order::STATUS_DELIVERED)
                        ->where('created_at', '>=', $currentStart)
                );

                $previousRevenue = $this->sumOrderRevenue(
                    $this->applyOrderFilters($this->ordersQuery($supplierId), $filters)
                        ->where('status', Order::STATUS_DELIVERED)
                        ->whereBetween('created_at', [$previousStart, $currentStart])
                );

                $ordersChange = $previousOrders > 0
                    ? (($currentOrders - $previousOrders) / $previousOrders) * 100
                    : 0.0;

                $revenueChange = $previousRevenue > 0
                    ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100
                    : 0.0;

                return [
                    'current_orders_30d' => $currentOrders,
                    'previous_orders_30d' => $previousOrders,
                    'orders_change_percent' => $ordersChange,
                    'projected_orders_next_30d' => max(0, (int) round($currentOrders + (($currentOrders - $previousOrders) * 0.5))),
                    'current_revenue_30d' => $currentRevenue,
                    'previous_revenue_30d' => $previousRevenue,
                    'revenue_change_percent' => $revenueChange,
                    'projected_revenue_next_30d' => max(0.0, $currentRevenue + (($currentRevenue - $previousRevenue) * 0.5)),
                ];
            }
        );
    }

    public function getBranchPerformanceComparison(int $supplierId, array $filters = []): Collection
    {
        $query = DB::table('branches')
            ->leftJoin('orders', function ($join) {
                $join->on('orders.branch_id', '=', 'branches.id')
                    ->where('orders.status', '=', Order::STATUS_DELIVERED);
            })
            ->where('branches.supplier_id', $supplierId)
            ->select(
                'branches.id',
                'branches.name',
                DB::raw('COUNT(orders.id) as delivered_orders'),
                DB::raw('COALESCE(SUM(' . $this->orderRevenueExpression('orders') . '), 0) as delivered_revenue'),
                DB::raw('COALESCE(AVG(' . $this->orderRevenueExpression('orders') . '), 0) as avg_order_value')
            )
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('delivered_revenue');

        $rows = $this->applyOrderFilters($query, $filters, 'orders')->get()->values();
        $totalRevenue = (float) $rows->sum('delivered_revenue');

        return $rows->map(function ($row, $index) use ($totalRevenue) {
            $row->rank = $index + 1;
            $row->revenue_share_percent = $totalRevenue > 0
                ? (((float) $row->delivered_revenue / $totalRevenue) * 100)
                : 0.0;

            return $row;
        });
    }

    public function getUncoveredAreas(int $supplierId, array $filters = []): Collection
    {
        $delivered = Order::STATUS_DELIVERED;
        $pending = Order::STATUS_PENDING;
        $approved = Order::STATUS_APPROVED;
        $assigned = Order::STATUS_ASSIGNED;
        $outForDelivery = Order::STATUS_OUT_FOR_DELIVERY;
        $cancelled = Order::STATUS_CANCELLED;

        $query = DB::table('orders')
            ->where('supplier_id', $supplierId)
            ->whereNotNull('snapshot_customer_address')
            ->where('snapshot_customer_address', '!=', '')
            ->select(
                'snapshot_customer_address as customer_address',
                DB::raw("SUM(CASE WHEN status = '{$delivered}' THEN 1 ELSE 0 END) as delivered_orders"),
                DB::raw("SUM(CASE WHEN status IN ('{$pending}','{$approved}','{$assigned}','{$outForDelivery}','{$cancelled}') THEN 1 ELSE 0 END) as unmet_orders")
            )
            ->groupBy('snapshot_customer_address')
            ->havingRaw("SUM(CASE WHEN status = '{$delivered}' THEN 1 ELSE 0 END) = 0")
            ->havingRaw("SUM(CASE WHEN status IN ('{$pending}','{$approved}','{$assigned}','{$outForDelivery}','{$cancelled}') THEN 1 ELSE 0 END) > 0")
            ->orderByDesc('unmet_orders')
            ->limit(12);

        return $this->applyOrderFilters($query, $filters, 'orders')->get();
    }

    public function getLowDemandProducts(int $supplierId, array $filters = [], int $maxSoldQty = 2): Collection
    {
        $fromDate = $this->normalizeDate($filters['from_date'] ?? null);
        $toDate = $this->normalizeDate($filters['to_date'] ?? null);

        $from = $fromDate ? Carbon::parse($fromDate)->startOfDay() : Carbon::now()->subDays(30);
        $to = $toDate ? Carbon::parse($toDate)->endOfDay() : Carbon::now()->endOfDay();

        return DB::table('product_units')
            ->join('products', 'products.id', '=', 'product_units.product_id')
            ->leftJoin('order_items', 'order_items.product_unit_id', '=', 'product_units.id')
            ->leftJoin('orders', function ($join) use ($supplierId, $from, $to) {
                $join->on('orders.id', '=', 'order_items.order_id')
                    ->where('orders.supplier_id', '=', $supplierId)
                    ->where('orders.status', '=', Order::STATUS_DELIVERED)
                    ->whereBetween('orders.created_at', [$from, $to]);
            })
            ->where('products.supplier_id', $supplierId)
            ->select(
                'product_units.id as product_unit_id',
                'products.id as product_id',
                'products.name as product_name',
                'products.model',
                'product_units.stock_quantity',
                'product_units.low_stock_threshold',
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as sold_quantity_30d')
            )
            ->groupBy(
                'product_units.id',
                'products.id',
                'products.name',
                'products.model',
                'product_units.stock_quantity',
                'product_units.low_stock_threshold'
            )
            ->havingRaw('COALESCE(SUM(order_items.quantity), 0) <= ?', [$maxSoldQty])
            ->orderBy('sold_quantity_30d')
            ->orderByDesc('product_units.stock_quantity')
            ->limit(20)
            ->get();
    }

    protected function ordersQuery(?int $supplierId = null): Builder
    {
        return Order::query()->when($supplierId !== null, function ($query) use ($supplierId) {
            $query->where('supplier_id', $supplierId);
        });
    }

    private function applyOrderFilters(Builder|QueryBuilder $query, array $filters = [], string $ordersTable = 'orders'): Builder|QueryBuilder
    {
        $fromDate = $this->normalizeDate($filters['from_date'] ?? null);
        if ($fromDate !== null) {
            $query->whereDate($ordersTable . '.created_at', '>=', $fromDate);
        }

        $toDate = $this->normalizeDate($filters['to_date'] ?? null);
        if ($toDate !== null) {
            $query->whereDate($ordersTable . '.created_at', '<=', $toDate);
        }

        $branchId = isset($filters['branch_id']) ? (int) $filters['branch_id'] : 0;
        if ($branchId > 0) {
            $query->where($ordersTable . '.branch_id', $branchId);
        }

        $customerBusinessType = $filters['customer_business_type'] ?? null;
        if (in_array($customerBusinessType, ['retail_store', 'workshop'], true)) {
            $query->whereExists(function ($subQuery) use ($ordersTable, $customerBusinessType) {
                $subQuery->selectRaw('1')
                    ->from('customers')
                    ->whereColumn('customers.id', $ordersTable . '.buyer_id')
                    ->where($ordersTable . '.buyer_type', CustomerOrder::BUYER_TYPE_CUSTOMER)
                    ->where('customers.type', $customerBusinessType);
            });
        }

        return $query;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function orderRevenueExpression(string $ordersTable = 'orders'): string
    {
        return 'COALESCE(' . $ordersTable . '.payable_total, ' . $ordersTable . '.total_price)';
    }

    private function sumOrderRevenue(Builder|QueryBuilder $query, string $ordersTable = 'orders'): float
    {
        return (float) $query->sum(DB::raw($this->orderRevenueExpression($ordersTable)));
    }

    private function cacheKey(string $segment, ?int $supplierId = null, array $filters = [], array $extra = []): string
    {
        return 'reports:' . $segment . ':' . md5(json_encode([
            'supplier_id' => $supplierId,
            'filters' => $filters,
            'extra' => $extra,
        ], JSON_UNESCAPED_UNICODE));
    }

    private function cacheTtl(): \DateTimeInterface
    {
        $minutes = self::REPORT_CACHE_MINUTES;
        $minutes = $minutes > 0 ? $minutes : 10;

        return now()->addMinutes($minutes);
    }
}
