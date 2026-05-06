@extends('agent.layout.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">إدارة مستخدمي الوكيل</h1>
        <p class="text-muted mb-0">إضافة وتعديل وتعطيل حسابات فريق الوكيل داخل نفس الشركة.</p>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->has('users'))
<div class="alert alert-danger">{{ $errors->first('users') }}</div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-3">إضافة مستخدم جديد</h2>
        <form method="POST" action="{{ route('agent.users.store') }}" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label class="form-label">الاسم</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">البريد الإلكتروني</label>
                <input type="email" name="email" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">الهاتف</label>
                <input type="text" name="phone" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">كلمة المرور</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">تأكيد كلمة المرور</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">الحالة</label>
                <select name="status" class="form-select" required>
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-dark">إضافة المستخدم</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-3">قائمة المستخدمين</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الاسم</th>
                        <th>الهاتف</th>
                        <th>البريد</th>
                        <th>الحالة</th>
                        <th>تحديث</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->phone }}</td>
                        <td>{{ $user->email ?: '-' }}</td>
                        <td>
                            @if ($user->status === 'active')
                            <span class="badge text-bg-success">نشط</span>
                            @else
                            <span class="badge text-bg-secondary">غير نشط</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('agent.users.update', $user) }}" class="row g-2">
                                @csrf
                                @method('PUT')
                                <div class="col-12">
                                    <input type="text" name="name" value="{{ $user->name }}"
                                        class="form-control form-control-sm" required>
                                </div>
                                <div class="col-12">
                                    <input type="text" name="phone" value="{{ $user->phone }}"
                                        class="form-control form-control-sm" required>
                                </div>
                                <div class="col-12">
                                    <input type="email" name="email" value="{{ $user->email }}"
                                        class="form-control form-control-sm" placeholder="البريد الإلكتروني">
                                </div>
                                <div class="col-12">
                                    <input type="password" name="password" class="form-control form-control-sm"
                                        placeholder="كلمة مرور جديدة (اختياري)">
                                </div>
                                <div class="col-12">
                                    <input type="password" name="password_confirmation"
                                        class="form-control form-control-sm" placeholder="تأكيد كلمة المرور">
                                </div>
                                <div class="col-12">
                                    <select name="status" class="form-select form-select-sm" required>
                                        <option value="active" @selected($user->status === 'active')>نشط</option>
                                        <option value="inactive" @selected($user->status === 'inactive')>غير نشط</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-sm btn-outline-dark" type="submit">تحديث</button>
                                </div>
                            </form>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('agent.users.toggle', $user) }}" class="mb-2">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-primary" type="submit">تبديل الحالة</button>
                            </form>

                            <form method="POST" action="{{ route('agent.users.destroy', $user) }}"
                                onsubmit="return confirm('تأكيد حذف المستخدم؟');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">لا يوجد مستخدمون بعد.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $users->links() }}</div>
    </div>
</div>
@endsection