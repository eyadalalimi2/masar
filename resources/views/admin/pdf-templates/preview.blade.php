@extends('shared.pdf.layout-a4')

@php
$typeLabels = [
'documents' => 'المستندات',
'reports' => 'التقارير',
'invoices' => 'الفواتير',
];
$typeLabel = $typeLabels[$selectedType] ?? $selectedType;
@endphp

@section('pdf-template-scope', (string) ($selectedScope ?? app(\App\Services\Pdf\PdfTemplateService::class)->resolveCurrentScope()))
@section('pdf-template-type', $selectedType)
@section('pdf-template-key', (string) ($templateKey ?? \App\Services\Pdf\PdfTemplateService::DEFAULT_TEMPLATE_KEY))
@section('pdf-title', (string) ($settings['document_title'] ?? 'معاينة قالب PDF'))
@section('pdf-subtitle', 'Preview')
@section('pdf-printed-by', (string) (auth('admin')->user()->name ?? auth()->user()->name ?? 'مدير النظام'))
@section('pdf-printed-at', $printedAt)

@section('pdf-body')
<div class="meta-box">
    <table style="border:0; border-collapse:collapse; width:100%;">
        <tr>
            <td style="border:0; width:50%; padding:2px 0;"><strong>نوع القالب:</strong> {{ $typeLabel }}</td>
            <td style="border:0; width:50%; padding:2px 0;"><strong>حالة المستند:</strong> تجريبي</td>
        </tr>
        <tr>
            <td style="border:0; padding:2px 0;"><strong>المرجع:</strong> PREVIEW-001</td>
            <td style="border:0; padding:2px 0;"><strong>التاريخ:</strong> {{ $printedAt }}</td>
        </tr>
    </table>
</div>

<h3>جدول معاينة</h3>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>العنصر</th>
            <th>القيمة</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>1</td>
            <td>نموذج صف</td>
            <td>123.450</td>
        </tr>
        <tr>
            <td>2</td>
            <td>نموذج صف</td>
            <td>987.000</td>
        </tr>
    </tbody>
</table>

<div class="small" style="margin-top:10px;">هذه صفحة معاينة لإعدادات ترويسة وتذييل PDF.</div>
@endsection