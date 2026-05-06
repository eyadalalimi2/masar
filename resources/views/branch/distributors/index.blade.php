@extends('branch.layout.app')

@section('title', 'مندوبو الفرع')

@section('content')
    <div class="container-fluid py-2">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h1 class="h4 fw-bold mb-0">إدارة المندوبين</h1>
            <form method="GET" class="d-flex gap-2" action="{{ route('branch.distributors.index') }}">
                <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="بحث">
                <button class="btn btn-outline-secondary">بحث</button>
            </form>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">إضافة مندوب جديد</h2>
                <form method="POST" action="{{ route('branch.distributors.store') }}" enctype="multipart/form-data"
                    class="row g-2">
                    @csrf
                    <div class="col-md-3"><input class="form-control" name="name" placeholder="اسم المندوب" required>
                    </div>
                    <div class="col-md-2"><input class="form-control" name="phone" placeholder="رقم الهاتف" required>
                    </div>
                    <div class="col-md-2"><input class="form-control" type="password" name="password"
                            placeholder="كلمة المرور" required></div>
                    <div class="col-md-2"><input class="form-control" name="vehicle_type" placeholder="نوع المركبة"></div>
                    <div class="col-md-2"><input class="form-control" name="distribution_points" placeholder="منطقة العمل">
                    </div>
                    <div class="col-md-1"><select class="form-select" name="status">
                            <option value="active">نشط</option>
                            <option value="inactive">معطل</option>
                        </select></div>
                    <div class="col-md-3"><input class="form-control" type="file" name="image" accept="image/*"></div>
                    <div class="col-md-2"><button class="btn btn-dark w-100">إضافة</button></div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>المندوب</th>
                            <th>الهاتف</th>
                            <th>المنطقة</th>
                            <th>المركبة</th>
                            <th>الحالة</th>
                            <th>إدارة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($distributors as $d)
                            <tr>
                                <td>{{ $d->name }}</td>
                                <td>{{ $d->phone }}</td>
                                <td>{{ $d->distribution_points ?: '-' }}</td>
                                <td>{{ $d->vehicle_type ?: '-' }}</td>
                                <td>{{ $d->status }}</td>
                                <td class="d-flex gap-2">
                                    <form method="POST" action="{{ route('branch.distributors.toggle', $d) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-primary">تفعيل/تعطيل</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">لا يوجد مندوبون حتى الآن.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">{{ $distributors->links() }}</div>
        </div>
    </div>
@endsection
