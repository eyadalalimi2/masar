@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">تقرير الاستيراد</h1>
        <p class="text-muted mb-0">نتيجة التنفيذ النهائي لعملية الاستيراد.</p>
    </div>
    <a href="{{ route('admin.account-opening-excel.index') }}" class="btn btn-outline-secondary">عودة لصفحة الاستيراد</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-muted">إجمالي السجلات</div>
                <div class="h5 mb-0">{{ number_format((int) ($report['summary']['total_rows'] ?? 0)) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-muted">تم إنشاؤها</div>
                <div class="h5 mb-0 text-primary">{{ number_format((int) ($report['summary']['created_rows'] ?? 0)) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-muted">تم تحديثها</div>
                <div class="h5 mb-0 text-warning">{{ number_format((int) ($report['summary']['updated_rows'] ?? 0)) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-muted">فشلت</div>
                <div class="h5 mb-0 text-danger">{{ number_format((int) ($report['summary']['failed_rows'] ?? 0)) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">تفاصيل الصفوف</span>
        <span class="badge text-bg-dark">النوع: {{ (string) ($report['type'] ?? '-') }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>السطر</th>
                    <th>الاسم</th>
                    <th>الهاتف</th>
                    <th>الحالة</th>
                    <th>الرسالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($report['details'] ?? []) as $detail)
                <tr>
                    <td>{{ (int) ($detail['line'] ?? 0) }}</td>
                    <td>{{ (string) ($detail['name'] ?? '-') }}</td>
                    <td dir="ltr">{{ (string) ($detail['phone'] ?? '-') }}</td>
                    <td>
                        @if (($detail['status'] ?? '') === 'success')
                        <span class="badge text-bg-success">نجاح</span>
                        @else
                        <span class="badge text-bg-danger">فشل</span>
                        @endif
                    </td>
                    <td>{{ (string) ($detail['message'] ?? '-') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">لا توجد تفاصيل.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection