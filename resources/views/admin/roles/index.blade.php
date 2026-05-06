@extends('admin.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">الأدوار والصلاحيات</h1>
            <p class="text-muted mb-0">إدارة أدوار الأدمن وربط الصلاحيات بها</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">إضافة دور جديد</h2>
            <form method="POST" action="{{ route('admin.roles.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">اسم الدور</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">المعرف (slug)</label>
                    <input type="text" name="slug" class="form-control" placeholder="operations-manager" required>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-dark w-100">إنشاء الدور</button>
                </div>

                <div class="col-12">
                    <label class="form-label">صلاحيات الدور</label>
                    <div class="row g-2">
                        @foreach ($permissions as $permission)
                            <div class="col-md-3 col-sm-6">
                                <label class="form-check border rounded p-2 d-block">
                                    <input class="form-check-input me-2" type="checkbox" name="permissions[]"
                                        value="{{ $permission->slug }}">
                                    <span class="form-check-label">{{ $permission->name }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">ربط الأدمنات بالدور</label>
                    <div class="row g-2">
                        @foreach ($admins as $admin)
                            <div class="col-md-3 col-sm-6">
                                <label class="form-check border rounded p-2 d-block">
                                    <input class="form-check-input me-2" type="checkbox" name="admin_ids[]"
                                        value="{{ $admin->id }}">
                                    <span class="form-check-label">{{ $admin->name }} ({{ $admin->phone }})</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">الأدوار الحالية</h2>
            <div class="row g-3">
                @forelse ($roles as $role)
                    <div class="col-12">
                        <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="border rounded p-3">
                            @csrf
                            @method('PUT')

                            <div class="row g-2 align-items-end mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">اسم الدور</label>
                                    <input type="text" name="name" class="form-control" value="{{ $role->name }}"
                                        required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">المعرف</label>
                                    <input type="text" class="form-control" value="{{ $role->slug }}" disabled>
                                </div>
                                <div class="col-md-4 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary w-100">حفظ التعديلات</button>

                                    @if ($role->slug !== 'super-admin')
                                        <button type="submit" form="delete-role-{{ $role->id }}"
                                            class="btn btn-outline-danger w-100">حذف</button>
                                    @endif
                                </div>
                            </div>

                            <div class="mb-2 small text-muted">صلاحيات الدور</div>
                            <div class="row g-2 mb-3">
                                @foreach ($permissions as $permission)
                                    <div class="col-md-3 col-sm-6">
                                        <label class="form-check border rounded p-2 d-block">
                                            <input class="form-check-input me-2" type="checkbox" name="permissions[]"
                                                value="{{ $permission->slug }}" @checked($role->permissions->contains('slug', $permission->slug))>
                                            <span class="form-check-label">{{ $permission->name }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mb-2 small text-muted">ربط الأدمنات بالدور</div>
                            <div class="row g-2">
                                @foreach ($admins as $admin)
                                    <div class="col-md-3 col-sm-6">
                                        <label class="form-check border rounded p-2 d-block">
                                            <input class="form-check-input me-2" type="checkbox" name="admin_ids[]"
                                                value="{{ $admin->id }}" @checked($role->admins->contains('id', $admin->id))>
                                            <span class="form-check-label">{{ $admin->name }}
                                                ({{ $admin->phone }})</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </form>

                        @if ($role->slug !== 'super-admin')
                            <form id="delete-role-{{ $role->id }}" method="POST"
                                action="{{ route('admin.roles.destroy', $role) }}"
                                onsubmit="return confirm('هل أنت متأكد من حذف هذا الدور؟');" class="d-none">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="col-12 text-center text-muted py-4">لا توجد أدوار بعد.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
