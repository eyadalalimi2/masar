@extends('pos.layout.app')

@section('title', 'الكتالوج المحلي')

@section('content')
    <div class="hero-box reveal rv1">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h1 class="h4 mb-1">الكتالوج المحلي والتسعير</h1>
                <p class="mb-0 text-white-50">إدارة أسعار البيع وتفعيل المنتجات داخل المحل التجاري.</p>
            </div>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('pos.catalog.smart-refill.generate') }}">
                    @csrf
                    <button class="btn btn-outline-light btn-sm">توليد تنبيهات إعادة التعبئة</button>
                </form>
                <a href="{{ route('pos.marketplace.index') }}" class="btn btn-light btn-sm">السوق</a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" class="table-wrap reveal rv1 mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label mb-1">بحث</label><input type="text" name="search"
                    value="{{ request('search') }}" class="form-control"></div>
            <div class="col-md-2"><button class="btn btn-dark w-100">تطبيق</button></div>
        </div>
    </form>

    <div class="table-wrap reveal rv2">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>المنتج</th>
                        <th>الفرع</th>
                        <th>المخزون المحلي</th>
                        <th>متوسط البيع اليومي (14 يوم)</th>
                        <th>أيام متوقعة للنفاد</th>
                        <th>سعر الشراء</th>
                        <th>سعر البيع</th>
                        <th>الهامش</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $row)
                        @php
                            $insight = $insightsMap[(int) $row->id] ?? null;
                        @endphp
                        <tr>
                            <td>{{ $row->product?->name }} ({{ $row->productUnit?->unit?->name }})</td>
                            <td>{{ $row->branch?->name }}</td>
                            <td>{{ number_format((float) $row->local_quantity, 3) }}</td>
                            <td>{{ number_format((float) ($insight['avg_daily_sales_14d'] ?? 0), 3) }}</td>
                            <td>
                                @if (!is_null($insight['days_to_stockout'] ?? null))
                                    <span
                                        class="badge {{ (float) $insight['days_to_stockout'] <= 3 ? 'text-bg-warning' : 'text-bg-success' }}">
                                        {{ number_format((float) $insight['days_to_stockout'], 1) }} يوم
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ number_format((float) $row->purchase_price, 2) }}</td>
                            <td>
                                <form method="POST" action="{{ route('pos.catalog.price', $row) }}" class="d-flex gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" step="0.01" min="0" name="selling_price"
                                        value="{{ $row->selling_price }}" class="form-control form-control-sm"
                                        style="max-width:120px;">
                                    <button class="btn btn-sm btn-outline-primary">حفظ</button>
                                </form>
                            </td>
                            <td>{{ number_format((float) $row->selling_price - (float) $row->purchase_price, 2) }}</td>
                            <td>{{ $row->is_active ? 'مفعل' : 'معطل' }}</td>
                            <td>
                                <form method="POST" action="{{ route('pos.catalog.toggle', $row) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-outline-secondary">تبديل</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">لا توجد منتجات محلية بعد. أنشئ طلب توريد
                                أولًا.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $products->links() }}</div>
    </div>
@endsection
