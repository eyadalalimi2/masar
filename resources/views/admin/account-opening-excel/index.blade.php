@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">استيراد وتصدير فتح الحسابات</h1>
        <p class="text-muted mb-0">هذه الصفحة خاصة بلوحة الأدمن فقط لإدارة حسابات الوكلاء والمحلات التجارية وورش الصيانة عبر Excel.</p>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if ($errors->any())
<div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">رفع ملف المعاينة قبل الاستيراد</h2>
                <form method="POST" action="{{ route('admin.account-opening-excel.preview-upload') }}" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label class="form-label">نوع الحساب</label>
                        <select name="type" class="form-select" required>
                            <option value="supplier">وكلاء</option>
                            <option value="commercial_store">محلات تجارية</option>
                            <option value="workshop">ورش صيانة</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملف Excel</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <small class="text-muted">الصيغ المدعومة: xlsx, xls, csv. الحد الأقصى 10MB.</small>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="has_header" value="1" id="hasHeader" checked>
                            <label class="form-check-label" for="hasHeader">الملف يحتوي صف عناوين (Header)</label>
                        </div>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-dark">معاينة الملف</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">القوالب والتصدير</h2>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>النوع</th>
                                <th>تحميل القالب</th>
                                <th>تصدير البيانات الحالية</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>وكلاء</td>
                                <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.account-opening-excel.template', 'supplier') }}">تنزيل قالب</a></td>
                                <td><a class="btn btn-sm btn-outline-success" href="{{ route('admin.account-opening-excel.export', 'supplier') }}">تصدير</a></td>
                            </tr>
                            <tr>
                                <td>محلات تجارية</td>
                                <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.account-opening-excel.template', 'commercial_store') }}">تنزيل قالب</a></td>
                                <td><a class="btn btn-sm btn-outline-success" href="{{ route('admin.account-opening-excel.export', 'commercial_store') }}">تصدير</a></td>
                            </tr>
                            <tr>
                                <td>ورش صيانة</td>
                                <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.account-opening-excel.template', 'workshop') }}">تنزيل قالب</a></td>
                                <td><a class="btn btn-sm btn-outline-success" href="{{ route('admin.account-opening-excel.export', 'workshop') }}">تصدير</a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection