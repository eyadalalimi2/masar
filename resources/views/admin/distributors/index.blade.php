@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">إدارة المندوبين</h1>
        <p class="text-muted mb-0">إضافة وتعديل وتعطيل المندوبين</p>
    </div>
    <a href="{{ route('admin.distributors.create') }}" class="btn btn-dark">إضافة مندوب</a>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (!empty($supplierFilter))
<div class="alert alert-info d-flex justify-content-between align-items-center">
    <span>عرض المندوبين المرتبطين بالوكيل: <strong>{{ $supplierFilter->business_name }}</strong></span>
    <a href="{{ route('admin.suppliers.show', $supplierFilter) }}" class="btn btn-sm btn-outline-primary">العودة
        لتفاصيل الوكيل</a>
</div>
@endif

@if (!empty($branchFilter))
<div class="alert alert-info d-flex justify-content-between align-items-center">
    <span>عرض المندوبين المرتبطين بالفرع: <strong>{{ $branchFilter->name }}</strong></span>
    <a href="{{ route('admin.branches.show', $branchFilter) }}" class="btn btn-sm btn-outline-primary">العودة
        لتفاصيل الفرع</a>
</div>
@endif

<form method="GET" action="{{ route('admin.distributors.index') }}" class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        @if (!empty($supplierFilter))
        <input type="hidden" name="supplier_id" value="{{ $supplierFilter->id }}">
        @endif
        @if (!empty($branchFilter))
        <input type="hidden" name="branch_id" value="{{ $branchFilter->id }}">
        @endif
        <div class="row g-2 align-items-end">
            <div class="col-md-8">
                <label class="form-label mb-1">بحث</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                    placeholder="اسم المندوب أو رقم الهاتف أو نوع المركبة">
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
                <a href="{{ route('admin.distributors.index') }}" class="btn btn-outline-secondary w-100">إعادة التعيين</a>
            </div>
        </div>
    </div>
</form>

@php
$distributorsTrashedCount = $distributors->getCollection()->filter(fn($item) => $item->trashed())->count();
@endphp

<div class="d-flex gap-2 align-items-center mb-2">
    <span class="badge text-bg-dark">عدد السجلات في الصفحة: {{ $distributors->count() }}</span>
    <span class="badge text-bg-warning">المحذوف في الصفحة: {{ $distributorsTrashedCount }}</span>
</div>

<div class="table-responsive">
    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>المعرف</th>
                <th>الصورة</th>
                <th>الوكيل</th>
                <th>الاسم التجاري</th>
                <th>لوجو الوكيل</th>
                <th>الفرع</th>
                <th>الاسم</th>
                <th>رقم الهاتف</th>
                <th>نوع المركبة</th>
                <th>أماكن التوزيع</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($distributors as $distributor)
            <tr class="{{ $distributor->trashed() ? 'table-warning' : '' }}">
                <td>{{ $distributor->id }}</td>
                <td style="width:90px;">
                    @if ($distributor->image)
                    <img src="{{ asset('storage/' . $distributor->image) }}" alt="صورة المندوب"
                        style="width:56px;height:56px;object-fit:cover;border-radius:50%;">
                    @else
                    <span class="text-muted small">لا يوجد</span>
                    @endif
                </td>
                <td>{{ $distributor->supplier?->owner_name }}</td>
                <td>{{ $distributor->supplier?->business_name ?: '-' }}</td>
                <td>
                    @if ($distributor->supplier?->logo_url)
                    <img src="{{ $distributor->supplier->logo_url }}" alt="لوجو الوكيل"
                        style="width: 54px; height: 54px; object-fit: cover; border-radius: 10px; border: 1px solid #e5e7eb;">
                    @else
                    -
                    @endif
                </td>
                <td>{{ $distributor->branch?->name ?: '-' }}</td>
                <td>{{ $distributor->name }}</td>
                <td>{{ $distributor->phone }}</td>
                <td>{{ $distributor->vehicle_type ?: '-' }}</td>
                <td style="white-space: pre-line;">{{ $distributor->distribution_points ?: '-' }}</td>
                <td>
                    @if ($distributor->trashed())
                    <span class="badge text-bg-danger">محذوف</span>
                    @else
                    <form action="{{ route('admin.distributors.toggle', $distributor) }}" method="POST"
                        class="d-inline">
                        @csrf
                        @method('PATCH')
                        @if ($distributor->status === 'active')
                        <button type="submit" class="badge text-bg-success border-0">مفعل</button>
                        @else
                        <button type="submit" class="badge text-bg-secondary border-0">معطل</button>
                        @endif
                    </form>
                    @endif
                </td>
                <td>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.distributors.show', $distributor) }}"
                            class="btn btn-sm btn-outline-dark">عرض</a>
                        @if (! $distributor->trashed())
                        <a href="{{ route('admin.distributors.edit', $distributor) }}"
                            class="btn btn-sm btn-outline-primary">تعديل</a>

                        <form action="{{ route('admin.distributors.destroy', $distributor) }}" method="POST"
                            onsubmit="return confirm('هل أنت متأكد من حذف المندوب؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                        </form>
                        @else
                        <form action="{{ route('admin.distributors.restore', $distributor->id) }}" method="POST"
                            onsubmit="return confirm('هل تريد استرجاع المندوب؟');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-outline-success">استرجاع</button>
                        </form>
                        <form action="{{ route('admin.distributors.force-delete', $distributor->id) }}"
                            method="POST" onsubmit="return confirm('سيتم حذف المندوب نهائيًا. متابعة؟');">
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
                <td colspan="12" class="text-center text-muted py-4">لا يوجد مندوبون حتى الآن</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $distributors->links() }}
@endsection