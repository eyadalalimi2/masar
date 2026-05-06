<?php

namespace App\Http\Controllers\Pos;

use App\Exports\PosReportExport;
use App\Http\Controllers\Controller;
use App\Models\PosSale;
use App\Services\Pos\PosContextService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;

class ReportController extends Controller
{
    public function __construct(private readonly PosContextService $posContext) {}

    public function index(): View
    {
        $pos = $this->posContext->currentPos();
        $from = request()->query('from') ? Carbon::parse((string) request()->query('from'))->startOfDay() : now()->subDays(29)->startOfDay();
        $to = request()->query('to') ? Carbon::parse((string) request()->query('to'))->endOfDay() : now()->endOfDay();
        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $reportData = $this->buildReportPayload($pos->id, $from, $to);

        return view('pos.reports.index', [
            'pos' => $pos,
            'from' => $from,
            'to' => $to,
            ...$reportData,
        ]);
    }

    public function export(Request $request)
    {
        $pos = $this->posContext->currentPos();

        $data = $request->validate([
            'format' => ['required', 'in:excel,pdf'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = isset($data['from']) ? Carbon::parse((string) $data['from'])->startOfDay() : now()->subDays(29)->startOfDay();
        $to = isset($data['to']) ? Carbon::parse((string) $data['to'])->endOfDay() : now()->endOfDay();
        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $reportData = $this->buildReportPayload($pos->id, $from, $to);
        $exportedAt = now()->format('Y-m-d_H-i');

        if ($data['format'] === 'excel') {
            return Excel::download(
                new PosReportExport($reportData, (string) $pos->name, $from, $to),
                'pos_report_' . $pos->id . '_' . $exportedAt . '.xlsx'
            );
        }

        $html = view('pos.reports.pdf', [
            ...$reportData,
            'from' => $from,
            'to' => $to,
            'posName' => $this->sanitizeUtf8Text((string) $pos->name),
            'exportedAt' => now()->format('Y-m-d H:i'),
        ])->render();

        $html = $this->sanitizePdfHtml($html);

        $mpdf = $this->makePdfEngine();

        try {
            $mpdf->WriteHTML($html);
        } catch (MpdfException $exception) {
            $retryHtml = function_exists('mb_scrub') ? mb_scrub($html, 'UTF-8') : $html;
            $retryHtml = $this->sanitizePdfHtml($retryHtml);
            $mpdf = $this->makePdfEngine();
            $mpdf->WriteHTML($retryHtml);
        }

        return response(
            $mpdf->Output('', Destination::STRING_RETURN),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="pos_report_' . $pos->id . '_' . $exportedAt . '.pdf"',
            ]
        );
    }

    private function buildReportPayload(int $posId, Carbon $from, Carbon $to): array
    {
        $salesQuery = PosSale::query()
            ->where('pos_account_id', $posId)
            ->whereBetween('sold_at', [$from, $to]);

        $sales = (clone $salesQuery)
            ->latest('sold_at')
            ->get()
            ->map(function (PosSale $sale) {
                $sale->product_name = $this->sanitizeUtf8Text((string) $sale->product_name);
                $sale->sale_channel = $this->sanitizeUtf8Text((string) $sale->sale_channel);

                return $sale;
            });

        $topProducts = (clone $salesQuery)
            ->select('product_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total_amount) as total_sales'), DB::raw('SUM(profit_amount) as total_profit'))
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $row->product_name = $this->sanitizeUtf8Text((string) $row->product_name);

                return $row;
            });

        $stats = [
            'sales_total' => (float) (clone $salesQuery)->sum('total_amount'),
            'profit_total' => (float) (clone $salesQuery)->sum('profit_amount'),
            'sales_count' => (int) (clone $salesQuery)->count(),
            'quantity_total' => (float) (clone $salesQuery)->sum('quantity'),
        ];

        return compact('sales', 'topProducts', 'stats');
    }

    private function sanitizePdfHtml(string $html): string
    {
        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $html);
        if ($clean === false) {
            $clean = mb_convert_encoding($html, 'UTF-8', 'UTF-8');
        }

        if (function_exists('mb_scrub')) {
            $clean = mb_scrub($clean, 'UTF-8');
        }

        // Remove control characters that can break PDF HTML parsing.
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $clean) ?? $clean;

        if (function_exists('mb_check_encoding') && ! mb_check_encoding($clean, 'UTF-8')) {
            $clean = mb_convert_encoding($clean, 'UTF-8', 'UTF-8');
        }

        return $clean;
    }

    private function sanitizeUtf8Text(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        if (! mb_check_encoding($value, 'UTF-8')) {
            $detected = mb_detect_encoding($value, ['Windows-1256', 'ISO-8859-6', 'Windows-1252', 'ISO-8859-1'], true);

            if ($detected !== false) {
                $value = mb_convert_encoding($value, 'UTF-8', $detected);
            }
        }

        $value = @iconv('UTF-8', 'UTF-8//IGNORE', $value) ?: $value;
        if (function_exists('mb_scrub')) {
            $value = mb_scrub($value, 'UTF-8');
        }

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value) ?? $value;
    }

    private function makePdfEngine(): Mpdf
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => storage_path('app/mpdf-temp'),
        ]);

        $mpdf->SetDirectionality('rtl');

        return $mpdf;
    }
}
