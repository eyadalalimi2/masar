@extends('customer.layout.app')

@section('title', 'إدارة المنتجات')

@section('content')
<div class="container-fluid py-2">
    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">إدارة المنتجات</h1>
            <p class="text-muted mb-0">عرض كتالوج المنتجات المتاحة لتاجر الجملة.</p>
        </div>
        <a href="{{ route('customer.wholesale.products.create') }}" class="btn btn-primary">إضافة منتج</a>
    </div>

    <form method="GET" class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-7">
                    <label class="form-label mb-1">بحث</label>
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="اسم المنتج أو الموديل">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">التصنيف</label>
                    <select name="category_id" class="form-select">
                        <option value="">الكل</option>
                        @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($categoryId===(int) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-dark" type="submit">تطبيق</button>
                </div>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>الصورة</th>
                        <th>المنتج</th>
                        <th>الموديل</th>
                        <th>التصنيف</th>
                        <th>الأسعار</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                    <tr>
                        <td style="width:90px;">
                            @if ($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="rounded border"
                                style="width:64px;height:48px;object-fit:cover;">
                            @else
                            <span class="text-muted small">لا يوجد</span>
                            @endif
                        </td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->model ?: '-' }}</td>
                        <td>{{ $product->category?->name ?: '-' }}</td>
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
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">لا توجد منتجات مطابقة.</td>
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