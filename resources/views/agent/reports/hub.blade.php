@extends('agent.layout.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">شاشة التقارير</h1>
        <p class="text-muted mb-0">اختر نوع التقرير الذي تريد عرضه أو طباعته.</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column gap-2">
                <div class="fw-bold">تقرير المخزون</div>
                <div class="small text-muted">طباعة تقرير المخزون بالأعمدة: الموديل، الصورة، اسم الصنف، الوحدة، الكمية.</div>
                <div class="mt-auto">
                    <a href="{{ route('agent.inventory.report.pdf') }}" target="_blank" class="btn btn-dark btn-sm">طباعة تقرير المخزون</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column gap-2">
                <div class="fw-bold">تقارير المحلات التجارية</div>
                <div class="small text-muted">تحليل المبيعات والطلبات والإيرادات الخاصة بالمحلات التجارية.</div>
                <div class="mt-auto">
                    <a href="{{ route('agent.reports.commercial-stores.index') }}" class="btn btn-outline-dark btn-sm">فتح التقرير</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column gap-2">
                <div class="fw-bold">تقارير الورش</div>
                <div class="small text-muted">عرض أداء الورش من الطلبات والمؤشرات الأساسية.</div>
                <div class="mt-auto">
                    <a href="{{ route('agent.reports.workshops.index') }}" class="btn btn-outline-dark btn-sm">فتح التقرير</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection