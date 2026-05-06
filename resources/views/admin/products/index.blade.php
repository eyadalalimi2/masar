@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">إدارة المنتجات</h1>
        <p class="text-muted mb-0">دعم الجملة والتجزئة والوحدات</p>
    </div>
    <a href="{{ route('admin.products.create') }}" class="btn btn-dark">إضافة منتج</a>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="GET" action="{{ route('admin.products.index') }}" class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label mb-1">بحث</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                    placeholder="اسم المنتج أو الموديل">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">التصنيف</label>
                <select name="category_id" class="form-select">
                    <option value="">الكل</option>
                    @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) request('category_id')===(int) $category->id)>
                        {{ $category->name }}
                    </option>
                    @endforeach
                </select>
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
            <div class="col-md-2">
                <button type="submit" class="btn btn-dark w-100">تطبيق</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary w-100">إعادة التعيين</a>
            </div>
        </div>
    </div>
</form>

@php
$productsTrashedCount = $products->getCollection()->filter(fn($item) => $item->trashed())->count();
@endphp

<div class="d-flex gap-2 align-items-center mb-2">
    <span class="badge text-bg-dark">عدد السجلات في الصفحة: {{ $products->count() }}</span>
    <span class="badge text-bg-warning">المحذوف في الصفحة: {{ $productsTrashedCount }}</span>
</div>

<div class="table-responsive">
    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>الموديل</th>
                <th>الصورة</th>
                <th>المنتج</th>
                <th>الوكيل</th>
                <th>التصنيف</th>
                <th>سعر الجملة</th>
                <th>المواصفات</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
            <tr class="{{ $product->trashed() ? 'table-warning' : '' }}">
                <td>{{ $product->model }}</td>
                <td style="width:90px;">
                    @if ($product->image)
                    <img src="{{ asset('storage/' . $product->image) }}" alt="صورة"
                        style="width:64px;height:48px;object-fit:cover;border-radius:8px;">
                    @else
                    <span class="text-muted small">لا يوجد</span>
                    @endif
                </td>
                <td>
                    <div class="fw-semibold">{{ $product->name }}</div>
                    <div class="small text-muted">{{ $product->productUnits->count() }} وحدة</div>
                </td>
                <td>{{ $product->supplier?->business_name ?? $product->supplier?->owner_name }}</td>
                <td>{{ $product->category?->name }}</td>
                <td>
                    @if ($product->productUnits->isNotEmpty())
                    @foreach ($product->productUnits as $unitRow)
                    <div class="small">
                        {{ $unitRow->unit?->name ?? 'وحدة' }}:
                        {{ number_format((float) $unitRow->wholesale_price, 2) }}
                    </div>
                    @endforeach
                    @else
                    <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    @if ($product->productVariants->isNotEmpty())
                    @foreach ($product->productVariants as $variant)
                    <div class="small">
                        {{ $variant->variantValue?->type?->name ?? 'المواصفة' }}:
                        {{ $variant->variantValue?->value ?? '-' }}
                    </div>
                    @endforeach
                    @else
                    <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    @if ($product->trashed())
                    <span class="badge text-bg-danger">محذوف</span>
                    @else
                    <form action="{{ route('admin.products.toggle', $product) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        @if ($product->status === 'active')
                        <button type="submit" class="badge text-bg-success border-0">مفعل</button>
                        @else
                        <button type="submit" class="badge text-bg-secondary border-0">معطل</button>
                        @endif
                    </form>
                    @endif
                </td>
                <td>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.products.show', $product) }}"
                            class="btn btn-sm btn-outline-dark">عرض</a>
                        @if (! $product->trashed())
                        <a href="{{ route('admin.products.edit', $product) }}"
                            class="btn btn-sm btn-outline-primary">تعديل</a>
                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                            onsubmit="return confirm('هل أنت متأكد من حذف المنتج؟');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                        </form>
                        @else
                        <form method="POST" action="{{ route('admin.products.restore', $product->id) }}"
                            onsubmit="return confirm('هل تريد استرجاع المنتج؟');">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-sm btn-outline-success" type="submit">استرجاع</button>
                        </form>
                        <form method="POST" action="{{ route('admin.products.force-delete', $product->id) }}"
                            onsubmit="return confirm('سيتم حذف المنتج نهائيًا. هل أنت متأكد؟');">
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
                <td colspan="9" class="text-center text-muted py-4">لا يوجد منتجات</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $products->links() }}
@endsection