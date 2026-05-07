@extends('agent.layout.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">إدارة المخزون</h1>
        <p class="text-muted mb-0">تم فصل المهام إلى صفحات مستقلة لتسهيل الإدارة والمتابعة.</p>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->has('inventory'))
<div class="alert alert-danger">{{ $errors->first('inventory') }}</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">عدد وحدات المنتجات</div>
                <div class="h4 fw-bold mb-0">{{ number_format($totals['units_count']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">إجمالي المخزون</div>
                <div class="h4 fw-bold mb-0">{{ number_format((float) $totals['total_stock'], 3) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">وحدات منخفضة المخزون</div>
                <div class="h4 fw-bold mb-0">{{ number_format($totals['low_stock_count']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <a href="{{ route('agent.inventory.stock-management') }}" class="card border-0 shadow-sm h-100 text-decoration-none text-dark">
            <div class="card-body d-flex flex-column gap-2">
                <div class="fw-bold">إضافة وتعديل كمية المخزون</div>
                <div class="small text-muted">إدارة الكميات وحدود التنبيه لكل وحدة منتج.</div>
                <div class="mt-auto"><span class="btn btn-dark btn-sm">فتح الصفحة</span></div>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-xl-3">
        <a href="{{ route('agent.inventory.distribution-page') }}" class="card border-0 shadow-sm h-100 text-decoration-none text-dark">
            <div class="card-body d-flex flex-column gap-2">
                <div class="fw-bold">صفحة التوزيع على الفروع</div>
                <div class="small text-muted">صرف الكميات للفروع مباشرة مع تسجيل الملاحظات.</div>
                <div class="mt-auto"><span class="btn btn-outline-primary btn-sm">فتح الصفحة</span></div>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-xl-3">
        <a href="{{ route('agent.replenishment.index') }}" class="card border-0 shadow-sm h-100 text-decoration-none text-dark">
            <div class="card-body d-flex flex-column gap-2">
                <div class="fw-bold">صفحة طلبات الفروع</div>
                <div class="small text-muted">اعتماد أو رفض أو تنفيذ طلبات التوريد الواردة.</div>
                <div class="mt-auto"><span class="btn btn-outline-dark btn-sm">فتح الصفحة</span></div>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-xl-3">
        <a href="{{ route('agent.inventory.movements') }}" class="card border-0 shadow-sm h-100 text-decoration-none text-dark">
            <div class="card-body d-flex flex-column gap-2">
                <div class="fw-bold">صفحة سجل حركة المخزون</div>
                <div class="small text-muted">عرض الحركات الأخيرة للإدخال والتوزيع والتعديل.</div>
                <div class="mt-auto"><span class="btn btn-outline-secondary btn-sm">فتح الصفحة</span></div>
            </div>
        </a>
    </div>
</div>
@endsection