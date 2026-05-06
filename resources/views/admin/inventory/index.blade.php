@extends('admin.layout.app')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">مراقبة المخزون</h1>
            <p class="text-muted mb-0">عرض مخزون الفروع، تنبيهات النقص، وتتبع حركة المنتجات.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.inventory.index') }}" class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">الوكيل</label>
                    <select class="form-select" name="supplier_id" onchange="this.form.submit()">
                        <option value="">الكل</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((int) request('supplier_id') === (int) $supplier->id)>
                                {{ $supplier->business_name ?: $supplier->owner_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">الفرع</label>
                    <select class="form-select" name="branch_id">
                        <option value="">الكل</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) request('branch_id') === (int) $branch->id)>
                                {{ $branch->name }}
                                @if ($branch->supplier)
                                    ({{ $branch->supplier->business_name ?: $branch->supplier->owner_name }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check me-3">
                        <input class="form-check-input" type="checkbox" value="1" id="lowStockOnly"
                            name="low_stock_only" @checked(request()->boolean('low_stock_only'))>
                        <label class="form-check-label" for="lowStockOnly">تنبيهات النقص فقط</label>
                    </div>
                    <button class="btn btn-primary me-2" type="submit">تطبيق</button>
                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary">إعادة ضبط</a>
                </div>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">عدد عناصر المخزون</div>
                    <div class="h4 mb-0 fw-bold">{{ number_format($stats['items_count']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">عدد الفروع المغطاة</div>
                    <div class="h4 mb-0 fw-bold">{{ number_format($stats['branches_count']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">إجمالي الكميات</div>
                    <div class="h4 mb-0 fw-bold">{{ number_format((float) $stats['total_quantity'], 3) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">تنبيهات نقص المخزون</div>
                    <div class="h4 mb-0 fw-bold text-danger">{{ number_format($stats['low_stock_count']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">مخزون الفروع</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>الوكيل</th>
                            <th>الفرع</th>
                            <th>المنتج</th>
                            <th>الوحدة</th>
                            <th>الكمية</th>
                            <th>حد التنبيه</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stocks as $stock)
                            @php
                                $threshold = (float) ($stock->threshold_value ?? 0);
                                $quantity = (float) $stock->quantity;
                                $isLow = $threshold > 0 && $quantity <= $threshold;
                            @endphp
                            <tr>
                                <td>{{ $stock->branch?->supplier?->business_name ?: $stock->branch?->supplier?->owner_name ?: '-' }}
                                </td>
                                <td>{{ $stock->branch?->name ?? '-' }}</td>
                                <td>{{ $stock->product?->name ?? '-' }}</td>
                                <td>{{ $stock->productUnit?->unit?->name ?? '-' }}</td>
                                <td>{{ number_format($quantity, 3) }}</td>
                                <td>{{ number_format($threshold, 3) }}</td>
                                <td>
                                    @if ($isLow)
                                        <span class="badge bg-danger">منخفض</span>
                                    @else
                                        <span class="badge bg-success">آمن</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">لا توجد بيانات مخزون مطابقة.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $stocks->links() }}
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">آخر حركات المخزون</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>الوقت</th>
                            <th>الفرع</th>
                            <th>المنتج</th>
                            <th>النوع</th>
                            <th>الكمية</th>
                            <th>قبل</th>
                            <th>بعد</th>
                            <th>الطلب / المندوب</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $movement)
                            <tr>
                                <td>{{ $movement->created_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $movement->branch?->name ?? '-' }}</td>
                                <td>{{ $movement->product?->name ?? '-' }}</td>
                                <td>{{ $movement->movement_type }}</td>
                                <td>{{ number_format((float) $movement->quantity, 3) }}</td>
                                <td>{{ number_format((float) $movement->stock_before, 3) }}</td>
                                <td>{{ number_format((float) $movement->stock_after, 3) }}</td>
                                <td>
                                    @if ($movement->order)
                                        طلب #{{ $movement->order->id }}
                                    @endif
                                    @if ($movement->distributor)
                                        <div class="small text-muted">{{ $movement->distributor->name }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">لا توجد حركات مخزون بعد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
