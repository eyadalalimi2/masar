@extends('shared.pdf.layout-a4')

@section('pdf-template-type', 'reports')
@section('pdf-template-key', 'agent-performance-report')
@section('pdf-title', 'تقرير أداء الوكيل')
@section('pdf-subtitle', $segmentLabel ?? 'عام')
@section('pdf-business-name', $businessName)
@section('pdf-business-address', (string) ($businessAddress ?? ''))
@section('pdf-business-phone', (string) ($businessPhone ?? ''))
@section('pdf-printed-by', (string) ($printedBy ?? ''))
@section('pdf-printed-at', $exportedAt)

@section('pdf-body')
<div class="meta-box">
    <div><strong>الاسم التجاري:</strong> {{ $businessName }}</div>
    <div><strong>الفترة:</strong> {{ $filterSummary['from_date'] }} - {{ $filterSummary['to_date'] }}</div>
    <div><strong>الفرع:</strong> {{ $filterSummary['branch_id'] ? '#' . $filterSummary['branch_id'] : 'كل الفروع' }}</div>
</div>

<h3>المؤشرات العامة</h3>
<table>
    <tr>
        <th>إجمالي الطلبات</th>
        <th>إجمالي صافي الإيرادات</th>
        <th>عدد العملاء</th>
        <th>طلبات مكتملة</th>
    </tr>
    <tr>
        <td>{{ number_format((float) $cards['total_orders']) }}</td>
        <td>{{ number_format((float) $cards['total_revenue'], 2) }}</td>
        <td>{{ number_format((float) $cards['customers_count']) }}</td>
        <td>{{ number_format((float) $ordersStats['delivered']) }}</td>
    </tr>
</table>

<h3 style="margin-top:12px;">أفضل المنتجات</h3>
<table>
    <tr>
        <th>المنتج</th>
        <th>الكمية</th>
        <th>الإيراد</th>
    </tr>
    @forelse ($topProducts as $product)
    <tr>
        <td>{{ $product->name }}</td>
        <td>{{ number_format((float) $product->sold_quantity) }}</td>
        <td>{{ number_format((float) $product->revenue, 2) }}</td>
    </tr>
    @empty
    <tr>
        <td colspan="3">لا توجد بيانات</td>
    </tr>
    @endforelse
</table>

<h3 style="margin-top:12px;">أفضل المندوبين</h3>
<table>
    <tr>
        <th>المندوب</th>
        <th>الطلبات</th>
        <th>الإيراد</th>
    </tr>
    @forelse ($topDistributors as $distributor)
    <tr>
        <td>{{ $distributor->name }}</td>
        <td>{{ number_format((float) $distributor->orders_count) }}</td>
        <td>{{ number_format((float) $distributor->revenue, 2) }}</td>
    </tr>
    @empty
    <tr>
        <td colspan="3">لا توجد بيانات</td>
    </tr>
    @endforelse
</table>

<h3 style="margin-top:12px;">فرص التوسع (30 يوم)</h3>
<table>
    <tr>
        <th>المنطقة</th>
        <th>عدد الطلبات</th>
    </tr>
    @forelse ($coverageInsights['expansion_opportunities'] as $row)
    <tr>
        <td>{{ $row->customer_address }}</td>
        <td>{{ (int) $row->orders_count }}</td>
    </tr>
    @empty
    <tr>
        <td colspan="2">لا توجد بيانات</td>
    </tr>
    @endforelse
</table>
@endsection