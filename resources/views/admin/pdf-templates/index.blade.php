@extends('admin.layout.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">إدارة قوالب PDF</h1>
        <p class="text-muted mb-0">التحكم في القوالب حسب النوع (مستندات / تقارير / فواتير) مع دعم رفع الشعار.</p>
    </div>
    <a href="{{ route('admin.settings.pdf-templates.preview', ['type' => $selectedType]) }}" target="_blank" class="btn btn-outline-dark btn-sm">معاينة PDF</a>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        @php
        $typeLabels = [
        'documents' => 'المستندات',
        'reports' => 'التقارير',
        'invoices' => 'الفواتير',
        ];
        @endphp

        <ul class="nav nav-pills mb-3">
            @foreach ($types as $type)
            <li class="nav-item">
                <a class="nav-link {{ $selectedType === $type ? 'active' : '' }}" href="{{ route('admin.settings.pdf-templates.index', ['type' => $type]) }}">{{ $typeLabels[$type] ?? $type }}</a>
            </li>
            @endforeach
        </ul>

        <form method="POST" action="{{ route('admin.settings.pdf-templates.update') }}" class="row g-3" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="type" value="{{ $selectedType }}">

            <div class="col-md-6">
                <label class="form-label">اسم المنصة</label>
                <input type="text" name="platform_name" value="{{ old('platform_name', $settings['platform_name'] ?? '') }}" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">عنوان المنصة</label>
                <input type="text" name="platform_address" value="{{ old('platform_address', $settings['platform_address'] ?? '') }}" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">هاتف المنصة</label>
                <input type="text" name="platform_phone" value="{{ old('platform_phone', $settings['platform_phone'] ?? '') }}" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">اسم المستند الافتراضي</label>
                <input type="text" name="document_title" value="{{ old('document_title', $settings['document_title'] ?? '') }}" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">الاسم التجاري (عربي)</label>
                <input type="text" name="business_name_ar" value="{{ old('business_name_ar', $settings['business_name_ar'] ?? ($settings['business_name'] ?? '')) }}" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">الاسم التجاري (إنجليزي)</label>
                <input type="text" name="business_name_en" value="{{ old('business_name_en', $settings['business_name_en'] ?? '') }}" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">العنوان (عربي)</label>
                <input type="text" name="business_address_ar" value="{{ old('business_address_ar', $settings['business_address_ar'] ?? ($settings['business_address'] ?? '')) }}" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">العنوان (إنجليزي)</label>
                <input type="text" name="business_address_en" value="{{ old('business_address_en', $settings['business_address_en'] ?? '') }}" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">رقم الهاتف</label>
                <input type="text" name="business_phone" value="{{ old('business_phone', $settings['business_phone'] ?? '') }}" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">المسار العام للشعار</label>
                <input type="text" name="logo_public_path" value="{{ old('logo_public_path', $settings['logo_public_path'] ?? '') }}" class="form-control">
                <div class="form-text">اختياري. مثال: assets/images/logo.png</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">رفع شعار جديد</label>
                <input type="file" name="logo_file" class="form-control" accept="image/*">
                <div class="form-text">عند الرفع سيتم استخدام الشعار الجديد مباشرة لهذا النوع.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label d-block">معاينة الشعار الحالي</label>
                @if (!empty($settings['logo_public_path']))
                <img src="{{ asset($settings['logo_public_path']) }}" alt="Logo" style="max-height:60px; max-width:180px; border:1px solid #eee; padding:4px; border-radius:6px; background:#fff;">
                @else
                <div class="text-muted">لا يوجد شعار محدد</div>
                @endif
            </div>

            <div class="col-md-6">
                <label class="form-label">سطر إضافي في الترويسة</label>
                <input type="text" name="header_subtitle" value="{{ old('header_subtitle', $settings['header_subtitle'] ?? '') }}" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">ملاحظة التذييل</label>
                <input type="text" name="footer_note" value="{{ old('footer_note', $settings['footer_note'] ?? '') }}" class="form-control">
            </div>

            <div class="col-12 d-flex justify-content-end gap-2">
                <a href="{{ route('admin.settings.pdf-templates.preview', ['type' => $selectedType]) }}" target="_blank" class="btn btn-outline-secondary">معاينة</a>
                <button type="submit" class="btn btn-primary">حفظ القالب</button>
            </div>
        </form>
    </div>
</div>
@endsection