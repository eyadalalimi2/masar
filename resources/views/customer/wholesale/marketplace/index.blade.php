@extends('customer.layout.app')

@section('title', 'سوق منتجات الجملة')

@section('content')
    <div class="container-fluid py-2">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
            <div>
                <h1 class="h4 mb-1">سوق منتجات الجملة</h1>
                <p class="text-muted mb-0">تصفح منتجات الفروع المتاحة وإنشاء طلبات شراء مباشرة.</p>
            </div>
        </div>

        <form method="GET" class="card border-0 shadow-sm mb-3">
            <div class="card-body p-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label mb-1">بحث</label>
                        <input type="text" name="search" class="form-control" value="{{ $search }}"
                            placeholder="اسم المنتج أو الموديل">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1">الفرع</label>
                        <select name="branch_id" class="form-select">
                            <option value="">الكل</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($branchId === (int) $branch->id)>{{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1">ترتيب</label>
                        <select name="sort" class="form-select">
                            <option value="price_asc" @selected($sort === 'price_asc')>الأقل سعرًا</option>
                            <option value="price_desc" @selected($sort === 'price_desc')>الأعلى سعرًا</option>
                            <option value="qty_desc" @selected($sort === 'qty_desc')>الأكثر توفرًا</option>
                            <option value="branch" @selected($sort === 'branch')>الفرع</option>
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
                            <th>الفرع</th>
                            <th>الوحدة</th>
                            <th>السعر</th>
                            <th>المتوفر</th>
                            <th class="text-end">طلب</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stocks as $stock)
                            <tr>
                                <td style="width:90px;">
                                    @if ($stock->product?->image)
                                        <img src="{{ asset('storage/' . $stock->product->image) }}"
                                            alt="{{ $stock->product->name }}" class="rounded border"
                                            style="width:64px;height:48px;object-fit:cover;">
                                    @else
                                        <span class="text-muted small">لا يوجد</span>
                                    @endif
                                </td>
                                <td>{{ $stock->product?->name ?? '-' }}</td>
                                <td>{{ $stock->product?->model ?: '-' }}</td>
                                <td>{{ $stock->branch?->name ?? '-' }}</td>
                                <td>{{ $stock->productUnit?->unit?->name ?? '-' }}</td>
                                <td>{{ number_format((float) $stock->selling_price, 2) }}</td>
                                <td>{{ number_format((float) $stock->quantity, 3) }}</td>
                                <td style="width:220px;">
                                    <form method="POST" action="{{ route('customer.wholesale.marketplace.order.store') }}"
                                        class="d-flex gap-2">
                                        @csrf
                                        <input type="hidden" name="stock_id" value="{{ $stock->id }}">
                                        <input type="number" name="quantity" value="1" min="1" step="1"
                                            class="form-control form-control-sm" style="max-width:84px;">
                                        <button class="btn btn-primary btn-sm">طلب</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">لا توجد منتجات مطابقة.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($stocks->hasPages())
                <div class="card-footer bg-white">
                    {{ $stocks->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
