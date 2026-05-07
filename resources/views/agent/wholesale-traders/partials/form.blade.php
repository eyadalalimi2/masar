<input type="hidden" name="type" value="wholesale_trader">

<div class="col-md-4">
    <label class="form-label">اسم تاجر الجملة</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name ?? '') }}" required>
</div>
<div class="col-md-4">
    <label class="form-label">الهاتف</label>
    <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone ?? '') }}"
        required>
</div>
<div class="col-md-4">
    <label class="form-label">كلمة المرور {{ isset($customer) ? '(اتركها فارغة بدون تغيير)' : '' }}</label>
    <input type="password" name="password" class="form-control" {{ isset($customer) ? '' : 'required' }}>
</div>
<div class="col-md-4">
    <label class="form-label">تأكيد كلمة المرور</label>
    <input type="password" name="password_confirmation" class="form-control" {{ isset($customer) ? '' : 'required' }}>
</div>
<div class="col-md-4">
    <label class="form-label">واتساب</label>
    <input type="text" name="whatsapp" class="form-control" value="{{ old('whatsapp', $customer->whatsapp ?? '') }}">
</div>
<div class="col-md-4">
    <label class="form-label">اسم المالك</label>
    <input type="text" name="owner_name" class="form-control"
        value="{{ old('owner_name', $customer->owner_name ?? '') }}">
</div>
<div class="col-md-4">
    <label class="form-label">رقم البطاقة الشخصية</label>
    <input type="text" name="national_id_number" class="form-control"
        value="{{ old('national_id_number', $customer->national_id_number ?? '') }}">
</div>
<div class="col-md-4">
    <label class="form-label">رقم السجل التجاري</label>
    <input type="text" name="commercial_reg_number" class="form-control"
        value="{{ old('commercial_reg_number', $customer->commercial_reg_number ?? '') }}">
</div>
<div class="col-md-4">
    <label class="form-label">رقم الرخصة</label>
    <input type="text" name="license_number" class="form-control"
        value="{{ old('license_number', $customer->license_number ?? '') }}">
</div>
<div class="col-md-4">
    <label class="form-label">الحالة</label>
    <select name="status" class="form-select" required>
        <option value="active" @selected(old('status', $customer->status ?? 'active') === 'active')>نشط</option>
        <option value="inactive" @selected(old('status', $customer->status ?? 'active') === 'inactive')>غير نشط</option>
    </select>
</div>
<div class="col-md-6">
    <label class="form-label">الموقع الجغرافي (GPS)</label>
    <input type="text" name="gps_location" class="form-control"
        value="{{ old('gps_location', $customer->gps_location ?? '') }}">
</div>
<div class="col-12">
    <label class="form-label">العنوان</label>
    <textarea name="address" rows="3" class="form-control" required>{{ old('address', $customer->address ?? '') }}</textarea>
</div>
<div class="col-md-4">
    <label class="form-label">صورة البطاقة الشخصية</label>
    <input type="file" name="national_id_image" class="form-control" accept="image/*">
    @if (!empty($customer?->national_id_image_url))
    <a href="{{ $customer->national_id_image_url }}" target="_blank" rel="noopener noreferrer"
        class="d-inline-block mt-2 small">عرض الصورة الحالية</a>
    @endif
</div>
<div class="col-md-4">
    <label class="form-label">صورة السجل التجاري</label>
    <input type="file" name="commercial_reg_image" class="form-control" accept="image/*">
    @if (!empty($customer?->commercial_reg_image_url))
    <a href="{{ $customer->commercial_reg_image_url }}" target="_blank" rel="noopener noreferrer"
        class="d-inline-block mt-2 small">عرض الصورة الحالية</a>
    @endif
</div>
<div class="col-md-4">
    <label class="form-label">صورة الرخصة</label>
    <input type="file" name="license_image" class="form-control" accept="image/*">
    @if (!empty($customer?->license_image_url))
    <a href="{{ $customer->license_image_url }}" target="_blank" rel="noopener noreferrer"
        class="d-inline-block mt-2 small">عرض الصورة الحالية</a>
    @endif
</div>