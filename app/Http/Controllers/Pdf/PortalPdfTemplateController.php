<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Services\Pdf\PdfTemplateService;
use Illuminate\Http\Request;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class PortalPdfTemplateController extends Controller
{
    public function __construct(private readonly PdfTemplateService $pdfTemplateService) {}

    public function index(Request $request, string $scope)
    {
        $scope = $this->normalizeScope($scope);
        $selectedType = PdfTemplateService::TYPE_DOCUMENTS;
        $templateKey = PdfTemplateService::DEFAULT_TEMPLATE_KEY;
        $settings = $this->pdfTemplateService->getTemplate($selectedType, $scope, $templateKey);

        return view('shared.pdf.template-settings', [
            'layoutView' => $scope . '.layout.app',
            'scope' => $scope,
            'selectedType' => $selectedType,
            'templateKey' => $templateKey,
            'settings' => $settings,
            'routeNameIndex' => $scope . '.settings.pdf-templates.index',
            'routeNameUpdate' => $scope . '.settings.pdf-templates.update',
            'routeNamePreview' => $scope . '.settings.pdf-templates.preview',
        ]);
    }

    public function update(Request $request, string $scope)
    {
        $scope = $this->normalizeScope($scope);
        $type = PdfTemplateService::TYPE_DOCUMENTS;

        $current = $this->pdfTemplateService->getTemplate($type, $scope, PdfTemplateService::DEFAULT_TEMPLATE_KEY);

        $data = $request->validate([
            'business_name_ar' => ['nullable', 'string', 'max:120'],
            'business_name_en' => ['nullable', 'string', 'max:120'],
            'business_address_ar' => ['nullable', 'string', 'max:255'],
            'business_address_en' => ['nullable', 'string', 'max:255'],
            'business_phone' => ['nullable', 'string', 'max:60'],
            'logo_file' => ['nullable', 'image', 'max:4096', 'dimensions:max_width=2400,max_height=2400'],
        ]);

        $templateKey = PdfTemplateService::DEFAULT_TEMPLATE_KEY;

        $saveData = array_merge($current, $data, [
            // Keep layout platform fields synchronized with business identity fields.
            'platform_name' => (string) ($data['business_name_ar'] ?? $current['platform_name'] ?? ''),
            'platform_address' => (string) ($data['business_address_ar'] ?? $current['platform_address'] ?? ''),
            'platform_phone' => (string) ($data['business_phone'] ?? $current['platform_phone'] ?? ''),
        ]);

        $savedDefault = $this->pdfTemplateService->updateTemplate(
            PdfTemplateService::TYPE_DOCUMENTS,
            $saveData,
            $request->file('logo_file'),
            $scope,
            $templateKey
        );

        foreach ($this->pdfTemplateService->types() as $templateType) {
            if ($templateType === PdfTemplateService::TYPE_DOCUMENTS) {
                continue;
            }

            $this->pdfTemplateService->updateTemplate(
                $templateType,
                [
                    'platform_name' => (string) ($savedDefault['platform_name'] ?? ''),
                    'platform_address' => (string) ($savedDefault['platform_address'] ?? ''),
                    'platform_phone' => (string) ($savedDefault['platform_phone'] ?? ''),
                    'business_name_ar' => (string) ($savedDefault['business_name_ar'] ?? ''),
                    'business_name_en' => (string) ($savedDefault['business_name_en'] ?? ''),
                    'business_address_ar' => (string) ($savedDefault['business_address_ar'] ?? ''),
                    'business_address_en' => (string) ($savedDefault['business_address_en'] ?? ''),
                    'business_phone' => (string) ($savedDefault['business_phone'] ?? ''),
                    'logo_public_path' => (string) ($savedDefault['logo_public_path'] ?? ''),
                ],
                null,
                $scope,
                $templateKey
            );
        }

        return back()->with('success', 'تم حفظ إعدادات الترويسة بنجاح.');
    }

    public function preview(Request $request, string $scope)
    {
        $scope = $this->normalizeScope($scope);

        $type = PdfTemplateService::TYPE_DOCUMENTS;

        $templateKey = PdfTemplateService::DEFAULT_TEMPLATE_KEY;
        $settings = $this->pdfTemplateService->getTemplate($type, $scope, $templateKey);

        $html = view('admin.pdf-templates.preview', [
            'printedAt' => now()->format('Y-m-d H:i'),
            'selectedType' => $type,
            'templateKey' => $templateKey,
            'selectedScope' => $scope,
            'settings' => $settings,
        ])->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 40,
            'margin_bottom' => 22,
            'margin_left' => 12,
            'margin_right' => 12,
            'tempDir' => storage_path('app/mpdf-temp'),
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        return response(
            $mpdf->Output('', Destination::STRING_RETURN),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="pdf_template_preview_' . $scope . '_' . $templateKey . '.pdf"',
            ]
        );
    }

    private function normalizeScope(string $scope): string
    {
        if (! in_array($scope, $this->pdfTemplateService->scopes(), true)) {
            return PdfTemplateService::SCOPE_ADMIN;
        }

        return $scope;
    }

    private function normalizeTemplateKey(string $templateKey): string
    {
        $templateKey = trim(strtolower($templateKey));
        if ($templateKey === '') {
            return PdfTemplateService::DEFAULT_TEMPLATE_KEY;
        }

        $templateKey = preg_replace('/[^a-z0-9\-_]/', '-', $templateKey);
        $templateKey = trim((string) $templateKey, '-_');

        return $templateKey !== '' ? $templateKey : PdfTemplateService::DEFAULT_TEMPLATE_KEY;
    }
}
