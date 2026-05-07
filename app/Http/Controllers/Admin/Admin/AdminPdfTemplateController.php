<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Http\Controllers\Controller;
use App\Services\Pdf\PdfTemplateService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class AdminPdfTemplateController extends Controller
{
    public function __construct(private readonly PdfTemplateService $pdfTemplateService) {}

    public function index(Request $request)
    {
        $types = $this->pdfTemplateService->types();
        $selectedType = (string) $request->query('type', PdfTemplateService::TYPE_DOCUMENTS);
        if (! in_array($selectedType, $types, true)) {
            $selectedType = PdfTemplateService::TYPE_DOCUMENTS;
        }

        $templates = $this->pdfTemplateService->getTemplates();
        $settings = $templates[$selectedType] ?? [];

        return view('admin.pdf-templates.index', compact('settings', 'selectedType', 'types'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'string', Rule::in($this->pdfTemplateService->types())],
            'platform_name' => ['required', 'string', 'max:120'],
            'platform_address' => ['nullable', 'string', 'max:255'],
            'platform_phone' => ['nullable', 'string', 'max:60'],
            'document_title' => ['nullable', 'string', 'max:120'],
            'business_name_ar' => ['nullable', 'string', 'max:120'],
            'business_name_en' => ['nullable', 'string', 'max:120'],
            'business_address_ar' => ['nullable', 'string', 'max:255'],
            'business_address_en' => ['nullable', 'string', 'max:255'],
            'business_phone' => ['nullable', 'string', 'max:60'],
            'header_subtitle' => ['nullable', 'string', 'max:200'],
            'footer_note' => ['nullable', 'string', 'max:200'],
            'logo_public_path' => ['nullable', 'string', 'max:255'],
            'logo_file' => ['nullable', 'image', 'max:4096', 'dimensions:max_width=2400,max_height=2400'],
        ]);

        $this->pdfTemplateService->updateTemplate(
            (string) $data['type'],
            $data,
            $request->file('logo_file')
        );

        return back()->with('success', 'تم تحديث قالب PDF بنجاح.');
    }

    public function preview(Request $request)
    {
        $type = (string) $request->query('type', PdfTemplateService::TYPE_DOCUMENTS);
        if (! in_array($type, $this->pdfTemplateService->types(), true)) {
            $type = PdfTemplateService::TYPE_DOCUMENTS;
        }

        $settings = $this->pdfTemplateService->getTemplate($type);

        $html = view('admin.pdf-templates.preview', [
            'printedAt' => now()->format('Y-m-d H:i'),
            'selectedType' => $type,
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
                'Content-Disposition' => 'inline; filename="pdf_template_preview.pdf"',
            ]
        );
    }
}
