@extends('agent.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">إدارة المحلات التجارية</h1>
        <a href="{{ route('agent.commercial-stores.create') }}" class="btn btn-dark">إضافة محل تجاري</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-2">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" value="{{ $search }}"
                    placeholder="بحث بالاسم أو الهاتف أو المالك">
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
            <div class="col-md-2 d-grid">
                <a class="btn btn-outline-secondary" href="{{ route('agent.commercial-stores.index') }}">إعادة</a>
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
                        <th>المالك</th>
                        <th>الحالة</th>
                        <th class="text-end">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td>{{ $customer->id }}</td>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->phone }}</td>
                            <td>{{ $customer->owner_name ?: '-' }}</td>
                            <td>
                                <span
                                    class="badge {{ $customer->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $customer->status === 'active' ? 'نشط' : 'غير نشط' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('agent.commercial-stores.edit', $customer) }}"
                                        class="btn btn-sm btn-outline-primary">تعديل</a>
                                    <form method="POST"
                                        action="{{ route('agent.commercial-stores.toggleStatus', $customer) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-dark" type="submit">تبديل الحالة</button>
                                    </form>
                                    <form method="POST" action="{{ route('agent.commercial-stores.destroy', $customer) }}"
                                        onsubmit="return confirm('تأكيد حذف المحل التجاري؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">لا توجد بيانات</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $customers->links() }}</div>
@endsection
