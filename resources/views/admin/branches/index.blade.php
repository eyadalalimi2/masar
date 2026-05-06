@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">إدارة الفروع</h1>
        <p class="text-muted mb-0">إضافة وتعديل وتعطيل الفروع</p>
    </div>
    <a href="{{ route('admin.branches.create') }}" class="btn btn-dark">إضافة فرع</a>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (!empty($supplierFilter))
<div class="alert alert-info d-flex justify-content-between align-items-center">
    <span>عرض الفروع المرتبطة بالوكيل: <strong>{{ $supplierFilter->business_name }}</strong></span>
    <a href="{{ route('admin.suppliers.show', $supplierFilter) }}" class="btn btn-sm btn-outline-primary">العودة
        لتفاصيل الوكيل</a>
</div>
@endif

<form method="GET" action="{{ route('admin.branches.index') }}" class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        @if (!empty($supplierFilter))
        <input type="hidden" name="supplier_id" value="{{ $supplierFilter->id }}">
        @endif
        <div class="row g-2 align-items-end">
            <div class="col-md-8">
                <label class="form-label mb-1">بحث</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                    placeholder="اسم الفرع أو رقم الهاتف أو العنوان">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">الحالة</label>
                <select name="status" class="form-select">
                    <option value="">الكل</option>
                    <option value="active" @selected(request('status')==='active' )>مفعل</option>
                    <option value="inactive" @selected(request('status')==='inactive' )>معطل</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">المحذوفات</label>
                <select name="trashed" class="form-select">
                    <option value="" @selected(request('trashed')==='' )>الافتراضي</option>
                    <option value="all" @selected(request('trashed')==='all' )>الكل</option>
                    <option value="only" @selected(request('trashed')==='only' )>المحذوف فقط</option>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-dark w-100" type="submit">تطبيق</button>
                <a href="{{ route('admin.branches.index') }}" class="btn btn-outline-secondary w-100">إعادة التعيين</a>
            </div>
        </div>
    </div>
</form>

@php
$branchesTrashedCount = $branches->getCollection()->filter(fn($item) => $item->trashed())->count();
@endphp

<div class="d-flex gap-2 align-items-center mb-2">
    <span class="badge text-bg-dark">عدد السجلات في الصفحة: {{ $branches->count() }}</span>
    <span class="badge text-bg-warning">المحذوف في الصفحة: {{ $branchesTrashedCount }}</span>
</div>

<div class="table-responsive">
    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>المعرف</th>
                <th>الوكيل</th>
                <th>لوجو الوكيل</th>
                <th>اسم الفرع</th>
                <th>رقم الهاتف</th>
                <th>اسم مدير الفرع</th>
                <th>صورة مدير الفرع</th>
                <th>العنوان</th>
                <th>الموقع</th>
                <th>أوقات الدوام</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($branches as $branch)
            @php
            $enabledDays = collect($branch->working_hours_schedule)
            ->filter(fn($day) => (bool) data_get($day, 'enabled', false))
            ->count();
            @endphp
            <tr class="{{ $branch->trashed() ? 'table-warning' : '' }}">
                <td>{{ $branch->id }}</td>
                <td>{{ $branch->supplier?->business_name }}</td>
                <td>
                    @if ($branch->supplier?->logo_url)
                    <img src="{{ $branch->supplier->logo_url }}" alt="لوجو الوكيل"
                        style="width: 54px; height: 54px; object-fit: cover; border-radius: 10px; border: 1px solid #e5e7eb;">
                    @else
                    -
                    @endif
                </td>
                <td>{{ $branch->name }}</td>
                <td>{{ $branch->phone }}</td>
                <td>{{ $branch->branch_manager_name ?: '-' }}</td>
                <td>
                    @if ($branch->branch_manager_image)
                    <img src="{{ asset('storage/' . $branch->branch_manager_image) }}" alt="صورة مدير الفرع"
                        style="width: 54px; height: 54px; object-fit: cover; border-radius: 999px; border: 1px solid #e5e7eb;">
                    @else
                    -
                    @endif
                </td>
                <td>{{ $branch->address }}</td>
                <td>
                    @if ($branch->gps_location)
                    <a href="https://www.google.com/maps?q={{ rawurlencode($branch->gps_location) }}"
                        target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-dark">
                        عرض الموقع بالخريطة
                    </a>
                    @else
                    -
                    @endif
                </td>
                <td style="min-width: 260px;">
                    <details>
                        <summary class="small">
                            {{ $enabledDays > 0 ? 'مفعّل ' . $enabledDays . ' يوم' : 'لا يوجد دوام مفعّل' }}
                        </summary>
                        @include('admin.partials.working-hours-display', [
                        'schedule' => $branch->working_hours_schedule,
                        ])
                    </details>
                </td>
                <td>
                    @if ($branch->trashed())
                    <span class="badge text-bg-danger">محذوف</span>
                    @else
                    <form action="{{ route('admin.branches.toggle', $branch) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        @if ($branch->status === 'active')
                        <button type="submit" class="badge text-bg-success border-0">مفعل</button>
                        @else
                        <button type="submit" class="badge text-bg-secondary border-0">معطل</button>
                        @endif
                    </form>
                    @endif
                </td>
                <td>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.branches.show', $branch) }}"
                            class="btn btn-sm btn-outline-dark">عرض</a>
                        @if (! $branch->trashed())
                        <a href="{{ route('admin.branches.edit', $branch) }}"
                            class="btn btn-sm btn-outline-primary">تعديل</a>
                        <form action="{{ route('admin.branches.destroy', $branch) }}" method="POST"
                            onsubmit="return confirm('هل أنت متأكد من حذف الفرع؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                        </form>
                        @else
                        <form action="{{ route('admin.branches.restore', $branch->id) }}" method="POST"
                            onsubmit="return confirm('هل تريد استرجاع الفرع؟');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-outline-success">استرجاع</button>
                        </form>
                        <form action="{{ route('admin.branches.force-delete', $branch->id) }}" method="POST"
                            onsubmit="return confirm('سيتم حذف الفرع نهائيًا. متابعة؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">حذف نهائي</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="12" class="text-center text-muted py-4">لا يوجد فروع حتى الآن</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $branches->links() }}
@endsection