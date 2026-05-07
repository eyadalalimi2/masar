@extends('shared.pdf.layout-a4')

@php
$type = (string) ($movement->movement_type ?? 'adjustment');
$titleByType = [
'in' => 'أمر توريد مخزني',
'out' => 'أمر صرف مخزني',
'adjustment' => 'محضر جرد/تسوية مخزنية',
];
$documentTitle = $titleByType[$type] ?? 'مستند حركة مخزون';
@endphp

@section('pdf-template-type', 'documents')
@section('pdf-template-key', 'agent-inventory-movement')
@section('pdf-title', $documentTitle)
@section('pdf-subtitle', 'Inventory Movement Document')
@section('pdf-business-name', (string) ($movement->supplier?->name ?? ''))
@section('pdf-business-address', (string) ($movement->supplier?->address ?? ''))
@section('pdf-business-phone', (string) ($movement->supplier?->phone ?? ''))
@section('pdf-printed-by', (string) ($movement->agent?->name ?? auth('agent')->user()->name ?? ''))
@section('pdf-printed-at', $printedAt)

@section('pdf-body')
<div class="meta-box">
    <table style="border:0; border-collapse:collapse; width:100%;">
        <tr>
            <td style="border:0; width:50%; padding:2px 0;"><strong>رقم المستند:</strong> #{{ $movement->id }}</td>
            <td style="border:0; width:50%; padding:2px 0;"><strong>نوع الحركة:</strong> {{ $documentTitle }}</td>
        </tr>
        <tr>
            <td style="border:0; padding:2px 0;"><strong>التاريخ:</strong> {{ $movement->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
            <td style="border:0; padding:2px 0;"><strong>الفرع:</strong> {{ $movement->branch?->name ?? '-' }}</td>
        </tr>
    </table>
</div>

<table>
    <thead>
        <tr>
            <th>الموديل</th>
            <th>المنتج</th>
            <th>الوحدة</th>
            <th>الكمية</th>
            <th>قبل</th>
            <th>بعد</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $movement->product?->model ?? '-' }}</td>
            <td>{{ $movement->product?->name ?? '-' }}</td>
            <td>{{ $movement->productUnit?->unit?->name ?? '-' }}</td>
            <td>{{ number_format((float) ($movement->quantity ?? 0), 3) }}</td>
            <td>{{ number_format((float) ($movement->stock_before ?? 0), 3) }}</td>
            <td>{{ number_format((float) ($movement->stock_after ?? 0), 3) }}</td>
        </tr>
    </tbody>
</table>

<div class="meta-box" style="margin-top:12px;">
    <strong>ملاحظة:</strong>
    <div style="margin-top:4px;">{{ $movement->note ?: '-' }}</div>
</div>
@endsection