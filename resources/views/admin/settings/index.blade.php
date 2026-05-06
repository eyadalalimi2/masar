@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">إعدادات النظام</h1>
        <p class="text-muted mb-0">ضبط الإعدادات العامة، الأمان، والتوصيل من مكان واحد.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">الإعدادات العامة</h2>
                <form method="POST" action="{{ route('admin.settings.update', 'general') }}" class="row g-3">
                    @csrf
                    @method('PUT')
                    <div class="col-12">
                        <label class="form-label">اسم المنصة</label>
                        <input type="text" name="platform_name" class="form-control"
                            value="{{ old('platform_name', $settings['general']['platform_name']) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">العملة الافتراضية</label>
                        <input type="text" name="default_currency" class="form-control"
                            value="{{ old('default_currency', $settings['general']['default_currency']) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">اللغة الافتراضية</label>
                        <input type="text" name="default_language" class="form-control"
                            value="{{ old('default_language', $settings['general']['default_language']) }}" required>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">حفظ الإعدادات العامة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">إعدادات الأمان</h2>
                <form method="POST" action="{{ route('admin.settings.update', 'security') }}" class="row g-3">
                    @csrf
                    @method('PUT')
                    <div class="col-md-6">
                        <label class="form-label">الحد الأدنى لطول كلمة المرور</label>
                        <input type="number" min="6" max="32" name="password_min_length"
                            class="form-control"
                            value="{{ old('password_min_length', $settings['security']['password_min_length']) }}"
                            required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">مهلة انتهاء الجلسة (دقيقة)</label>
                        <input type="number" min="5" max="1440" name="session_timeout_minutes"
                            class="form-control"
                            value="{{ old('session_timeout_minutes', $settings['security']['session_timeout_minutes']) }}"
                            required>
                    </div>
                    <div class="col-12">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" value="1" id="mixedCase"
                                name="password_require_mixed_case" @checked(old('password_require_mixed_case', $settings['security']['password_require_mixed_case']))>
                            <label class="form-check-label" for="mixedCase">فرض أحرف كبيرة وصغيرة في كلمة المرور</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" value="1" id="requireNumbers"
                                name="password_require_numbers" @checked(old('password_require_numbers', $settings['security']['password_require_numbers']))>
                            <label class="form-check-label" for="requireNumbers">فرض أرقام في كلمة المرور</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" value="1" id="requireSymbols"
                                name="password_require_symbols" @checked(old('password_require_symbols', $settings['security']['password_require_symbols']))>
                            <label class="form-check-label" for="requireSymbols">فرض رموز خاصة في كلمة المرور</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" value="1" id="enable2fa"
                                name="enable_2fa" @checked(old('enable_2fa', $settings['security']['enable_2fa']))>
                            <label class="form-check-label" for="enable2fa">تفعيل التحقق الثنائي (2FA) على مستوى
                                السياسات</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">حفظ إعدادات الأمان</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">إعدادات التوصيل</h2>
                <form method="POST" action="{{ route('admin.settings.update', 'delivery') }}" class="row g-3">
                    @csrf
                    @method('PUT')
                    <div class="col-12">
                        <label class="form-label">نطاق الخدمة (كم)</label>
                        <input type="number" step="0.1" min="1" max="500" name="service_radius_km"
                            class="form-control"
                            value="{{ old('service_radius_km', $settings['delivery']['service_radius_km']) }}"
                            required>
                    </div>
                    <div class="col-12">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" value="1" id="manualReassign"
                                name="allow_manual_reassign" @checked(old('allow_manual_reassign', $settings['delivery']['allow_manual_reassign']))>
                            <label class="form-check-label" for="manualReassign">السماح بإعادة تعيين المندوب
                                يدويًا</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" value="1" id="autoAssign"
                                name="auto_assign_distributor" @checked(old('auto_assign_distributor', $settings['delivery']['auto_assign_distributor']))>
                            <label class="form-check-label" for="autoAssign">تفعيل التعيين التلقائي للمندوبين</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">حفظ إعدادات التوصيل</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection