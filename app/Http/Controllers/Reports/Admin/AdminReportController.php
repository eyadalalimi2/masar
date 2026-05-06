<?php

namespace App\Http\Controllers\Reports\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reports\ReportService;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function index(): View
    {
        $cards = $this->reportService->getDashboardCards();
        $salesSummary = $this->reportService->getSalesSummary();
        $ordersStats = $this->reportService->getOrdersStats();
        $topProducts = $this->reportService->getTopProducts();
        $topDistributors = $this->reportService->getTopDistributors();
        $revenueReport = $this->reportService->getRevenueReport();
        $debtReport = $this->reportService->getCustomerDebtReport();

        return view('admin.reports.index', compact(
            'cards',
            'salesSummary',
            'ordersStats',
            'topProducts',
            'topDistributors',
            'revenueReport',
            'debtReport'
        ));
    }
}






