@extends('admin.layout.app')

@section('content')
@include('admin.roles.partials.styles')

<div class="rbac-page">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h1 class="h4 fw-bold mb-1">مجموعات الصلاحيات</h1>
            <p class="text-muted mb-0">صفحة مخصصة لإدارة مفاتيح المجموعات، ترتيبها، وحالتها</p>
        </div>
    </div>

    @include('admin.roles.partials.nav')

    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="rbac-shell p-3 p-md-4 h-100">
                <div class="rbac-title">إضافة مجموعة جديدة</div>
                <div class="rbac-subtitle">أضف مجموعة واضحة لفرز الصلاحيات</div>

                <form method="POST" action="{{ route('admin.permission-groups.store') }}" class="row g-2">
                    @csrf
                    <div class="col-12">
                        <label class="form-label">المفتاح</label>
                        <input type="text" name="group_key" class="form-control" placeholder="customer_success" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">الاسم المعروض</label>
                        <input type="text" name="name" class="form-control" placeholder="نجاح العملاء" required>
                    </div>
                    <div class="col-8">
                        <label class="form-label">الترتيب</label>
                        <input type="number" name="display_order" class="form-control" min="0" max="9999" value="999">
                    </div>
                    <div class="col-4 d-flex align-items-end">
                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="create_group_active">
                            <label class="form-check-label" for="create_group_active">نشط</label>
                        </div>
                    </div>
                    <div class="col-12 d-grid">
                        <button type="submit" class="btn btn-outline-dark">إضافة مجموعة</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="rbac-shell p-3 p-md-4">
                <div class="rbac-title">المجموعات الحالية</div>
                <div class="rbac-subtitle">تعديل مباشر مع منع حذف المجموعة المرتبطة بصلاحيات</div>

                <div class="rbac-scroll" style="max-height: 560px;">
                    <div class="d-grid gap-2">
                        @forelse ($definedGroups as $group)
                        <div class="rbac-chip">
                            <form method="POST" action="{{ route('admin.permission-groups.update', $group) }}" class="row g-2">
                                @csrf
                                @method('PUT')

                                <div class="col-md-4">
                                    <input type="text" name="group_key" class="form-control form-control-sm" value="{{ $group->group_key }}" required>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="name" class="form-control form-control-sm" value="{{ $group->name }}" required>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" name="display_order" class="form-control form-control-sm" min="0" max="9999" value="{{ (int) $group->display_order }}">
                                </div>
                                <div class="col-md-2 d-flex align-items-center justify-content-end">
                                    <input type="hidden" name="is_active" value="0">
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="group_active_{{ $group->id }}" @checked($group->is_active)>
                                        <label class="form-check-label" for="group_active_{{ $group->id }}">نشط</label>
                                    </div>
                                </div>

                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <small class="{{ ((int) ($group->usage_count ?? 0)) > 0 ? 'text-warning' : 'text-muted' }}">
                                        عدد الصلاحيات المرتبطة: {{ (int) ($group->usage_count ?? 0) }}
                                    </small>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-sm btn-primary">حفظ</button>
                                        <button type="submit" form="delete-group-{{ $group->id }}" class="btn btn-sm btn-outline-danger"
                                            @disabled(((int) ($group->usage_count ?? 0)) > 0)
                                            title="{{ ((int) ($group->usage_count ?? 0)) > 0 ? 'لا يمكن حذف مجموعة مرتبطة بصلاحيات.' : 'حذف المجموعة' }}">
                                            حذف
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <form id="delete-group-{{ $group->id }}" method="POST" action="{{ route('admin.permission-groups.destroy', $group) }}" class="d-none" onsubmit="return confirm('هل أنت متأكد من حذف هذه المجموعة؟');">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                        @empty
                        <div class="text-center text-muted py-4">لا توجد مجموعات معرفة بعد.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection