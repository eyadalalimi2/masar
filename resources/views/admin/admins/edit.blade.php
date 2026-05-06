@extends('admin.layout.app')

@section('content')
<h1 class="h4 fw-bold mb-4">تعديل حساب أدمن</h1>

@if (session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('admin.admins.update', $admin) }}" method="POST" class="card border-0 shadow-sm">
    @csrf
    @method('PUT')

    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">الاسم</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $admin->name) }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">رقم الهاتف</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $admin->phone) }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">كلمة المرور الجديدة (اختياري)</label>
                <input type="password" name="password" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">الحالة</label>
                <select name="status" class="form-select" required>
                    <option value="active" @selected(old('status', $admin->status) === 'active')>مفعل</option>
                    <option value="inactive" @selected(old('status', $admin->status) === 'inactive')>معطل</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">الأدوار</label>
                @php
                $selectedRoles = collect(old('role_ids', $admin->roles->pluck('id')->all()))->map(fn($id) => (int) $id);
                @endphp
                <select name="role_ids[]" class="form-select" multiple>
                    @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected($selectedRoles->contains((int) $role->id))>
                        {{ $role->name }} ({{ $role->slug }})
                    </option>
                    @endforeach
                </select>
                <small class="text-muted">يمكن تحديد أكثر من دور بالضغط على Ctrl أثناء الاختيار.</small>
            </div>
        </div>
    </div>

    <div class="card-footer bg-white d-flex gap-2">
        <button type="submit" class="btn btn-dark">تحديث</button>
        <a href="{{ route('admin.admins.index') }}" class="btn btn-outline-secondary">إلغاء</a>
    </div>
</form>
@endsection