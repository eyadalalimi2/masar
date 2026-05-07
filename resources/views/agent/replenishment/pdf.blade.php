@extends('shared.pdf.layout-a4')

@section('pdf-template-type', 'documents')
@section('pdf-template-key', 'agent-replenishment-request')
@section('pdf-title', 'مستند طلب توريد مخزني')
@section('pdf-subtitle', 'Branch Replenishment Request')
@section('pdf-business-name', (string) ($replenishment->supplier?->name ?? ''))
@section('pdf-business-address', (string) ($replenishment->supplier?->address ?? ''))
@section('pdf-business-phone', (string) ($replenishment->supplier?->phone ?? ''))
@section('pdf-printed-by', (string) (auth('agent')->user()->name ?? ''))
@section('pdf-printed-at', $printedAt)

@section('pdf-body')
@php
$status = (string) ($replenishment->status ?? 'pending');
$statusText = $statusLabels[$status] ?? 'غير محدد';
$requestedAt = $replenishment->requested_at?->format('Y-m-d H:i') ?? '-';
$resolvedAt = $replenishment->resolved_at?->format('Y-m-d H:i') ?? '-';
@endphp

<div class="meta-box">
    <table style="border:0; border-collapse:collapse; width:100%;">
        <tr>
            <td style="border:0; width:50%; padding:2px 0;"><strong>رقم المستند:</strong> #{{ $replenishment->id }}</td>
            <td style="border:0; width:50%; padding:2px 0;"><strong>الحالة:</strong> {{ $statusText }}</td>
        </tr>
        <tr>
            <td style="border:0; padding:2px 0;"><strong>تاريخ الطلب:</strong> {{ $requestedAt }}</td>
            <td style="border:0; padding:2px 0;"><strong>تاريخ المعالجة:</strong> {{ $resolvedAt }}</td>
        </tr>
        <tr>
            <td style="border:0; padding:2px 0;"><strong>الفرع:</strong> {{ $replenishment->branch?->name ?? '-' }}</td>
            <td style="border:0; padding:2px 0;"><strong>هاتف الفرع:</strong> {{ $replenishment->branch?->phone ?? '-' }}</td>
        </tr>
    </table>
</div>

<h3>تفاصيل الصنف المطلوب</h3>
<table>
    <thead>
        <tr>
            <th>الموديل</th>
            <th>المنتج</th>
            <th>الوحدة</th>
            <th>الكمية المطلوبة</th>
            <th>المتاح حاليًا</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $replenishment->product?->model ?? '-' }}</td>
            <td>{{ $replenishment->product?->name ?? '-' }}</td>
            <td>{{ $replenishment->productUnit?->unit?->name ?? '-' }}</td>
            <td>{{ number_format((float) ($replenishment->requested_quantity ?? 0), 3) }}</td>
            <td>{{ number_format((float) ($replenishment->productUnit?->stock_quantity ?? 0), 3) }}</td>
        </tr>
    </tbody>
</table>

<div class="meta-box" style="margin-top:12px;">
    <strong>ملاحظة:</strong>
    <div style="margin-top:4px;">{{ $replenishment->note ?: '-' }}</div>
</div>

<div class="small">هذا المستند مولد آليًا من النظام ويمكن طباعته على مقاس A4.</div>
@endsection