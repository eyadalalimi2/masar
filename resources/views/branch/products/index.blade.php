@extends('branch.layout.app')

@section('title', 'منتجات الفرع')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">منتجات الفرع</h1>
        <p class="text-muted mb-0">عرض كتالوج منتجات الوكيل. تسعير الفرع المحلي يتم من شاشة المخزون.</p>
    </div>
    <a href="{{ route('branch.inventory.index') }}" class="btn btn-outline-dark">تسعير ومخزون الفرع</a>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="GET" action="{{ route('branch.products.index') }}" class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-8">
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
                <button type="submit" class="btn btn-dark w-100">تطبيق</button>
            </div>
        </div>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>الموديل</th>
                <th>الصورة</th>
                <th>المنتج</th>
                <th>التصنيف</th>
                <th>سعر الجملة</th>
                <th>المخزون</th>
                <th>سعر بيع الفرع</th>
                <th>المواصفات</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
            <tr>
                <td>{{ $product->model }}</td>
                <td style="width:90px;">
                    @if ($product->image)
                    <img src="{{ asset('storage/' . $product->image) }}" alt="صورة"
                        style="width:64px;height:48px;object-fit:cover;border-radius:8px;">
                    @else
                    <span class="text-muted small">لا يوجد</span>
                    @endif
                </td>
                <td>{{ $product->name }}</td>
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
                    @if ($product->productUnits->isNotEmpty())
                    @foreach ($product->productUnits as $unitRow)
                    <div class="small">
                        {{ $unitRow->unit?->name ?? 'وحدة' }}:
                        {{ number_format((float) $unitRow->stock_quantity, 3) }}
                    </div>
                    @endforeach
                    @else
                    <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    @if ($product->productUnits->isNotEmpty())
                    @foreach ($product->productUnits as $unitRow)
                    @php
                    $branchSellingPrice = $branchSellingPricesByUnit[$unitRow->id] ?? null;
                    $editableSellingPrice = $branchSellingPrice ?? (float) ($unitRow->retail_price ?? 0);
                    @endphp
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="small text-muted" style="min-width:70px;">{{ $unitRow->unit?->name ?? 'وحدة' }}:</span>
                        <form method="POST" action="{{ route('branch.inventory.update-price') }}" class="d-flex gap-1 align-items-center">
                            @csrf
                            <input type="hidden" name="product_unit_id" value="{{ $unitRow->id }}">
                            <input type="number" step="0.01" min="0" name="selling_price" class="form-control form-control-sm"
                                style="max-width:110px;"
                                value="{{ number_format((float) $editableSellingPrice, 2, '.', '') }}">
                            <button class="btn btn-sm btn-outline-primary" type="submit">حفظ</button>
                        </form>
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
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('branch.products.show', $product) }}"
                            class="btn btn-sm btn-outline-dark">عرض</a>
                        <a href="{{ route('branch.inventory.index') }}" class="btn btn-sm btn-outline-primary">تسعير الفرع</a>
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