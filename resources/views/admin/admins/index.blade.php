@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">إدارة الأدمن</h1>
        <p class="text-muted mb-0">عرض وإضافة وتعديل وحذف حسابات لوحة التحكم</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.roles.admin-assignments') }}" class="btn btn-outline-dark">ربط الأدمنات بالأدوار</a>
        <a href="{{ route('admin.admins.create') }}" class="btn btn-dark">إضافة أدمن</a>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<form method="GET" action="{{ route('admin.admins.index') }}" class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-8">
                <label class="form-label mb-1">بحث</label>
                <input type="text" name="search" class="form-control" placeholder="الاسم أو الهاتف"
                    value="{{ request('search') }}">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">الحالة</label>
                <select name="status" class="form-select">
                    <option value="">الكل</option>
                    <option value="active" @selected(request('status')==='active' )>مفعل</option>
                    <option value="inactive" @selected(request('status')==='inactive' )>معطل</option>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100">تطبيق</button>
                <a href="{{ route('admin.admins.index') }}" class="btn btn-outline-secondary w-100">إعادة</a>
            </div>
        </div>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>المعرف</th>
                <th>الاسم</th>
                <th>الهاتف</th>
                <th>الأدوار</th>
                <th>الحالة</th>
                <th>تاريخ الإضافة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($admins as $admin)
            <tr>
                <td>{{ $admin->id }}</td>
                <td>{{ $admin->name }}</td>
                <td dir="ltr">{{ $admin->phone }}</td>
                <td>
                    @if ($admin->roles->isNotEmpty())
                    <div class="d-flex flex-wrap gap-1">
                        @foreach ($admin->roles as $role)
                        <span class="badge text-bg-light border">{{ $role->name }}</span>
                        @endforeach
                    </div>
                    @else
                    <span class="text-muted small">بدون دور</span>
                    @endif
                </td>
                <td>
                    <span class="badge {{ $admin->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                        {{ $admin->status === 'active' ? 'مفعل' : 'معطل' }}
                    </span>
                </td>
                <td>{{ $admin->created_at?->format('Y-m-d H:i') }}</td>
                <td>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.admins.edit', $admin) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                        <form action="{{ route('admin.admins.destroy', $admin) }}" method="POST"
                            onsubmit="return confirm('هل أنت متأكد من حذف حساب الأدمن؟');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-4">لا توجد حسابات أدمن حتى الآن</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $admins->links() }}
@endsection