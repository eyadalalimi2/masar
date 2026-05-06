<?php

namespace App\Http\Controllers\Reports\Agent;

use App\Exports\AgentReportExport;
use App\Http\Controllers\Controller;
use App\Models\Distribution\Branch;
use App\Models\Notifications\WebAlert;
use App\Models\Orders\Order;
use App\Services\Reports\ReportService;
use App\Services\Notifications\WebAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class AgentReportController extends Controller
{
    private const TYPE_RETAIL_STORE = 'retail_store';
    private const TYPE_WORKSHOP = 'workshop';

    public function __construct(
        private readonly ReportService $reportService,
        private readonly WebAlertService $webAlertService,
    ) {}

    public function commercialStoresIndex(Request $request): View
    {
        return $this->renderReportPage($request, self::TYPE_RETAIL_STORE, 'المحلات التجارية', 'agent.reports.commercial-stores.index', 'agent.reports.commercial-stores.export');
    }

    public function workshopsIndex(Request $request): View
    {
        return $this->renderReportPage($request, self::TYPE_WORKSHOP, 'الورش', 'agent.reports.workshops.index', 'agent.reports.workshops.export');
    }

    public function coverage(): View
    {
        $supplierId = Auth::guard('agent')->user()->supplier->id;
        $coverageInsights = $this->reportService->getCoverageInsights($supplierId);

        return view('agent.coverage.index', compact('coverageInsights'));
    }

    public function generateLowDemandAlerts(): \Illuminate\Http\RedirectResponse
    {
        $supplierId = (int) Auth::guard('agent')->user()->supplier->id;
        $agentId = (int) Auth::guard('agent')->id();

        $lowDemandProducts = $this->reportService->getLowDemandProducts($supplierId);
        $generated = 0;

        foreach ($lowDemandProducts->take(5) as $product) {
            $title = 'انخفاض الطلب على منتج';
            $body = 'المنتج ' . $product->product_name . ' مبيعاته منخفضة خلال آخر 30 يوم.';

            $existsToday = WebAlert::query()
                ->where('recipient_type', 'agent')
                ->where('recipient_id', $agentId)
                ->where('title', $title)
                ->where('body', $body)
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            if ($existsToday) {
                continue;
            }

            $this->webAlertService->create('agent', $agentId, $title, $body, [
                'type' => 'low_demand_product',
                'product_unit_id' => (int) $product->product_unit_id,
                'sold_quantity_30d' => (float) $product->sold_quantity_30d,
            ]);
            $generated++;
        }

        return back()->with('success', 'تم إنشاء ' . $generated . ' تنبيه لانخفاض الطلب.');
    }

    public function advancedForecast(Request $request): JsonResponse
    {
        $supplierId = (int) Auth::guard('agent')->user()->supplier->id;
        $filters = $this->extractFilters($request, $supplierId, self::TYPE_RETAIL_STORE);
        $baseline = $this->reportService->getDemandForecast($supplierId, $filters);

        $weekly = DB::table('orders')
            ->where('supplier_id', $supplierId)
            ->where('status', Order::STATUS_DELIVERED)
            ->where('created_at', '>=', now()->subWeeks(12))
            ->selectRaw('YEARWEEK(created_at, 1) as yw, COUNT(id) as orders_count, COALESCE(SUM(COALESCE(payable_total,total_price)),0) as revenue_total')
            ->groupBy('yw')
            ->orderBy('yw')
            ->get();

        $volatility = 0.0;
        if ($weekly->count() > 1) {
            $diffs = [];
            for ($i = 1; $i < $weekly->count(); $i++) {
                $diffs[] = abs((int) $weekly[$i]->orders_count - (int) $weekly[$i - 1]->orders_count);
            }
            $volatility = count($diffs) > 0 ? round(array_sum($diffs) / count($diffs), 2) : 0.0;
        }

        $confidence = max(0, min(100, (int) round(100 - ($volatility * 3))));
        $riskLevel = $confidence >= 75 ? 'low' : ($confidence >= 50 ? 'medium' : 'high');

        return response()->json([
            'success' => true,
            'supplier_id' => $supplierId,
            'forecast' => $baseline,
            'weekly_trend' => $weekly,
            'confidence_percent' => $confidence,
            'risk_level' => $riskLevel,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function commercialStoresExport(Request $request)
    {
        return $this->exportByType($request, self::TYPE_RETAIL_STORE, 'المحلات التجارية');
    }

    public function workshopsExport(Request $request)
    {
        return $this->exportByType($request, self::TYPE_WORKSHOP, 'الورش');
    }

    private function exportByType(Request $request, string $businessType, string $segmentLabel)
    {
        $supplier = Auth::user()->supplier;
        $supplierId = (int) $supplier->id;

        $data = $request->validate([
            'format' => ['required', 'in:excel,pdf'],
        ]);

        $filters = $this->extractFilters($request, $supplierId, $businessType);

        $reportData = $this->buildReportPayload($supplierId, $filters);
        $exportedAt = now()->format('Y-m-d_H-i');
        $businessName = preg_replace('/[^A-Za-z0-9\-_]+/', '_', (string) ($supplier->business_name ?? 'agent'));
        $filterSummary = $this->buildFilterSummary($filters);

        if ($data['format'] === 'excel') {
            return Excel::download(
                new AgentReportExport($reportData, (string) $supplier->business_name, $filterSummary),
                'agent_report_' . $businessName . '_' . $exportedAt . '.xlsx'
            );
        }

        $html = view('agent.reports.exports.pdf', [
            ...$reportData,
            'businessName' => (string) $supplier->business_name,
            'filterSummary' => $filterSummary,
            'segmentLabel' => $segmentLabel,
            'exportedAt' => now()->format('Y-m-d H:i'),
        ])->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => storage_path('app/mpdf-temp'),
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        return response(
            $mpdf->Output('', Destination::STRING_RETURN),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="agent_report_' . Str::slug((string) $businessName) . '_' . $exportedAt . '.pdf"',
            ]
        );
    }

    private function renderReportPage(Request $request, string $businessType, string $segmentLabel, string $indexRoute, string $exportRoute): View
    {
        $supplierId = (int) Auth::user()->supplier->id;
        $filters = $this->extractFilters($request, $supplierId, $businessType);
        $branches = Branch::query()
            ->where('supplier_id', $supplierId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('agent.reports.index', [
            ...$this->buildReportPayload($supplierId, $filters),
            'filters' => $filters,
            'branches' => $branches,
            'segmentLabel' => $segmentLabel,
            'indexRoute' => $indexRoute,
            'exportRoute' => $exportRoute,
        ]);
    }

    private function buildReportPayload(int $supplierId, array $filters): array
    {
        return [
            'cards' => $this->reportService->getDashboardCards($supplierId, $filters),
            'salesSummary' => $this->reportService->getSalesSummary($supplierId, $filters),
            'ordersStats' => $this->reportService->getOrdersStats($supplierId, $filters),
            'topProducts' => $this->reportService->getTopProducts($supplierId, 8, $filters),
            'topDistributors' => $this->reportService->getTopDistributors($supplierId, 8, $filters),
            'revenueReport' => $this->reportService->getRevenueReport($supplierId, $filters),
            'debtReport' => $this->reportService->getCustomerDebtReport($supplierId, $filters),
            'coverageInsights' => $this->reportService->getCoverageInsights($supplierId, $filters),
            'demandForecast' => $this->reportService->getDemandForecast($supplierId, $filters),
            'branchComparison' => $this->reportService->getBranchPerformanceComparison($supplierId, $filters),
            'uncoveredAreas' => $this->reportService->getUncoveredAreas($supplierId, $filters),
            'lowDemandProducts' => $this->reportService->getLowDemandProducts($supplierId, $filters),
        ];
    }

    private function extractFilters(Request $request, int $supplierId, string $businessType): array
    {
        $validated = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $branchId = isset($validated['branch_id']) ? (int) $validated['branch_id'] : null;
        if ($branchId !== null && $branchId > 0) {
            $branchExists = Branch::query()
                ->where('supplier_id', $supplierId)
                ->whereKey($branchId)
                ->exists();

            if (! $branchExists) {
                abort(422, 'الفرع المحدد لا يتبع الوكيل الحالي.');
            }
        }

        return [
            'from_date' => $validated['from_date'] ?? null,
            'to_date' => $validated['to_date'] ?? null,
            'branch_id' => ($branchId !== null && $branchId > 0) ? $branchId : null,
            'customer_business_type' => $businessType,
        ];
    }

    private function buildFilterSummary(array $filters): array
    {
        return [
            'from_date' => $filters['from_date'] ?: 'بداية البيانات',
            'to_date' => $filters['to_date'] ?: 'حتى اليوم',
            'branch_id' => $filters['branch_id'],
        ];
    }
}
