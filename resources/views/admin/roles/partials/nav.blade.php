@php
$current = request()->route()?->getName();
@endphp

<div class="rbac-nav d-flex flex-wrap gap-2 mb-4">
    <a href="{{ route('admin.roles.index') }}" class="btn {{ $current === 'admin.roles.index' ? 'btn-dark' : 'btn-outline-dark' }}">إدارة الأدوار</a>
    <a href="{{ route('admin.roles.permissions') }}" class="btn {{ $current === 'admin.roles.permissions' ? 'btn-dark' : 'btn-outline-dark' }}">صلاحيات الأدوار</a>
    <a href="{{ route('admin.roles.admin-assignments') }}" class="btn {{ $current === 'admin.roles.admin-assignments' ? 'btn-dark' : 'btn-outline-dark' }}">ربط الأدمنات بالأدوار</a>
    <a href="{{ route('admin.permission-groups.index') }}" class="btn {{ str_starts_with((string) $current, 'admin.permission-groups') ? 'btn-dark' : 'btn-outline-dark' }}">مجموعات الصلاحيات</a>
</div>