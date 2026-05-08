@extends('distributor.layout.app')

@section('title', 'منتجات المندوب')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
        <div>
            <h1 class="h4 mb-1">منتجات الوكيل</h1>
            <p class="text-muted mb-0">عرض المنتجات المفعلة المرتبطة بوكيل المندوب</p>
        </div>
    </div>

    <form method="GET" action="{{ route('distributor.products.index') }}" class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-7">
                    <label class="form-label mb-1">بحث</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                        placeholder="اسم المنتج أو الموديل">
                </div>
                <div class="col-md-3">
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
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100">تطبيق</button>
                </div>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>الموديل</th>
                        <th>الصورة</th>
                        <th>المنتج</th>
                        <th>التصنيف</th>
                        <th>سعر الجملة</th>
                        <th>المخزون</th>
                        <th>المواصفات</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
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
                            <a href="{{ route('distributor.products.show', $product) }}"
                                class="btn btn-sm btn-outline-dark">عرض</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">لا توجد منتجات مفعلة حالياً لهذا
                            الوكيل.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())
        <div class="card-footer bg-white">
            {{ $products->links() }}
        </div>
        @endif
    </div>
</div>
@endsection