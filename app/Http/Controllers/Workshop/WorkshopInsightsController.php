<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Models\Notifications\WebAlert;
use App\Models\Workshop\WorkshopService;
use App\Models\Workshop\WorkshopPurchaseOrder;
use App\Models\Workshop\WorkshopServiceOrder;
use App\Services\Notifications\WebAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WorkshopInsightsController extends Controller
{
    public function __construct(private readonly WebAlertService $webAlertService) {}

    public function execution(): View
    {
        $workshopId = $this->workshopId();
        $recentOrders = WorkshopServiceOrder::query()
            ->with('service:id,name')
            ->where('workshop_id', $workshopId)
            ->latest()
            ->limit(15)
            ->get();

        $slaSnapshot = $this->buildSlaSnapshot($workshopId);

        return view('workshop.execution.index', [
            'recentOrders' => $recentOrders,
            'slaSummary' => $slaSnapshot['summary'],
            'priorityOrders' => $slaSnapshot['priorityOrders']->take(8),
        ]);
    }

    public function generateSlaAlerts(): RedirectResponse
    {
        $workshopId = $this->workshopId();
        $slaSnapshot = $this->buildSlaSnapshot($workshopId);
        $today = now()->toDateString();
        $created = 0;

        foreach ($slaSnapshot['priorityOrders'] as $order) {
            if (! in_array($order->sla_priority_level, ['critical', 'high'], true)) {
                continue;
            }

            $title = 'تنبيه SLA لطلبات الخدمة';
            $body = 'الطلب ' . $order->order_number . ' في حالة ' . $order->sla_priority_label
                . ' وزمنه المتبقي ' . $order->sla_remaining_minutes . ' دقيقة.';

            $exists = WebAlert::query()
                ->where('recipient_type', 'workshop_account')
                ->where('recipient_id', $workshopId)
                ->whereDate('created_at', $today)
                ->where('title', $title)
                ->where('body', $body)
                ->exists();

            if ($exists) {
                continue;
            }

            $this->webAlertService->create(
                'workshop_account',
                $workshopId,
                $title,
                $body,
                [
                    'type' => 'workshop_sla_alert',
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'priority_level' => $order->sla_priority_level,
                    'remaining_minutes' => $order->sla_remaining_minutes,
                ]
            );

            $created++;
        }

        return back()->with('status', 'تم إنشاء ' . $created . ' تنبيه SLA.');
    }

    public function sales(Request $request): View
    {
        [$from, $to] = $this->resolveRange($request);

        $base = WorkshopServiceOrder::query()
            ->where('workshop_id', $this->workshopId())
            ->where('status', WorkshopServiceOrder::STATUS_COMPLETED)
            ->whereBetween('updated_at', [$from, $to]);

        $revenue = (float) (clone $base)->sum(DB::raw($this->workshopRevenueExpression()));
        $serviceRevenue = (float) (clone $base)->sum('service_fee');
        $productsRevenue = (float) (clone $base)->sum('products_total');
        $invoicesCount = (int) (clone $base)->count();

        $serviceShare = $revenue > 0 ? round(($serviceRevenue / $revenue) * 100, 1) : 0;
        $productShare = $revenue > 0 ? round(($productsRevenue / $revenue) * 100, 1) : 0;

        $latestInvoices = WorkshopServiceOrder::query()
            ->where('workshop_id', $this->workshopId())
            ->where('status', WorkshopServiceOrder::STATUS_COMPLETED)
            ->latest('updated_at')
            ->limit(10)
            ->get();

        return view('workshop.sales.index', compact(
            'revenue',
            'invoicesCount',
            'serviceShare',
            'productShare',
            'latestInvoices',
            'from',
            'to'
        ));
    }

    public function invoice(WorkshopServiceOrder $order): View
    {
        abort_unless((int) $order->workshop_id === $this->workshopId(), 403);

        $order->loadMissing('service:id,name');

        return view('workshop.sales.invoice', [
            'order' => $order,
        ]);
    }

    public function liveOverview(): JsonResponse
    {
        $workshopId = $this->workshopId();
        $slaSnapshot = $this->buildSlaSnapshot($workshopId);

        $metrics = [
            'new_service_orders' => (int) WorkshopServiceOrder::query()
                ->where('workshop_id', $workshopId)
                ->where('status', WorkshopServiceOrder::STATUS_REQUESTED)
                ->count(),
            'in_progress_orders' => (int) WorkshopServiceOrder::query()
                ->where('workshop_id', $workshopId)
                ->where('status', WorkshopServiceOrder::STATUS_IN_PROGRESS)
                ->count(),
            'today_completed_services' => (int) WorkshopServiceOrder::query()
                ->where('workshop_id', $workshopId)
                ->where('status', WorkshopServiceOrder::STATUS_COMPLETED)
                ->whereDate('updated_at', now()->toDateString())
                ->count(),
            'today_revenue' => (float) WorkshopServiceOrder::query()
                ->where('workshop_id', $workshopId)
                ->where('status', WorkshopServiceOrder::STATUS_COMPLETED)
                ->whereDate('updated_at', now()->toDateString())
                ->sum(DB::raw($this->workshopRevenueExpression())),
            'pending_purchase_orders' => (int) WorkshopPurchaseOrder::query()
                ->where('workshop_id', $workshopId)
                ->whereIn('status', [
                    WorkshopPurchaseOrder::STATUS_PENDING,
                    WorkshopPurchaseOrder::STATUS_APPROVED,
                    WorkshopPurchaseOrder::STATUS_IN_TRANSIT,
                ])
                ->count(),
            'sla_breached_orders' => (int) ($slaSnapshot['summary']['critical_orders_count'] ?? 0),
        ];

        $recentOrders = WorkshopServiceOrder::query()
            ->with('service:id,name')
            ->where('workshop_id', $workshopId)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn(WorkshopServiceOrder $order) => [
                'order_number' => $order->order_number,
                'service_name' => $order->service?->name ?? 'خدمة عامة',
                'products_total' => (float) $order->products_total,
                'status' => (string) $order->status,
            ])
            ->values();

        return response()->json([
            'metrics' => $metrics,
            'recent_orders' => $recentOrders,
            'updated_at' => now()->format('H:i:s'),
        ]);
    }

    private function buildSlaSnapshot(int $workshopId): array
    {
        $defaultSlaMinutes = max((int) env('WORKSHOP_DEFAULT_SLA_MINUTES', 120), 30);

        $orders = WorkshopServiceOrder::query()
            ->with('service:id,name,duration_minutes')
            ->where('workshop_id', $workshopId)
            ->whereIn('status', [
                WorkshopServiceOrder::STATUS_REQUESTED,
                WorkshopServiceOrder::STATUS_IN_PROGRESS,
            ])
            ->get();

        $priorityOrders = $orders->map(function (WorkshopServiceOrder $order) use ($defaultSlaMinutes) {
            $slaMinutes = (int) ($order->service?->duration_minutes ?? $defaultSlaMinutes);
            $elapsedMinutes = (int) $order->created_at->diffInMinutes(now());
            $remainingMinutes = $slaMinutes - $elapsedMinutes;

            $priorityLevel = 'normal';
            $priorityRank = 4;
            $priorityLabel = 'طبيعي';

            if ($remainingMinutes <= 0) {
                $priorityLevel = 'critical';
                $priorityRank = 1;
                $priorityLabel = 'متجاوز SLA';
            } elseif ($remainingMinutes <= 30) {
                $priorityLevel = 'high';
                $priorityRank = 2;
                $priorityLabel = 'عالي';
            } elseif ($remainingMinutes <= 90) {
                $priorityLevel = 'medium';
                $priorityRank = 3;
                $priorityLabel = 'متوسط';
            }

            $order->sla_minutes = $slaMinutes;
            $order->sla_elapsed_minutes = $elapsedMinutes;
            $order->sla_remaining_minutes = $remainingMinutes;
            $order->sla_priority_level = $priorityLevel;
            $order->sla_priority_rank = $priorityRank;
            $order->sla_priority_label = $priorityLabel;

            return $order;
        })->sort(function (WorkshopServiceOrder $a, WorkshopServiceOrder $b): int {
            $rankCompare = $a->sla_priority_rank <=> $b->sla_priority_rank;
            if ($rankCompare !== 0) {
                return $rankCompare;
            }

            return $a->created_at <=> $b->created_at;
        })->values();

        return [
            'priorityOrders' => $priorityOrders,
            'summary' => [
                'active_orders_count' => (int) $priorityOrders->count(),
                'critical_orders_count' => (int) $priorityOrders->where('sla_priority_level', 'critical')->count(),
                'high_orders_count' => (int) $priorityOrders->where('sla_priority_level', 'high')->count(),
                'average_remaining_minutes' => $priorityOrders->count() > 0
                    ? (int) round($priorityOrders->avg('sla_remaining_minutes'))
                    : 0,
            ],
        ];
    }

    public function pricing(): View
    {
        $serviceRows = WorkshopService::query()
            ->leftJoin('workshop_service_orders as o', function ($join): void {
                $join->on('o.service_id', '=', 'workshop_services.id')
                    ->where('o.status', '=', WorkshopServiceOrder::STATUS_COMPLETED);
            })
            ->where('workshop_services.workshop_id', $this->workshopId())
            ->groupBy('workshop_services.id', 'workshop_services.name', 'workshop_services.price')
            ->orderBy('workshop_services.name')
            ->select([
                'workshop_services.id',
                'workshop_services.name',
                'workshop_services.price',
                DB::raw('COALESCE(AVG(o.products_total), 0) as avg_products_cost'),
                DB::raw('COALESCE(AVG(' . $this->workshopRevenueExpression('o') . '), workshop_services.price) as avg_invoice'),
                DB::raw('COUNT(o.id) as completed_orders'),
            ])
            ->get()
            ->map(function ($row) {
                $avgInvoice = (float) $row->avg_invoice;
                $avgProductsCost = (float) $row->avg_products_cost;
                $margin = $avgInvoice > 0 ? round((($avgInvoice - $avgProductsCost) / $avgInvoice) * 100, 1) : 0;

                return [
                    'name' => $row->name,
                    'listed_price' => (float) $row->price,
                    'avg_products_cost' => $avgProductsCost,
                    'avg_invoice' => $avgInvoice,
                    'margin' => $margin,
                    'completed_orders' => (int) $row->completed_orders,
                ];
            });

        return view('workshop.pricing.index', compact('serviceRows'));
    }

    public function customers(): View
    {
        $customers = WorkshopServiceOrder::query()
            ->where('workshop_id', $this->workshopId())
            ->whereNotNull('snapshot_customer_phone')
            ->select([
                'snapshot_customer_phone as customer_phone',
                DB::raw('MAX(snapshot_customer_name) as customer_name'),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('MAX(updated_at) as last_visit_at'),
            ])
            ->groupBy('snapshot_customer_phone')
            ->orderByDesc('orders_count')
            ->limit(25)
            ->get();

        $thisMonth = WorkshopServiceOrder::query()
            ->where('workshop_id', $this->workshopId())
            ->whereBetween('updated_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->whereNotNull('snapshot_customer_phone')
            ->select('snapshot_customer_phone as customer_phone', DB::raw('COUNT(*) as orders_count'))
            ->groupBy('snapshot_customer_phone')
            ->get();

        $repeatCount = (int) $thisMonth->where('orders_count', '>', 1)->count();
        $newCount = (int) $thisMonth->where('orders_count', 1)->count();
        $totalDistinct = (int) max(1, $thisMonth->count());
        $avgOrdersPerCustomer = $thisMonth->count() > 0
            ? round($thisMonth->sum('orders_count') / $thisMonth->count(), 1)
            : 0;

        $repeatPercent = round(($repeatCount / $totalDistinct) * 100, 1);
        $newPercent = round(($newCount / $totalDistinct) * 100, 1);

        return view('workshop.customers.index', compact(
            'customers',
            'repeatPercent',
            'newPercent',
            'avgOrdersPerCustomer'
        ));
    }

    public function reports(Request $request): View
    {
        [$from, $to] = $this->resolveRange($request);

        $completedBase = WorkshopServiceOrder::query()
            ->where('workshop_id', $this->workshopId())
            ->where('status', WorkshopServiceOrder::STATUS_COMPLETED)
            ->whereBetween('updated_at', [$from, $to]);

        $servicesCount = (int) (clone $completedBase)->count();
        $revenue = (float) (clone $completedBase)->sum(DB::raw($this->workshopRevenueExpression()));
        $productsConsumptionValue = (float) (clone $completedBase)->sum('products_total');

        $topService = WorkshopServiceOrder::query()
            ->with('service:id,name')
            ->where('workshop_id', $this->workshopId())
            ->where('status', WorkshopServiceOrder::STATUS_COMPLETED)
            ->whereBetween('updated_at', [$from, $to])
            ->select('service_id', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('service_id')
            ->orderByDesc('aggregate')
            ->first();

        $topServiceName = $topService?->service?->name ?? 'لا يوجد';

        $previousStart = $from->copy()->subDays($from->diffInDays($to) + 1);
        $previousEnd = $from->copy()->subSecond();

        $previousRevenue = (float) WorkshopServiceOrder::query()
            ->where('workshop_id', $this->workshopId())
            ->where('status', WorkshopServiceOrder::STATUS_COMPLETED)
            ->whereBetween('updated_at', [$previousStart, $previousEnd])
            ->sum(DB::raw($this->workshopRevenueExpression()));

        $revenueDelta = $previousRevenue > 0
            ? round((($revenue - $previousRevenue) / $previousRevenue) * 100, 1)
            : null;

        $operationalNotes = [
            $revenueDelta === null
                ? 'لا توجد فترة سابقة كافية لحساب نسبة التغير.'
                : ($revenueDelta >= 0
                    ? 'الإيراد ارتفع بنسبة ' . $revenueDelta . '% مقارنة بالفترة السابقة.'
                    : 'الإيراد انخفض بنسبة ' . abs($revenueDelta) . '% مقارنة بالفترة السابقة.'),
            $topServiceName !== 'لا يوجد'
                ? 'الخدمة الأعلى طلبا: ' . $topServiceName . '.'
                : 'لا توجد خدمات مكتملة في الفترة المحددة.',
            'إجمالي قيمة المنتجات المستخدمة في التنفيذ: ' . number_format($productsConsumptionValue, 2) . ' ر.ي.',
        ];

        return view('workshop.reports.index', compact(
            'servicesCount',
            'revenue',
            'topServiceName',
            'productsConsumptionValue',
            'operationalNotes',
            'from',
            'to'
        ));
    }

    private function workshopId(): int
    {
        return (int) Auth::guard('workshop')->id();
    }

    private function resolveRange(Request $request): array
    {
        $from = $request->date('from')?->startOfDay() ?? now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    private function workshopRevenueExpression(string $table = 'workshop_service_orders'): string
    {
        return 'COALESCE(' . $table . '.payable_total, ' . $table . '.total_amount)';
    }
}
