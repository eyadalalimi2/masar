@extends('pos.layout.app')

@section('title', 'المبيعات')

@section('content')
<div class="hero-box reveal rv1">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="h4 mb-1">البيع للمستهلك</h1>
            <p class="mb-0 text-white-50">تسجيل عمليات البيع اليومية ومتابعة الربحية.</p>
        </div>
        <a href="{{ route('pos.reports.index') }}" class="btn btn-light btn-sm">التقارير</a>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
<div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="table-wrap reveal rv1 mb-3">
    <div class="card-body">
        <form method="POST" action="{{ route('pos.sales.store') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label mb-1">المنتج</label>
                <select name="pos_local_product_id" class="form-select" required>
                    <option value="">اختر</option>
                    @foreach ($saleProducts as $row)
                    <option value="{{ $row->id }}">{{ $row->product?->name }}
                        ({{ $row->productUnit?->unit?->name }})
                        - {{ number_format((float) $row->selling_price, 2) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label mb-1">الكمية</label><input type="number" step="0.001"
                    min="0.001" name="quantity" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label mb-1">القناة</label><select name="sale_channel"
                    class="form-select">
                    <option value="offline">غير متصل</option>
                    <option value="online">متصل</option>
                </select></div>
            <div class="col-md-2"><label class="form-label mb-1">نوع الخصم</label><select name="discount_type"
                    class="form-select">
                    <option value="">بدون</option>
                    <option value="percent">%</option>
                    <option value="fixed">مبلغ</option>
                </select></div>
            <div class="col-md-2"><label class="form-label mb-1">قيمة الخصم</label><input type="number" step="0.01"
                    min="0" name="discount_value" class="form-control"></div>
            <div class="col-md-2"><label class="form-label mb-1">اسم العميل</label><input type="text"
                    name="customer_name" class="form-control"></div>
            <div class="col-md-2"><label class="form-label mb-1">الهاتف</label><input type="text"
                    name="customer_phone" class="form-control"></div>
            <div class="col-md-1"><button class="btn btn-primary w-100">بيع</button></div>
            <div class="col-md-3"><input type="text" name="campaign_code" class="form-control"
                    placeholder="كود الحملة (اختياري)"></div>
            <div class="col-12"><input type="text" name="note" class="form-control"
                    placeholder="ملاحظة (اختياري)"></div>
        </form>
    </div>
</div>

<div class="table-wrap reveal rv1 mb-3">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-3">بيع سريع</h2>
        <form method="POST" action="{{ route('pos.sales.quick.store') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-3"><label class="form-label mb-1">اسم المنتج</label><input type="text"
                    name="product_name" class="form-control" required></div>
            <div class="col-md-1"><label class="form-label mb-1">الكمية</label><input type="number" step="0.001"
                    min="0.001" name="quantity" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label mb-1">سعر البيع/وحدة</label><input type="number"
                    step="0.01" min="0.01" name="unit_price" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label mb-1">سعر الشراء/وحدة</label><input type="number"
                    step="0.01" min="0" name="purchase_unit_price" class="form-control"></div>
            <div class="col-md-1"><label class="form-label mb-1">القناة</label><select name="sale_channel"
                    class="form-select">
                    <option value="offline">غير متصل</option>
                    <option value="online">متصل</option>
                </select></div>
            <div class="col-md-1"><label class="form-label mb-1">الخصم</label><select name="discount_type"
                    class="form-select">
                    <option value="">-</option>
                    <option value="percent">%</option>
                    <option value="fixed">مبلغ</option>
                </select></div>
            <div class="col-md-1"><label class="form-label mb-1">القيمة</label><input type="number" step="0.01"
                    min="0" name="discount_value" class="form-control"></div>
            <div class="col-md-1"><label class="form-label mb-1">العميل</label><input type="text"
                    name="customer_name" class="form-control"></div>
            <div class="col-md-1"><label class="form-label mb-1">الهاتف</label><input type="text"
                    name="customer_phone" class="form-control"></div>
            <div class="col-md-1"><button class="btn btn-outline-primary w-100">تسجيل</button></div>
            <div class="col-md-2"><input type="text" name="campaign_code" class="form-control"
                    placeholder="كود حملة"></div>
            <div class="col-12"><input type="text" name="note" class="form-control"
                    placeholder="ملاحظة سريعة"></div>
        </form>
    </div>
</div>

<div class="table-wrap reveal rv2">
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>المنتج</th>
                    <th>الكمية</th>
                    <th>الإجمالي قبل الخصم</th>
                    <th>الخصم</th>
                    <th>القيمة</th>
                    <th>الربح</th>
                    <th>الحملة</th>
                    <th>القناة</th>
                    <th>العميل</th>
                    <th>الوقت</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $row)
                <tr>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ number_format((float) $row->quantity, 3) }}</td>
                    <td>{{ number_format((float) ($row->gross_amount ?? $row->total_amount), 2) }}</td>
                    <td>{{ number_format((float) ($row->discount_amount ?? 0), 2) }}</td>
                    <td>{{ number_format((float) $row->total_amount, 2) }}</td>
                    <td>{{ number_format((float) $row->profit_amount, 2) }}</td>
                    <td>{{ $row->campaign_code ?: '-' }}</td>
                    <td>{{ $row->sale_channel === 'online' ? 'متصل' : 'غير متصل' }}</td>
                    <td>{{ $row->customer_name ?: '-' }}</td>
                    <td>{{ $row->sold_at?->format('Y-m-d H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">لا توجد مبيعات بعد.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $sales->links() }}</div>
</div>
@endsection