@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 fw-bold mb-0">إضافة حساب أدمن</h1>
    <a href="{{ route('admin.roles.admin-assignments') }}" class="btn btn-outline-dark">ربط الأدمنات بالأدوار</a>
</div>

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('admin.admins.store') }}" method="POST" class="card border-0 shadow-sm">
    @csrf
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">الاسم</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">رقم الهاتف</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">كلمة المرور</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">الحالة</label>
                <select name="status" class="form-select" required>
                    <option value="active" @selected(old('status', 'active' )==='active' )>مفعل</option>
                    <option value="inactive" @selected(old('status')==='inactive' )>معطل</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">الأدوار</label>
                <select name="role_ids[]" class="form-select" multiple>
                    @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected(collect(old('role_ids', []))->contains($role->id))>
                        {{ $role->name }} ({{ $role->slug }})
                    </option>
                    @endforeach
                </select>
                <small class="text-muted">يمكن تحديد أكثر من دور بالضغط على Ctrl أثناء الاختيار.</small>
            </div>
        </div>
    </div>

    <div class="card-footer bg-white d-flex gap-2">
        <button type="submit" class="btn btn-dark">حفظ</button>
        <a href="{{ route('admin.admins.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        <a href="{{ route('admin.roles.admin-assignments') }}" class="btn btn-outline-dark">صفحة الربط</a>
    </div>
</form>
@endsection