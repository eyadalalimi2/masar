@extends($layoutView)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">إعدادات الترويسة</h1>
        <p class="text-muted mb-0">إعدادات موحدة لجميع تقارير ومستندات هذه اللوحة: الاسم التجاري عربي/إنجليزي، العنوان عربي/إنجليزي، رقم الهاتف، اللوجو.</p>
    </div>
    <a href="{{ route($routeNamePreview) }}" target="_blank" class="btn btn-outline-dark btn-sm">معاينة PDF</a>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route($routeNameUpdate) }}" class="row g-3" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="col-md-6">
                <label class="form-label">الاسم التجاري (عربي)</label>
                <input type="text" name="business_name_ar" value="{{ old('business_name_ar', $settings['business_name_ar'] ?? ($settings['business_name'] ?? ($settings['platform_name'] ?? ''))) }}" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">الاسم التجاري (إنجليزي)</label>
                <input type="text" name="business_name_en" value="{{ old('business_name_en', $settings['business_name_en'] ?? ($settings['business_name_ar'] ?? ($settings['platform_name'] ?? ''))) }}" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">العنوان (عربي)</label>
                <input type="text" name="business_address_ar" value="{{ old('business_address_ar', $settings['business_address_ar'] ?? ($settings['business_address'] ?? ($settings['platform_address'] ?? ''))) }}" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">العنوان (إنجليزي)</label>
                <input type="text" name="business_address_en" value="{{ old('business_address_en', $settings['business_address_en'] ?? ($settings['business_address_ar'] ?? ($settings['platform_address'] ?? ''))) }}" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">رقم الهاتف</label>
                <input type="text" name="business_phone" value="{{ old('business_phone', $settings['business_phone'] ?? ($settings['platform_phone'] ?? '')) }}" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">رفع شعار جديد</label>
                <input type="file" name="logo_file" class="form-control" accept="image/*">
            </div>

            <div class="col-md-6">
                <label class="form-label d-block">معاينة الشعار الحالي</label>
                @if (!empty($settings['logo_public_path']))
                <img src="{{ asset($settings['logo_public_path']) }}" alt="Logo" style="max-height:60px; max-width:180px; border:1px solid #eee; padding:4px; border-radius:6px; background:#fff;">
                @else
                <div class="text-muted">لا يوجد شعار محدد</div>
                @endif
            </div>

            <div class="col-12 d-flex justify-content-end gap-2">
                <a href="{{ route($routeNamePreview) }}" target="_blank" class="btn btn-outline-secondary">معاينة</a>
                <button type="submit" class="btn btn-primary">حفظ إعدادات الترويسة</button>
            </div>
        </form>
    </div>
</div>
@endsection