@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">تفاصيل سجل العملية #{{ $auditLog->id }}</h1>
        <p class="text-muted mb-0">عرض تفصيلي للعملية المنفذة من الأدمن</p>
    </div>
    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline-secondary">رجوع</a>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="small text-muted">الأدمن</div>
                <div class="fw-semibold">{{ $auditLog->admin?->name ?? 'غير معروف' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">رقم الهاتف</div>
                <div class="fw-semibold">{{ $auditLog->admin?->phone ?? '-' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">الوقت</div>
                <div class="fw-semibold">{{ $auditLog->created_at?->format('Y-m-d H:i:s') }}</div>
            </div>

            <div class="col-md-4">
                <div class="small text-muted">الإجراء</div>
                <div><code>{{ $auditLog->action }}</code></div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">اسم المسار</div>
                <div><code>{{ $auditLog->route_name ?: '-' }}</code></div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">الطريقة</div>
                <div class="fw-semibold">{{ $auditLog->method ?: '-' }}</div>
            </div>

            <div class="col-md-6">
                <div class="small text-muted">المسار</div>
                <div><code>{{ $auditLog->path ?: '-' }}</code></div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">IP</div>
                <div class="fw-semibold">{{ $auditLog->ip_address ?: '-' }}</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">وكيل المستخدم</div>
                <div class="small">{{ $auditLog->user_agent ?: '-' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-3">بيانات العملية (Meta)</h2>
        <pre class="mb-0">{{ json_encode($auditLog->meta ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>
@endsection