@extends('agent.layout.app')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">إدارة المخزون</h1>
            <p class="text-muted mb-0">إضافة وتعديل وصرف مخزون المنتجات للفروع مع تتبع كامل للحركة.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->has('inventory'))
        <div class="alert alert-danger">{{ $errors->first('inventory') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">عدد وحدات المنتجات</div>
                    <div class="h4 fw-bold mb-0">{{ number_format($totals['units_count']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">إجمالي المخزون</div>
                    <div class="h4 fw-bold mb-0">{{ number_format((float) $totals['total_stock'], 3) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">وحدات منخفضة المخزون</div>
                    <div class="h4 fw-bold mb-0">{{ number_format($totals['low_stock_count']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">إضافة مخزون</h2>
                    <form method="POST" action="{{ route('agent.inventory.add-stock') }}" class="d-grid gap-2">
                        @csrf
                        <select name="product_unit_id" class="form-select" required>
                            <option value="">اختر وحدة المنتج</option>
                            @foreach ($inventory as $row)
                                <option value="{{ $row->id }}">
                                    {{ $row->product?->name }} - {{ $row->unit?->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="number" name="quantity" step="0.001" min="0.001" class="form-control"
                            placeholder="الكمية المضافة" required>
                        <input type="number" name="low_stock_threshold" step="0.001" min="0" class="form-control"
                            placeholder="حد التنبيه (اختياري)">
                        <input type="text" name="note" class="form-control" placeholder="ملاحظة (اختياري)">
                        <button type="submit" class="btn btn-dark">إضافة</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">تعديل كمية المخزون</h2>
                    <form method="POST" action="{{ route('agent.inventory.adjust-stock') }}" class="d-grid gap-2">
                        @csrf
                        <select name="product_unit_id" class="form-select" required>
                            <option value="">اختر وحدة المنتج</option>
                            @foreach ($inventory as $row)
                                <option value="{{ $row->id }}">
                                    {{ $row->product?->name }} - {{ $row->unit?->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="number" name="new_quantity" step="0.001" min="0" class="form-control"
                            placeholder="الكمية الجديدة" required>
                        <input type="number" name="low_stock_threshold" step="0.001" min="0" class="form-control"
                            placeholder="حد التنبيه (اختياري)">
                        <input type="text" name="note" class="form-control" placeholder="سبب التعديل (اختياري)">
                        <button type="submit" class="btn btn-outline-dark">تحديث</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">توزيع على الفروع</h2>
                    <form method="POST" action="{{ route('agent.inventory.distribute') }}" class="d-grid gap-2">
                        @csrf
                        <select name="product_unit_id" class="form-select" required>
                            <option value="">اختر وحدة المنتج</option>
                            @foreach ($inventory as $row)
                                <option value="{{ $row->id }}">
                                    {{ $row->product?->name }} - {{ $row->unit?->name }}
                                </option>
                            @endforeach
                        </select>
                        <select name="branch_id" class="form-select" required>
                            <option value="">اختر الفرع</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="quantity" step="0.001" min="0.001" class="form-control"
                            placeholder="الكمية المصروفة" required>
                        <input type="text" name="note" class="form-control"
                            placeholder="ملاحظة (مثال: توزيع أسبوعي)">
                        <button type="submit" class="btn btn-outline-primary">صرف</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">المخزون الحالي</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th>الوحدة</th>
                            <th>الجملة</th>
                            <th>التجزئة المقترحة</th>
                            <th>المتاح</th>
                            <th>حد التنبيه</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inventory as $row)
                            @php
                                $isLow =
                                    (float) $row->low_stock_threshold > 0 &&
                                    (float) $row->stock_quantity <= (float) $row->low_stock_threshold;
                            @endphp
                            <tr>
                                <td>{{ $row->product?->name }}</td>
                                <td>{{ $row->unit?->name }}</td>
                                <td>{{ number_format((float) $row->wholesale_price, 2) }}</td>
                                <td>{{ number_format((float) $row->retail_price, 2) }}</td>
                                <td>{{ number_format((float) $row->stock_quantity, 3) }}</td>
                                <td>{{ number_format((float) $row->low_stock_threshold, 3) }}</td>
                                <td>
                                    @if ($isLow)
                                        <span class="badge text-bg-warning">منخفض</span>
                                    @else
                                        <span class="badge text-bg-success">جيد</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">لا توجد وحدات منتجات.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $inventory->links() }}</div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">سجل حركة المخزون</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>النوع</th>
                            <th>المنتج</th>
                            <th>الوحدة</th>
                            <th>الكمية</th>
                            <th>قبل</th>
                            <th>بعد</th>
                            <th>الفرع</th>
                            <th>ملاحظة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $movement)
                            <tr>
                                <td>{{ $movement->created_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    @if ($movement->movement_type === 'in')
                                        <span class="badge text-bg-success">إدخال</span>
                                    @elseif($movement->movement_type === 'out')
                                        <span class="badge text-bg-primary">توزيع</span>
                                    @else
                                        <span class="badge text-bg-secondary">تعديل</span>
                                    @endif
                                </td>
                                <td>{{ $movement->product?->name }}</td>
                                <td>{{ $movement->productUnit?->unit?->name }}</td>
                                <td>{{ number_format((float) $movement->quantity, 3) }}</td>
                                <td>{{ number_format((float) $movement->stock_before, 3) }}</td>
                                <td>{{ number_format((float) $movement->stock_after, 3) }}</td>
                                <td>{{ $movement->branch?->name ?? '-' }}</td>
                                <td>{{ $movement->note ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">لا توجد حركات مخزون بعد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
