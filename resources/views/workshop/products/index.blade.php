@extends('workshop.layout.app')

@section('title', 'إدارة المنتجات')

@section('content')
<div class="hero-box reveal rv1 mb-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="h4 mb-1">إدارة المنتجات</h1>
            <p class="mb-0 text-white-50">إضافة وتعديل وحذف منتجات مرتبطة تلقائيًا بالوكيل.</p>
        </div>
        <a href="{{ route('workshop.products.create') }}" class="btn btn-light btn-sm">إضافة منتج</a>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
<div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
@if ($supplierId === null)
<div class="alert alert-warning">لا يوجد وكيل مرتبط بهذه الورشة بعد. يتم الربط تلقائيًا بعد أول عملية شراء.</div>
@endif

<form method="GET" action="{{ route('workshop.products.index') }}" class="table-wrap reveal rv1 mb-3">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-8">
            <label class="form-label mb-1">بحث</label>
            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="اسم المنتج أو الموديل">
        </div>
        <div class="col-md-2">
            <label class="form-label mb-1">التصنيف</label>
            <select name="category_id" class="form-select">
                <option value="">الكل</option>
                @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) request('category_id')===(int) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-dark w-100">تطبيق</button></div>
    </div>
</form>

<div class="table-wrap reveal rv2">
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0 bg-white">
            <thead class="table-light">
                <tr>
                    <th>الموديل</th>
                    <th>الصورة</th>
                    <th>المنتج</th>
                    <th>التصنيف</th>
                    <th>سعر الجملة</th>
                    <th>المواصفات</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                <tr>
                    <td>{{ $product->model }}</td>
                    <td style="width:90px;">
                        @if ($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="صورة" style="width:64px;height:48px;object-fit:cover;border-radius:8px;">
                        @else
                        <span class="text-muted small">لا يوجد</span>
                        @endif
                    </td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->category?->name }}</td>
                    <td>
                        @forelse ($product->productUnits as $unitRow)
                        <div class="small">{{ $unitRow->unit?->name ?? 'وحدة' }}: {{ number_format((float) $unitRow->wholesale_price, 2) }}</div>
                        @empty
                        <span class="text-muted">-</span>
                        @endforelse
                    </td>
                    <td>
                        @forelse ($product->productVariants as $variant)
                        <div class="small">{{ $variant->variantValue?->type?->name ?? 'المواصفة' }}: {{ $variant->variantValue?->value ?? '-' }}</div>
                        @empty
                        <span class="text-muted">-</span>
                        @endforelse
                    </td>
                    <td>{{ $product->status === 'active' ? 'مفعل' : 'معطل' }}</td>
                    <td>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('workshop.products.show', $product) }}" class="btn btn-sm btn-outline-dark">عرض</a>
                            <a href="{{ route('workshop.products.edit', $product) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                            <form method="POST" action="{{ route('workshop.products.duplicate', $product) }}" onsubmit="return confirm('هل تريد إنشاء نسخة من هذا المنتج؟');">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary" type="submit">نسخ</button>
                            </form>
                            <form method="POST" action="{{ route('workshop.products.destroy', $product) }}" onsubmit="return confirm('هل أنت متأكد من حذف المنتج؟');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">لا يوجد منتجات</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $products->links() }}</div>
</div>
@endsection