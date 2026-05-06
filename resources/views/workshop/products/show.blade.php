@extends('workshop.layout.app')

@section('title', 'تفاصيل المنتج')

@section('content')
@php
$carModels = collect($product->car_models ?? [])->map(fn($item) => (string) $item)->values();
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 fw-bold mb-0">تفاصيل المنتج</h1>
    <a href="{{ route('workshop.products.index') }}" class="btn btn-outline-secondary">رجوع</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="row g-4">
            <div class="col-md-3">
                @if ($product->image)
                <img src="{{ asset('storage/' . $product->image) }}" alt="صورة المنتج" class="img-fluid rounded border">
                @else
                <div class="border rounded p-4 text-center text-muted">لا توجد صورة</div>
                @endif
            </div>
            <div class="col-md-9">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label text-muted mb-1">اسم المنتج</label>
                        <div class="fw-semibold">{{ $product->name }}</div>
                    </div>
                    <div class="col-md-6"><label class="form-label text-muted mb-1">الموديل</label>
                        <div class="fw-semibold">{{ $product->model }}</div>
                    </div>
                    <div class="col-md-6"><label class="form-label text-muted mb-1">التصنيف</label>
                        <div>{{ $product->category?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6"><label class="form-label text-muted mb-1">الحالة</label>
                        <div>{{ $product->status === 'active' ? 'مفعل' : 'معطل' }}</div>
                    </div>
                    <div class="col-md-6"><label class="form-label text-muted mb-1">موديلات السيارة</label>
                        <div>{{ $carModels->isNotEmpty() ? $carModels->join(' - ') : '-' }}</div>
                    </div>
                    <div class="col-12"><label class="form-label text-muted mb-1">الوصف</label>
                        <div>{{ $product->description ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <h2 class="h6 fw-bold mb-3">الوحدات والأسعار</h2>
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>الوحدة</th>
                        <th>سعر الجملة</th>
                        <th>معامل التحويل</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($product->productUnits as $unitRow)
                    <tr>
                        <td>{{ $unitRow->unit?->name ?? '-' }}</td>
                        <td>{{ number_format((float) $unitRow->wholesale_price, 2) }}</td>
                        <td>{{ rtrim(rtrim(number_format((float) $unitRow->conversion_factor, 4, '.', ''), '0'), '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">لا توجد وحدات</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h2 class="h6 fw-bold mb-3">المواصفات</h2>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>نوع المواصفة</th>
                        <th>القيمة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($product->productVariants as $variant)
                    <tr>
                        <td>{{ $variant->variantValue?->type?->name ?? '-' }}</td>
                        <td>{{ $variant->variantValue?->value ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="text-center text-muted">لا توجد مواصفات</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection