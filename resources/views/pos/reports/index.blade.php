@extends('pos.layout.app')

@section('title', 'تقارير المحل التجاري')

@section('content')
<div class="hero-box reveal rv1">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="h4 mb-1">تقارير الأداء</h1>
            <p class="mb-0 text-white-50">تحليل المبيعات والربحية لفترة زمنية محددة.</p>
        </div>
        <a href="{{ route('pos.sales.index') }}" class="btn btn-light btn-sm">المبيعات</a>
    </div>
</div>

<form method="GET" class="table-wrap reveal rv1 mb-3">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label mb-1">من تاريخ</label><input type="date" name="from"
                class="form-control" value="{{ $from->toDateString() }}"></div>
        <div class="col-md-3"><label class="form-label mb-1">إلى تاريخ</label><input type="date" name="to"
                class="form-control" value="{{ $to->toDateString() }}"></div>
        <div class="col-md-2"><button class="btn btn-dark w-100">تطبيق</button></div>
        <div class="col-md-2"><a class="btn btn-outline-success w-100"
                href="{{ route('pos.reports.export', ['format' => 'excel', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">تصدير
                Excel</a></div>
        <div class="col-md-2"><a class="btn btn-outline-danger w-100"
                href="{{ route('pos.reports.export', ['format' => 'pdf', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
                target="_blank">تصدير PDF</a></div>
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-3 reveal rv1">
        <div class="stat-card text-white">
            <div class="text-white small">إجمالي المبيعات</div>
            <div class="h5 mb-0 text-white">{{ number_format($stats['sales_total'], 2) }}</div>
        </div>
    </div>
    <div class="col-md-3 reveal rv1">
        <div class="stat-card text-white">
            <div class="text-white small">إجمالي الربح</div>
            <div class="h5 mb-0 text-white">{{ number_format($stats['profit_total'], 2) }}</div>
        </div>
    </div>
    <div class="col-md-3 reveal rv2">
        <div class="stat-card text-white">
            <div class="text-white small">عدد العمليات</div>
            <div class="h5 mb-0 text-white">{{ number_format($stats['sales_count']) }}</div>
        </div>
    </div>
    <div class="col-md-3 reveal rv2">
        <div class="stat-card text-white">
            <div class="text-white small">الكمية المباعة</div>
            <div class="h5 mb-0 text-white">{{ number_format($stats['quantity_total'], 3) }}</div>
        </div>
    </div>
</div>

<div class="table-wrap reveal rv2 mb-3">
    <div class="card-header bg-white fw-bold">أفضل المنتجات</div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>المنتج</th>
                    <th>الكمية</th>
                    <th>القيمة</th>
                    <th>الربح</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topProducts as $row)
                <tr>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ number_format((float) $row->total_qty, 3) }}</td>
                    <td>{{ number_format((float) $row->total_sales, 2) }}</td>
                    <td>{{ number_format((float) $row->total_profit, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">لا توجد بيانات ضمن الفترة المحددة.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="table-wrap reveal rv3">
    <div class="card-header bg-white fw-bold">تفصيل المبيعات</div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>التاريخ</th>
                    <th>المنتج</th>
                    <th>الكمية</th>
                    <th>القيمة</th>
                    <th>الربح</th>
                    <th>القناة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                <tr>
                    <td>{{ $sale->sold_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $sale->product_name }}</td>
                    <td>{{ number_format((float) $sale->quantity, 3) }}</td>
                    <td>{{ number_format((float) $sale->total_amount, 2) }}</td>
                    <td>{{ number_format((float) $sale->profit_amount, 2) }}</td>
                    <td>{{ $sale->sale_channel }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">لا توجد مبيعات ضمن الفترة المحددة.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection