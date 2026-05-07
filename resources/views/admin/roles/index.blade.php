@extends('admin.layout.app')

@section('content')
@include('admin.roles.partials.styles')

<div class="rbac-page">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h1 class="h4 fw-bold mb-1">إدارة الأدوار</h1>
            <p class="text-muted mb-0">صفحة مخصصة لإنشاء الأدوار وتعديل البيانات الأساسية للتسلسل الهرمي</p>
        </div>
    </div>

    @include('admin.roles.partials.nav')

    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-5">
            <div class="rbac-shell p-3 p-md-4 h-100">
                <div class="rbac-title">إضافة دور جديد</div>
                <div class="rbac-subtitle">أنشئ دورًا ثم انتقل لصفحات الصلاحيات والربط لإكمال الإعداد</div>

                <form method="POST" action="{{ route('admin.roles.store') }}" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label class="form-label">اسم الدور</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">المعرف (slug)</label>
                        <input type="text" name="slug" class="form-control" placeholder="operations-manager" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">الدور الأب</label>
                        <select name="parent_role_id" class="form-select">
                            <option value="">بدون</option>
                            @foreach ($availableParents as $parentRole)
                            <option value="{{ $parentRole->id }}">{{ $parentRole->name }} ({{ $parentRole->slug }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-grid">
                        <button type="submit" class="btn btn-dark">إنشاء الدور</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="rbac-shell p-3 p-md-4 h-100">
                <div class="rbac-title">ملاحظة تنظيمية</div>
                <div class="rbac-subtitle mb-2">تم تقسيم النظام إلى صفحات متخصصة لتسهيل الإدارة</div>
                <ul class="mb-0 text-muted">
                    <li>صفحة صلاحيات الأدوار: تعديل قائمة الصلاحيات لكل دور.</li>
                    <li>صفحة ربط الأدمنات بالأدوار: إسناد الحسابات الإدارية.</li>
                    <li>صفحة مجموعات الصلاحيات: إدارة مفاتيح وتصنيف الصلاحيات.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="rbac-shell p-3 p-md-4">
        <div class="rbac-title">الأدوار الحالية</div>
        <div class="rbac-subtitle">تعديل بيانات الدور الأساسية فقط (الاسم، الأب، المستوى)</div>

        <div class="row g-3">
            @forelse ($roles as $role)
            <div class="col-12 col-xl-6">
                <div class="rbac-role-card">
                    <button class="rbac-role-head" type="button" data-bs-toggle="collapse" data-bs-target="#role-profile-{{ $role->id }}" aria-expanded="false" aria-controls="role-profile-{{ $role->id }}">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <div class="fw-semibold">{{ $role->name }}</div>
                                <div class="small text-muted">{{ $role->slug }}</div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge text-bg-light">الصلاحيات: {{ $role->permissions->count() }}</span>
                                <span class="badge text-bg-light">الأدمنات: {{ $role->admins->count() }}</span>
                                <span class="badge text-bg-secondary">مستوى {{ (int) $role->hierarchy_level }}</span>
                            </div>
                        </div>
                    </button>

                    <div id="role-profile-{{ $role->id }}" class="collapse">
                        <div class="rbac-role-body">
                            <form method="POST" action="{{ route('admin.roles.update-profile', $role) }}" class="row g-3">
                                @csrf
                                @method('PUT')

                                <div class="col-md-6">
                                    <label class="form-label">اسم الدور</label>
                                    <input type="text" name="name" class="form-control" value="{{ $role->name }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">المعرف</label>
                                    <input type="text" class="form-control" value="{{ $role->slug }}" disabled>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">الدور الأب</label>
                                    <select name="parent_role_id" class="form-select" @disabled($role->slug === 'super-admin')>
                                        <option value="">بدون</option>
                                        @foreach ($availableParents as $parentRole)
                                        <option value="{{ $parentRole->id }}" @selected((int) $role->parent_role_id === (int) $parentRole->id)
                                            @disabled((int) $role->id === (int) $parentRole->id)>
                                            {{ $parentRole->name }} ({{ $parentRole->slug }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">المستوى</label>
                                    <input type="text" class="form-control" value="{{ (int) $role->hierarchy_level }}" disabled>
                                </div>

                                <div class="col-12 d-flex justify-content-between align-items-center gap-2">
                                    @if ($role->slug !== 'super-admin')
                                    <button type="submit" form="delete-role-{{ $role->id }}" class="btn btn-outline-danger">حذف الدور</button>
                                    @else
                                    <span class="small text-muted">دور المدير العام محمي من الحذف</span>
                                    @endif

                                    <button type="submit" class="btn btn-primary px-4">حفظ بيانات الدور</button>
                                </div>
                            </form>

                            @if ($role->slug !== 'super-admin')
                            <form id="delete-role-{{ $role->id }}" method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="d-none" onsubmit="return confirm('هل أنت متأكد من حذف هذا الدور؟');">
                                @csrf
                                @method('DELETE')
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center text-muted py-4">لا توجد أدوار بعد.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection