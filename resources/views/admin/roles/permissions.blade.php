@extends('admin.layout.app')

@section('content')
@include('admin.roles.partials.styles')

<div class="rbac-page">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h1 class="h4 fw-bold mb-1">صلاحيات الأدوار</h1>
            <p class="text-muted mb-0">صفحة مخصصة لتعديل صلاحيات كل دور بشكل مستقل</p>
        </div>
    </div>

    @include('admin.roles.partials.nav')

    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="rbac-shell p-3 p-md-4">
        <div class="row g-3">
            @forelse ($roles as $role)
            <div class="col-12 col-xl-6">
                <div class="rbac-role-card">
                    <button class="rbac-role-head" type="button" data-bs-toggle="collapse" data-bs-target="#role-permissions-{{ $role->id }}" aria-expanded="false" aria-controls="role-permissions-{{ $role->id }}">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <div class="fw-semibold">{{ $role->name }}</div>
                                <div class="small text-muted">{{ $role->slug }}</div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge text-bg-light">الصلاحيات: {{ $role->permissions->count() }}</span>
                                <span class="badge text-bg-secondary">مستوى {{ (int) $role->hierarchy_level }}</span>
                            </div>
                        </div>
                    </button>

                    <div id="role-permissions-{{ $role->id }}" class="collapse">
                        <div class="rbac-role-body">
                            <form method="POST" action="{{ route('admin.roles.update-permissions', $role) }}" class="row g-3">
                                @csrf
                                @method('PUT')

                                <div class="col-12">
                                    <div class="rbac-scroll" style="max-height: 430px;">
                                        <div class="d-grid gap-2">
                                            @foreach ($permissionGroups as $group)
                                            <div class="rbac-chip">
                                                <div class="fw-semibold small mb-2">{{ $group['label'] }}</div>
                                                <div class="row g-2">
                                                    @foreach ($group['permissions'] as $permission)
                                                    <div class="col-12 col-sm-6">
                                                        <label class="form-check">
                                                            <input class="form-check-input me-2" type="checkbox" name="permissions[]" value="{{ $permission->slug }}" @checked($role->permissions->contains('slug', $permission->slug))>
                                                            <span class="form-check-label small">{{ $permission->name }}</span>
                                                        </label>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary px-4">حفظ صلاحيات الدور</button>
                                </div>
                            </form>
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