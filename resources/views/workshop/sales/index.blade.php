@extends('workshop.layout.app')

@section('content')
    <h1 class="workshop-section-title">المبيعات والفواتير</h1>
    <p class="workshop-section-subtitle">تحليل المبيعات للفترة المحددة ومراجعة آخر الفواتير المكتملة.</p>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <label class="form-label small text-muted">من</label>
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label small text-muted">إلى</label>
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">تحديث التقرير</button>
        </div>
    </form>

    <div class="row g-3 mb-3">
        <div class="col-md-6 col-lg-3">
            <div class="workshop-stat">
                <div class="workshop-stat-label">صافي مبيعات الفترة</div>
                <div class="workshop-stat-value">{{ number_format($revenue, 2) }} ر.ي</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="workshop-stat">
                <div class="workshop-stat-label">عدد الفواتير</div>
                <div class="workshop-stat-value">{{ $invoicesCount }}</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="workshop-stat">
                <div class="workshop-stat-label">حصة الخدمات</div>
                <div class="workshop-stat-value">{{ $serviceShare }}%</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="workshop-stat">
                <div class="workshop-stat-label">حصة المنتجات</div>
                <div class="workshop-stat-value">{{ $productShare }}%</div>
            </div>
        </div>
    </div>

    <div class="workshop-panel">
        <h2 class="h6 fw-bold mb-3">آخر الفواتير</h2>
        @if ($latestInvoices->isEmpty())
            <p class="mb-0 text-muted">لا توجد فواتير مكتملة حاليًا.</p>
        @else
            <ul class="workshop-list">
                @foreach ($latestInvoices as $invoice)
                    <li class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <span>{{ $invoice->order_number }} - {{ $invoice->customer_name }} -
                            {{ number_format((float) $invoice->total_amount, 2) }} ر.ي</span>
                        <a href="{{ route('workshop.sales.invoice', $invoice) }}" target="_blank"
                            class="btn btn-sm btn-outline-dark">عرض/طباعة الفاتورة</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
