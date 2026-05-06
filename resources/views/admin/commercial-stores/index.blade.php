@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 fw-bold mb-0">إدارة المحلات التجارية</h1>
    <a href="{{ route('admin.commercial-stores.create') }}" class="btn btn-dark">إضافة محل تجاري</a>
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
            <a class="btn btn-outline-secondary" href="{{ route('admin.commercial-stores.index') }}">إعادة التعيين</a>
        </div>
    </div>
</form>

@php
$customersTrashedCount = $customers->getCollection()->filter(fn($item) => $item->trashed())->count();
@endphp

<div class="d-flex gap-2 align-items-center mb-2">
    <span class="badge text-bg-dark">عدد السجلات في الصفحة: {{ $customers->count() }}</span>
    <span class="badge text-bg-warning">المحذوف في الصفحة: {{ $customersTrashedCount }}</span>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>اللوجو</th>
                    <th>الاسم</th>
                    <th>الهاتف</th>
                    <th>المالك</th>
                    <th>صورة المالك</th>
                    <th>صور المحل</th>
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
                    <td>
                        @if ($customer->logo_url)
                        <img src="{{ $customer->logo_url }}" alt="لوجو المحل" class="rounded border"
                            style="width: 52px; height: 52px; object-fit: cover;">
                        @else
                        -
                        @endif
                    </td>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->phone }}</td>
                    <td>{{ $customer->owner_name ?: '-' }}</td>
                    <td>
                        @if ($customer->owner_image_url)
                        <img src="{{ $customer->owner_image_url }}" alt="صورة المالك"
                            class="rounded-circle border" style="width: 52px; height: 52px; object-fit: cover;">
                        @else
                        -
                        @endif
                    </td>
                    <td>
                        {{ is_array($customer->store_image_urls ?? null) ? count($customer->store_image_urls) : 0 }}
                    </td>
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
                            <a href="{{ route('admin.commercial-stores.edit', $customer) }}"
                                class="btn btn-sm btn-outline-primary">تعديل</a>
                            <form method="POST"
                                action="{{ route('admin.commercial-stores.toggleStatus', $customer) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-dark" type="submit">تبديل الحالة</button>
                            </form>
                            <form method="POST"
                                action="{{ route('admin.commercial-stores.destroy', $customer) }}"
                                onsubmit="return confirm('تأكيد حذف المحل التجاري؟');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                            </form>
                            @else
                            <form method="POST"
                                action="{{ route('admin.commercial-stores.restore', $customer->id) }}"
                                onsubmit="return confirm('تأكيد استرجاع المحل التجاري؟');">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-success" type="submit">استرجاع</button>
                            </form>
                            <form method="POST"
                                action="{{ route('admin.commercial-stores.forceDestroy', $customer->id) }}"
                                onsubmit="return confirm('سيتم حذف المحل التجاري نهائيًا. متابعة؟');">
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
                    <td colspan="10" class="text-center text-muted py-4">لا توجد بيانات</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $customers->links() }}</div>
@endsection