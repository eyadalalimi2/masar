<?php

namespace App\Http\Controllers\Branch;

use App\Exports\BranchReportExport;
use App\Http\Controllers\Controller;
use App\Models\Distribution\Branch;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class ReportController extends Controller
{
    public function index(): View
    {
        $branch = $this->currentBranch();

        return view('branch.reports.index', [
            'branch' => $branch,
            ...$this->buildReportPayload($branch),
        ]);
    }

    public function export(Request $request)
    {
        $branch = $this->currentBranch();

        $data = $request->validate([
            'format' => ['required', 'in:excel,pdf'],
        ]);

        $reportData = $this->buildReportPayload($branch);
        $exportedAt = now()->format('Y-m-d_H-i');
        $branchName = preg_replace('/[^A-Za-z0-9\-_]+/', '_', (string) ($branch->name ?? 'branch'));

        if ($data['format'] === 'excel') {
            return Excel::download(
                new BranchReportExport($reportData, (string) $branch->name),
                'branch_report_' . $branchName . '_' . $exportedAt . '.xlsx'
            );
        }

        $html = view('branch.reports.exports.pdf', [
            ...$reportData,
            'branchName' => (string) $branch->name,
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
                'Content-Disposition' => 'attachment; filename="branch_report_' . Str::slug($branchName) . '_' . $exportedAt . '.pdf"',
            ]
        );
    }

    private function buildReportPayload(Branch $branch): array
    {
        $dailySales = Order::query()
            ->where('branch_id', $branch->id)
            ->where('status', Order::STATUS_DELIVERED)
            ->whereDate('created_at', '>=', now()->subDays(13)->toDateString())
            ->selectRaw('DATE(created_at) as day, SUM(COALESCE(payable_total, total_price)) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $salesByProduct = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.branch_id', $branch->id)
            ->where('orders.status', Order::STATUS_DELIVERED)
            ->select('products.name', DB::raw('SUM(order_items.quantity) as sold_quantity'), DB::raw('SUM(order_items.total) as revenue'))
            ->groupBy('products.name')
            ->orderByDesc('sold_quantity')
            ->limit(10)
            ->get();

        $distributorPerformance = DB::table('orders')
            ->join('distributors', 'distributors.id', '=', 'orders.distributor_id')
            ->where('orders.branch_id', $branch->id)
            ->where('orders.status', Order::STATUS_DELIVERED)
            ->select('distributors.name', DB::raw('COUNT(orders.id) as delivered_orders'), DB::raw('SUM(COALESCE(orders.payable_total, orders.total_price)) as revenue'))
            ->groupBy('distributors.name')
            ->orderByDesc('delivered_orders')
            ->limit(10)
            ->get();

        $bestClients = Order::query()
            ->where('branch_id', $branch->id)
            ->where('status', Order::STATUS_DELIVERED)
            ->select('snapshot_customer_name as customer_name', 'snapshot_customer_phone as customer_phone', DB::raw('COUNT(id) as delivered_orders'), DB::raw('SUM(COALESCE(payable_total, total_price)) as total_value'))
            ->groupBy('snapshot_customer_name', 'snapshot_customer_phone')
            ->orderByDesc('total_value')
            ->limit(10)
            ->get();

        return compact('dailySales', 'salesByProduct', 'distributorPerformance', 'bestClients');
    }

    private function currentBranch(): Branch
    {
        $account = Auth::guard('branch')->user();

        if ($account && isset($account->branch_id) && (int) $account->branch_id > 0) {
            return Branch::query()->whereKey((int) $account->branch_id)->firstOrFail();
        }

        $phone = trim((string) ($account->phone ?? ''));
        if ($phone === '') {
            abort(403);
        }

        return Branch::query()->where('phone', $phone)->firstOrFail();
    }
}
