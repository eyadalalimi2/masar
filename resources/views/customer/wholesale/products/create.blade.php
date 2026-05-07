@extends('customer.layout.app')

@section('title', 'إضافة منتج')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">إضافة منتج جديد</h1>
            <p class="text-muted mb-0">إدخال بيانات المنتج ووحدته الأساسية.</p>
        </div>
        <a href="{{ route('customer.wholesale.products.index') }}" class="btn btn-outline-secondary">رجوع</a>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('customer.wholesale.products.store') }}" enctype="multipart/form-data" class="card border-0 shadow-sm">
        @csrf
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">اسم المنتج</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">الموديل</label>
                    <input type="text" name="model" class="form-control" value="{{ old('model') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">التصنيف</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">اختر التصنيف</option>
                        @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) old('category_id')===(int) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">الوحدة</label>
                    <select name="unit_id" class="form-select" required>
                        <option value="">اختر الوحدة</option>
                        @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected((int) old('unit_id')===(int) $unit->id)>{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">سعر الجملة</label>
                    <input type="number" step="0.01" min="0" name="wholesale_price" class="form-control" value="{{ old('wholesale_price') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">سعر التجزئة</label>
                    <input type="number" step="0.01" min="0" name="retail_price" class="form-control" value="{{ old('retail_price') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">معامل التحويل</label>
                    <input type="number" step="0.0001" min="0.0001" name="conversion_factor" class="form-control" value="{{ old('conversion_factor', 1) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">الكمية</label>
                    <input type="number" step="0.001" min="0" name="stock_quantity" class="form-control" value="{{ old('stock_quantity', 0) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">حد التنبيه</label>
                    <input type="number" step="0.001" min="0" name="low_stock_threshold" class="form-control" value="{{ old('low_stock_threshold', 0) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">صورة المنتج</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a href="{{ route('customer.wholesale.products.index') }}" class="btn btn-light border">إلغاء</a>
            <button type="submit" class="btn btn-primary">حفظ المنتج</button>
        </div>
    </form>
</div>
@endsection