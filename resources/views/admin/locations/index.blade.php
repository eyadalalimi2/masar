@extends('admin.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">إدارة المدن والمناطق</h1>
            <p class="text-muted mb-0">تعريف وتفعيل المواقع الجغرافية المتاحة داخل النظام</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">إضافة موقع جديد</h2>
            <form method="POST" action="{{ route('admin.locations.store') }}" class="row g-2">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">المدينة</label>
                    <input type="text" name="city" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">المنطقة</label>
                    <input type="text" name="zone" class="form-control" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                        <label class="form-check-label">مفعل</label>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-dark w-100">إضافة</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">قائمة المواقع</h2>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>المدينة</th>
                            <th>المنطقة</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($locations as $location)
                            <tr>
                                <td>{{ $location->city }}</td>
                                <td>{{ $location->zone }}</td>
                                <td>
                                    <span
                                        class="badge {{ $location->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $location->is_active ? 'مفعلة' : 'معطلة' }}
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#edit-location-{{ $location->id }}">تعديل</button>
                                    <form method="POST" action="{{ route('admin.locations.destroy', $location) }}"
                                        class="d-inline" onsubmit="return confirm('تأكيد حذف الموقع؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                                    </form>
                                </td>
                            </tr>
                            <tr class="collapse" id="edit-location-{{ $location->id }}">
                                <td colspan="4">
                                    <form method="POST" action="{{ route('admin.locations.update', $location) }}"
                                        class="row g-2">
                                        @csrf
                                        @method('PUT')
                                        <div class="col-md-4">
                                            <input type="text" name="city" class="form-control"
                                                value="{{ $location->city }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" name="zone" class="form-control"
                                                value="{{ $location->zone }}" required>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-center">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_active"
                                                    value="1" @checked($location->is_active)>
                                                <label class="form-check-label">مفعل</label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" class="btn btn-primary w-100">حفظ</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">لا توجد مواقع حتى الآن.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $locations->links() }}</div>
        </div>
    </div>
@endsection
