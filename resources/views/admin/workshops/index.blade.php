@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 fw-bold mb-0">إدارة الورش</h1>
    <a href="{{ route('admin.workshops.create') }}" class="btn btn-dark">إضافة ورشة</a>
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
                <option value="active" @selected($status==='active' )>نشط</option>
                <option value="inactive" @selected($status==='inactive' )>غير نشط</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="trashed" class="form-select">
                <option value="" @selected(request('trashed')==='' )>الافتراضي</option>
                <option value="all" @selected(request('trashed')==='all' )>الكل</option>
                <option value="only" @selected(request('trashed')==='only' )>المحذوف فقط</option>
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-outline-dark" type="submit">تصفية</button>
        </div>
        <div class="col-md-2 d-grid">
            <a class="btn btn-outline-secondary" href="{{ route('admin.workshops.index') }}">إعادة التعيين</a>
        </div>
    </div>
</form>

@php
$workshopsTrashedCount = $customers->getCollection()->filter(fn($item) => $item->trashed())->count();
@endphp

<div class="d-flex gap-2 align-items-center mb-2">
    <span class="badge text-bg-dark">عدد السجلات في الصفحة: {{ $customers->count() }}</span>
    <span class="badge text-bg-warning">المحذوف في الصفحة: {{ $workshopsTrashedCount }}</span>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>الاسم</th>
                    <th>الهاتف</th>
                    <th>المالك</th>
                    <th>أوقات الدوام</th>
                    <th>الحالة</th>
                    <th class="text-end">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                @php
                $enabledDays = collect($customer->working_hours_schedule)
                ->filter(fn($day) => (bool) data_get($day, 'enabled', false))
                ->count();
                @endphp
                <tr class="{{ $customer->trashed() ? 'table-warning' : '' }}">
                    <td>{{ $customer->id }}</td>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->phone }}</td>
                    <td>{{ $customer->owner_name ?: '-' }}</td>
                    <td style="min-width: 260px;">
                        <details>
                            <summary class="small">
                                {{ $enabledDays > 0 ? 'مفعّل ' . $enabledDays . ' يوم' : 'لا يوجد دوام مفعّل' }}
                            </summary>
                            @include('admin.partials.working-hours-display', [
                            'schedule' => $customer->working_hours_schedule,
                            ])
                        </details>
                    </td>
                    <td>
                        @if ($customer->trashed())
                        <span class="badge text-bg-danger">محذوف</span>
                        @else
                        <span
                            class="badge {{ $customer->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                            {{ $customer->status === 'active' ? 'نشط' : 'غير نشط' }}
                        </span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            @if (! $customer->trashed())
                            <a href="{{ route('admin.workshops.edit', $customer) }}"
                                class="btn btn-sm btn-outline-primary">تعديل</a>
                            <form method="POST" action="{{ route('admin.workshops.toggleStatus', $customer) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-dark" type="submit">تبديل الحالة</button>
                            </form>
                            <form method="POST" action="{{ route('admin.workshops.destroy', $customer) }}"
                                onsubmit="return confirm('تأكيد حذف الورشة؟');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('admin.workshops.restore', $customer->id) }}"
                                onsubmit="return confirm('تأكيد استرجاع الورشة؟');">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-success" type="submit">استرجاع</button>
                            </form>
                            <form method="POST" action="{{ route('admin.workshops.forceDestroy', $customer->id) }}"
                                onsubmit="return confirm('سيتم حذف الورشة نهائيًا. متابعة؟');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit">حذف نهائي</button>
                            </form>
                            @endif
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

<div class="mt-3">{{ $customers->links() }}</div>
@endsection