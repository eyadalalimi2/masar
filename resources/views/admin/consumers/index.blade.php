@extends('admin.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">إدارة المستهلكين</h1>
        <a href="{{ route('admin.consumers.create') }}" class="btn btn-dark">إضافة مستهلك</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-2">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" value="{{ $search }}"
                    placeholder="بحث بالاسم أو الهاتف أو الواتساب">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">كل الحالات</option>
                    <option value="active" @selected($status === 'active')>نشط</option>
                    <option value="inactive" @selected($status === 'inactive')>غير نشط</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-dark" type="submit">تصفية</button>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>الاسم</th>
                        <th>الهاتف</th>
                        <th>واتساب</th>
                        <th>العنوان</th>
                        <th>الحالة</th>
                        <th class="text-end">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($consumers as $consumer)
                        <tr>
                            <td>{{ $consumer->id }}</td>
                            <td>{{ $consumer->name }}</td>
                            <td>{{ $consumer->phone }}</td>
                            <td>{{ $consumer->whatsapp ?: '-' }}</td>
                            <td>{{ $consumer->address ?: '-' }}</td>
                            <td>
                                <span
                                    class="badge {{ $consumer->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $consumer->status === 'active' ? 'نشط' : 'غير نشط' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.consumers.edit', $consumer) }}"
                                        class="btn btn-sm btn-outline-primary">تعديل</a>
                                    <form method="POST" action="{{ route('admin.consumers.toggleStatus', $consumer) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-dark" type="submit">تبديل الحالة</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.consumers.destroy', $consumer) }}"
                                        onsubmit="return confirm('تأكيد حذف المستهلك؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">لا توجد بيانات</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $consumers->links() }}</div>
@endsection
