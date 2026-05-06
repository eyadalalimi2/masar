<div class="col-md-4">
    <label class="form-label">الاسم</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $consumer->name ?? '') }}" required>
</div>
<div class="col-md-4">
    <label class="form-label">الهاتف</label>
    <input type="text" name="phone" class="form-control" value="{{ old('phone', $consumer->phone ?? '') }}" required>
</div>
<div class="col-md-4">
    <label class="form-label">واتساب</label>
    <input type="text" name="whatsapp" class="form-control" value="{{ old('whatsapp', $consumer->whatsapp ?? '') }}">
</div>
<div class="col-md-4">
    <label class="form-label">كلمة المرور {{ isset($consumer) ? '(اتركها فارغة بدون تغيير)' : '' }}</label>
    <input type="password" name="password" class="form-control" {{ isset($consumer) ? '' : 'required' }}>
</div>
<div class="col-md-4">
    <label class="form-label">تأكيد كلمة المرور</label>
    <input type="password" name="password_confirmation" class="form-control" {{ isset($consumer) ? '' : 'required' }}>
</div>
<div class="col-md-4">
    <label class="form-label">الحالة</label>
    <select name="status" class="form-select" required>
        <option value="active" @selected(old('status', $consumer->status ?? 'active') === 'active')>نشط</option>
        <option value="inactive" @selected(old('status', $consumer->status ?? 'active') === 'inactive')>غير نشط</option>
    </select>
</div>
<div class="col-md-6">
    <label class="form-label">الموقع الجغرافي (GPS)</label>
    <input type="text" name="gps_location" class="form-control"
        value="{{ old('gps_location', $consumer->gps_location ?? '') }}">
</div>
<div class="col-12">
    <label class="form-label">العنوان</label>
    <textarea name="address" rows="3" class="form-control">{{ old('address', $consumer->address ?? '') }}</textarea>
</div>
