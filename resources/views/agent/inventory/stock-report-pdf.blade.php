@extends('shared.pdf.layout-a4')

@section('pdf-template-scope', 'agent')
@section('pdf-template-type', 'reports')
@section('pdf-title', 'تقرير المخزون')
@section('pdf-business-name-ar', (string) ($businessName ?? ''))
@section('pdf-business-name-en', (string) ($businessName ?? ''))
@section('pdf-business-address-ar', (string) ($businessAddressAr ?? ''))
@section('pdf-business-address-en', (string) ($businessAddressEn ?? ''))
@section('pdf-business-phone', (string) ($businessPhone ?? ''))
@section('pdf-printed-by', (string) ($printedBy ?? ''))
@section('pdf-printed-at', (string) ($printedAt ?? now()->format('Y-m-d H:i')))

@section('pdf-body')
<table>
    <thead>
        <tr>
            <th>الموديل</th>
            <th>الصورة</th>
            <th>اسم الصنف</th>
            <th>الوحدة</th>
            <th>الكمية</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
        <tr>
            <td>{{ (string) ($row->product?->model ?? '-') }}</td>
            <td>
                @if (!empty($row->product?->image))
                <img src="{{ public_path('storage/' . ltrim((string) $row->product->image, '/')) }}" alt="صورة المنتج" style="width:50px;height:38px;object-fit:cover;border-radius:4px;">
                @else
                -
                @endif
            </td>
            <td>{{ (string) ($row->product?->name ?? '-') }}</td>
            <td>{{ (string) ($row->unit?->name ?? '-') }}</td>
            <td>{{ number_format((float) ($row->stock_quantity ?? 0), 3) }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="5">لا توجد بيانات مخزون لعرضها.</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection