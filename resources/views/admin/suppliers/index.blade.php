@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">إدارة الوكلاء</h1>
        <p class="text-muted mb-0">إضافة وتعديل وتعطيل وكلاء النظام</p>
    </div>
    <a href="{{ route('admin.suppliers.create') }}" class="btn btn-dark">إضافة وكيل</a>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<form method="GET" action="{{ route('admin.suppliers.index') }}" class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-1">بحث</label>
                <input type="text" name="search" class="form-control"
                    placeholder="اسم المالك أو الاسم التجاري أو رقم الهاتف" value="{{ request('search') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label mb-1">الحالة</label>
                <select name="status" class="form-select">
                    <option value="">الكل</option>
                    <option value="active" @selected(request('status')==='active' )>مفعل</option>
                    <option value="inactive" @selected(request('status')==='inactive' )>معطل</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label mb-1">التوثيق</label>
                <select name="verification" class="form-select">
                    <option value="">الكل</option>
                    <option value="verified" @selected(request('verification')==='verified' )>موثّق</option>
                    <option value="pending" @selected(request('verification')==='pending' )>طلب قيد المراجعة</option>
                    <option value="not_requested" @selected(request('verification')==='not_requested' )>بدون طلب توثيق</option>
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
                <button type="submit" class="btn btn-dark w-100">تطبيق</button>
                <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary w-100">إعادة التعيين</a>
            </div>
        </div>
    </div>
</form>

@php
$suppliersTrashedCount = $suppliers->getCollection()->filter(fn($item) => $item->trashed())->count();
@endphp

<div class="d-flex gap-2 align-items-center mb-2">
    <span class="badge text-bg-dark">عدد السجلات في الصفحة: {{ $suppliers->count() }}</span>
    <span class="badge text-bg-warning">المحذوف في الصفحة: {{ $suppliersTrashedCount }}</span>
</div>

<div class="table-responsive">
    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>المعرف</th>
                <th>اسم المالك</th>
                <th>رقم الهاتف</th>
                <th>الاسم التجاري</th>
                <th>صورة الوكيل</th>
                <th>الشعار</th>
                <th>العنوان</th>
                <th>الحالة</th>
                <th>التوثيق</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($suppliers as $supplier)
            <tr class="{{ $supplier->trashed() ? 'table-warning' : '' }}">
                <td>{{ $supplier->id }}</td>
                <td>{{ $supplier->owner_name }}</td>
                <td>{{ $supplier->phone }}</td>
                <td>
                    <span>{{ $supplier->business_name }}</span>
                    @if ($supplier->is_verified)
                    <img src="{{ asset('assets/images/viv.png') }}" alt="موثق" class="ms-1 align-middle"
                        style="width: 18px; height: 18px; object-fit: contain;">
                    @endif
                </td>
                <td style="width: 90px;">
                    @if ($supplier->agent_image)
                    <img src="{{ asset('storage/' . $supplier->agent_image) }}" alt="صورة الوكيل"
                        style="width: 44px; height: 44px; object-fit: cover; border-radius: 999px; border: 1px solid #e5e7eb;">
                    @else
                    <span class="text-muted small">لا يوجد</span>
                    @endif
                </td>
                <td style="width: 90px;">
                    @if ($supplier->logo)
                    <img src="{{ asset('storage/' . $supplier->logo) }}" alt="شعار"
                        style="width: 64px; height: 40px; object-fit: contain;">
                    @else
                    <span class="text-muted small">لا يوجد</span>
                    @endif
                </td>
                <td style="min-width: 240px;">
                    <div class="d-flex gap-2 align-items-center">
                        <span class="small">{{ $supplier->address }}</span>
                        <a href="https://www.google.com/maps?q={{ rawurlencode($supplier->gps_location ?: $supplier->address) }}"
                            target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-dark">
                            عرض العنوان
                        </a>
                    </div>
                </td>
                <td>
                    @if ($supplier->trashed())
                    <span class="badge text-bg-danger">محذوف</span>
                    @else
                    <form action="{{ route('admin.suppliers.toggle', $supplier) }}" method="POST"
                        class="d-inline">
                        @csrf
                        @method('PATCH')
                        @if ($supplier->status === 'active')
                        <button type="submit" class="badge text-bg-success border-0">مفعل</button>
                        @else
                        <button type="submit" class="badge text-bg-secondary border-0">معطل</button>
                        @endif
                    </form>
                    @endif
                </td>
                <td>
                    @if ($supplier->is_verified)
                    <span class="badge text-bg-success">موثّق</span>
                    @elseif ($supplier->has_verification_request)
                    <div class="d-flex flex-column gap-1">
                        <span class="badge text-bg-warning">طلب قيد المراجعة</span>
                        <form action="{{ route('admin.suppliers.verify', $supplier) }}" method="POST"
                            onsubmit="return confirm('قبول طلب توثيق الوكيل؟ بعد القبول لن يستطيع الوكيل تعديل بياناته.');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-success">قبول التوثيق</button>
                        </form>
                    </div>
                    @else
                    <span class="badge text-bg-secondary">لا يوجد طلب</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.suppliers.show', $supplier) }}"
                            class="btn btn-sm btn-outline-dark">عرض</a>
                        @if (! $supplier->trashed())
                        <a href="{{ route('admin.suppliers.edit', $supplier) }}"
                            class="btn btn-sm btn-outline-primary">تعديل</a>

                        <form action="{{ route('admin.suppliers.destroy', $supplier) }}" method="POST"
                            onsubmit="return confirm('هل أنت متأكد من حذف الوكيل؟');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                        </form>
                        @else
                        <form action="{{ route('admin.suppliers.restore', $supplier->id) }}" method="POST"
                            onsubmit="return confirm('هل تريد استرجاع الوكيل؟');">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-sm btn-outline-success" type="submit">استرجاع</button>
                        </form>

                        <form action="{{ route('admin.suppliers.force-delete', $supplier->id) }}" method="POST"
                            onsubmit="return confirm('سيتم حذف الوكيل نهائيًا. هل أنت متأكد؟');">
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
                <td colspan="10" class="text-center text-muted py-4">لا يوجد وكلاء حتى الآن</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $suppliers->links() }}
@endsection