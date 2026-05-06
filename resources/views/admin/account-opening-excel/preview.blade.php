@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">معاينة استيراد فتح الحسابات</h1>
        <p class="text-muted mb-0">راجع النتائج قبل تنفيذ الاستيراد النهائي.</p>
    </div>
    <a href="{{ route('admin.account-opening-excel.index') }}" class="btn btn-outline-secondary">رجوع</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-muted">الإجمالي</div>
                <div class="h5 mb-0">{{ number_format((int) ($preview['summary']['total_rows'] ?? 0)) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-muted">صالحة</div>
                <div class="h5 mb-0 text-success">{{ number_format((int) ($preview['summary']['valid_rows'] ?? 0)) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-muted">غير صالحة</div>
                <div class="h5 mb-0 text-danger">{{ number_format((int) ($preview['summary']['invalid_rows'] ?? 0)) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-muted">سجلات إنشاء</div>
                <div class="h5 mb-0 text-primary">{{ number_format((int) ($preview['summary']['creatable_rows'] ?? 0)) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-muted">سجلات تحديث</div>
                <div class="h5 mb-0 text-warning">{{ number_format((int) ($preview['summary']['updatable_rows'] ?? 0)) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">تفاصيل المعاينة</span>
        <span class="badge text-bg-dark">النوع: {{ (string) ($preview['type'] ?? '-') }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>السطر</th>
                    <th>الاسم</th>
                    <th>رقم الهاتف</th>
                    <th>الحالة</th>
                    <th>الإجراء</th>
                    <th>الأخطاء</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($preview['rows'] ?? []) as $row)
                @php
                $errors = is_array($row['errors'] ?? null) ? $row['errors'] : [];
                $display = is_array($row['display'] ?? null) ? $row['display'] : [];
                $isValid = $errors === [];
                @endphp
                <tr>
                    <td>{{ (int) ($row['line'] ?? 0) }}</td>
                    <td>{{ (string) ($display['name'] ?? '-') }}</td>
                    <td dir="ltr">{{ (string) ($display['phone'] ?? '-') }}</td>
                    <td>
                        @if ($isValid)
                        <span class="badge text-bg-success">صالح</span>
                        @else
                        <span class="badge text-bg-danger">غير صالح</span>
                        @endif
                    </td>
                    <td>
                        @if (($row['action'] ?? '') === 'update')
                        <span class="badge text-bg-warning">تحديث</span>
                        @else
                        <span class="badge text-bg-primary">إنشاء</span>
                        @endif
                    </td>
                    <td>
                        @if ($errors === [])
                        <span class="text-muted">-</span>
                        @else
                        <ul class="mb-0 ps-3">
                            @foreach ($errors as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">لا توجد صفوف للمعاينة.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<form method="POST" action="{{ route('admin.account-opening-excel.import', ['token' => $token]) }}" onsubmit="return confirm('تأكيد تنفيذ الاستيراد وفق نتيجة المعاينة الحالية؟');">
    @csrf
    <button type="submit" class="btn btn-dark">تنفيذ الاستيراد</button>
</form>
@endsection