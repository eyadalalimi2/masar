<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Exports\Admin\AccountOpeningTemplateExport;
use App\Http\Controllers\Controller;
use App\Services\Admin\AccountOpeningExcelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class AdminAccountOpeningExcelController extends Controller
{
    public function __construct(private readonly AccountOpeningExcelService $service) {}

    public function index(): View
    {
        return view('admin.account-opening-excel.index');
    }

    public function downloadTemplate(string $type)
    {
        $this->assertType($type);

        $filename = 'account_opening_template_' . $type . '.xlsx';

        return Excel::download(new AccountOpeningTemplateExport($type), $filename);
    }

    public function export(string $type)
    {
        $this->assertType($type);

        $filename = 'account_opening_export_' . $type . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download($this->service->buildExportForType($type), $filename);
    }

    public function previewUpload(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:supplier,commercial_store,workshop'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'has_header' => ['nullable', 'boolean'],
        ]);

        $preview = $this->service->preview(
            $request->file('file'),
            (string) $data['type'],
            filter_var($request->input('has_header', true), FILTER_VALIDATE_BOOLEAN)
        );

        $token = 'account-open-preview:' . (int) $request->user('admin')->id . ':' . Str::uuid();
        Cache::put($token, $preview, now()->addHours(2));

        return redirect()->route('admin.account-opening-excel.preview', ['token' => $token]);
    }

    public function preview(string $token): View
    {
        $preview = $this->getScopedPayload($token, 'account-open-preview:');

        if (! is_array($preview)) {
            abort(404);
        }

        return view('admin.account-opening-excel.preview', [
            'token' => $token,
            'preview' => $preview,
        ]);
    }

    public function import(string $token): RedirectResponse
    {
        $preview = $this->getScopedPayload($token, 'account-open-preview:');

        if (! is_array($preview)) {
            return redirect()->route('admin.account-opening-excel.index')->with('error', 'جلسة المعاينة منتهية. ارفع الملف مرة أخرى.');
        }

        $report = $this->service->importFromPreview($preview);
        $reportToken = 'account-open-report:' . (int) auth('admin')->id() . ':' . Str::uuid();
        Cache::put($reportToken, $report, now()->addHours(6));

        return redirect()->route('admin.account-opening-excel.report', ['token' => $reportToken]);
    }

    public function report(string $token): View
    {
        $report = $this->getScopedPayload($token, 'account-open-report:');

        if (! is_array($report)) {
            abort(404);
        }

        return view('admin.account-opening-excel.report', [
            'report' => $report,
        ]);
    }

    private function assertType(string $type): void
    {
        if (! in_array($type, ['supplier', 'commercial_store', 'workshop'], true)) {
            abort(404);
        }
    }

    private function getScopedPayload(string $token, string $prefix): ?array
    {
        $adminId = (int) Auth::guard('admin')->id();
        $expectedPrefix = $prefix . $adminId . ':';

        if (! str_starts_with($token, $expectedPrefix)) {
            return null;
        }

        $payload = Cache::get($token);

        return is_array($payload) ? $payload : null;
    }
}
