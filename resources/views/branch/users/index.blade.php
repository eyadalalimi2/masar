@extends('branch.layout.app')

@section('title', 'مستخدمو الفرع')

@section('content')
    <div class="container-fluid py-2">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h1 class="h4 fw-bold mb-0">مستخدمو الفرع - {{ $branch->name }}</h1>
            <a href="{{ route('branch.profile') }}" class="btn btn-outline-secondary">إعدادات الفرع</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->has('branch_users'))
            <div class="alert alert-danger">{{ $errors->first('branch_users') }}</div>
        @endif

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">إضافة مستخدم فرع</h2>
                <form method="POST" action="{{ route('branch.users.store') }}" class="row g-2">
                    @csrf
                    <div class="col-md-3">
                        <input type="text" name="name" class="form-control" placeholder="الاسم" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="phone" class="form-control" placeholder="رقم الهاتف" required>
                    </div>
                    <div class="col-md-2">
                        <input type="password" name="password" class="form-control" placeholder="كلمة المرور" required>
                    </div>
                    <div class="col-md-2">
                        <input type="password" name="password_confirmation" class="form-control"
                            placeholder="تأكيد كلمة المرور" required>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select" required>
                            <option value="active">نشط</option>
                            <option value="inactive">غير نشط</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-dark" type="submit">إضافة</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>الاسم</th>
                            <th>الهاتف</th>
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
                                <td>
                                    @if ($user->status === 'active')
                                        <span class="badge text-bg-success">نشط</span>
                                    @else
                                        <span class="badge text-bg-secondary">غير نشط</span>
                                    @endif
                                </td>
                                <td style="min-width: 320px;">
                                    <form method="POST" action="{{ route('branch.users.update', $user) }}" class="row g-2">
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
                                    <form method="POST" action="{{ route('branch.users.toggle', $user) }}" class="mb-2">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-primary" type="submit">تبديل الحالة</button>
                                    </form>

                                    <form method="POST" action="{{ route('branch.users.destroy', $user) }}"
                                        onsubmit="return confirm('تأكيد حذف المستخدم؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">لا يوجد مستخدمون لهذا الفرع.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $users->links() }}</div>
        </div>
    </div>
@endsection
