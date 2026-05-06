@extends('workshop.layout.app')

@section('content')
    <h1 class="workshop-section-title">تقارير الورشة</h1>
    <p class="workshop-section-subtitle">متابعة عدد الخدمات والإيرادات وأكثر الخدمات طلبًا واستهلاك المنتجات للفترة المحددة.
    </p>

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
                <div class="workshop-stat-label">الخدمات المكتملة</div>
                <div class="workshop-stat-value">{{ $servicesCount }}</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="workshop-stat">
                <div class="workshop-stat-label">صافي إيراد الفترة</div>
                <div class="workshop-stat-value">{{ number_format($revenue, 2) }} ر.ي</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="workshop-stat">
                <div class="workshop-stat-label">الأكثر طلبًا</div>
                <div class="workshop-stat-value">{{ $topServiceName }}</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="workshop-stat">
                <div class="workshop-stat-label">قيمة استهلاك المنتجات</div>
                <div class="workshop-stat-value">{{ number_format($productsConsumptionValue, 2) }} ر.ي</div>
            </div>
        </div>
    </div>

    <div class="workshop-panel">
        <h2 class="h6 fw-bold mb-3">ملاحظات تشغيلية</h2>
        <ul class="workshop-list">
            @foreach ($operationalNotes as $note)
                <li>{{ $note }}</li>
            @endforeach
        </ul>
    </div>
@endsection
