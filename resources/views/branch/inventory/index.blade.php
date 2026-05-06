@extends('branch.layout.app')

@section('title', 'مخزون الفرع')

@section('content')
    <div class="container-fluid py-2">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h4 fw-bold mb-1">إدارة مخزون الفرع</h1>
                <p class="text-muted mb-0">الوارد من الوكيل والصادر للعملاء وتحديث سعر البيع المحلي.</p>
            </div>
            <form method="POST" action="{{ route('branch.inventory.auto-reorder') }}">
                @csrf
                <button class="btn btn-outline-dark">إنشاء طلبات توريد تلقائية للأصناف المنخفضة</button>
            </form>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        @if ($syncedInbound > 0)
            <div class="alert alert-info">تمت مزامنة {{ $syncedInbound }} حركة وارد من الوكيل إلى مخزون الفرع.</div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">عدد الأصناف</div>
                        <div class="h4 mb-0">{{ number_format($totals['total_items']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">إجمالي الكمية</div>
                        <div class="h4 mb-0">{{ number_format((float) $totals['total_quantity'], 3) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">أصناف نفدت</div>
                        <div class="h4 mb-0">{{ number_format($totals['low_stock_count']) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">المخزون المحلي والتسعير</h2>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>المنتج</th>
                                <th>الوحدة</th>
                                <th>الكمية</th>
                                <th>سعر الجملة</th>
                                <th>سعر الوكيل المقترح</th>
                                <th>سعر بيع الفرع</th>
                                <th>هامش تقريبي</th>
                                <th>تحديث الكمية</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stocks as $row)
                                @php
                                    $wholesale = (float) ($row->productUnit?->wholesale_price ?? 0);
                                    $selling = (float) ($row->selling_price ?? 0);
                                    $margin = $selling - $wholesale;
                                @endphp
                                <tr>
                                    <td>{{ $row->product?->name }}</td>
                                    <td>{{ $row->productUnit?->unit?->name }}</td>
                                    <td>{{ number_format((float) $row->quantity, 3) }}</td>
                                    <td>{{ number_format($wholesale, 2) }}</td>
                                    <td>{{ number_format((float) ($row->productUnit?->retail_price ?? 0), 2) }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('branch.inventory.update-price') }}"
                                            class="d-flex gap-2">
                                            @csrf
                                            <input type="hidden" name="product_unit_id"
                                                value="{{ $row->product_unit_id }}">
                                            <input type="number" step="0.01" min="0" name="selling_price"
                                                class="form-control form-control-sm"
                                                value="{{ number_format((float) ($row->selling_price ?? 0), 2, '.', '') }}"
                                                style="max-width:120px;">
                                            <button class="btn btn-sm btn-outline-primary" type="submit">حفظ</button>
                                        </form>
                                    </td>
                                    <td>
                                        <span class="badge {{ $margin >= 0 ? 'text-bg-success' : 'text-bg-danger' }}">
                                            {{ number_format($margin, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('branch.inventory.update-stock') }}"
                                            class="d-flex gap-2">
                                            @csrf
                                            <input type="hidden" name="product_unit_id"
                                                value="{{ $row->product_unit_id }}">
                                            <input type="number" step="0.001" min="0" name="quantity"
                                                class="form-control form-control-sm"
                                                value="{{ number_format((float) $row->quantity, 3, '.', '') }}"
                                                style="max-width:120px;">
                                            <button class="btn btn-sm btn-dark" type="submit">تحديث</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">لا يوجد مخزون محلي حتى الآن. سيتم
                                        تعبئته تلقائيًا عند استلام وارد من الوكيل.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">حركة المخزون</h2>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>التاريخ</th>
                                <th>النوع</th>
                                <th>المنتج</th>
                                <th>الوحدة</th>
                                <th>الكمية</th>
                                <th>قبل</th>
                                <th>بعد</th>
                                <th>الطلب</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($movements as $mv)
                                <tr>
                                    <td>{{ $mv->created_at?->format('Y-m-d H:i') }}</td>
                                    <td>{{ $mv->movement_type }}</td>
                                    <td>{{ $mv->product?->name }}</td>
                                    <td>{{ $mv->productUnit?->unit?->name }}</td>
                                    <td>{{ number_format((float) $mv->quantity, 3) }}</td>
                                    <td>{{ number_format((float) $mv->stock_before, 3) }}</td>
                                    <td>{{ number_format((float) $mv->stock_after, 3) }}</td>
                                    <td>{{ $mv->order_id ? '#' . $mv->order_id : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-3">لا توجد حركة مخزون بعد.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
