@extends('pos.layout.app')

@section('title', 'تفاصيل الطلب')

@section('content')
<div class="hero-box reveal rv1">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h1 class="h4 mb-1">تفاصيل الطلب #{{ $order->id }}</h1>
            <p class="mb-0 text-white-50">عرض جميع عناصر الطلب والحالة التشغيلية الحالية.</p>
        </div>
        <a href="{{ route('pos.orders.index') }}" class="btn btn-light btn-sm">رجوع</a>
    </div>
</div>

<div class="table-wrap reveal rv1 mb-3">
    <div class="card-body">
        <div>الفرع: {{ $order->branch?->name }}</div>
        <div>المورد: {{ $order->supplier?->business_name ?? $order->supplier?->owner_name }}</div>
        <div>الحالة: {{ \App\Support\StatusLabel::order($order->status) }}</div>
        <div>التاريخ: {{ $order->created_at?->format('Y-m-d H:i') }}</div>
        <div>المندوب: {{ $order->distributor?->name ?? '-' }}</div>
        <div>طريقة الدفع: {{ $order->payment_method_name ?: '-' }}</div>
        <div>رقم الحساب: {{ $order->payment_account_number ?: '-' }}</div>
        <div>اسم الحساب: {{ $order->payment_account_name ?: '-' }}</div>
        <div>ملاحظة الدفع: {{ $order->payment_note ?: '-' }}</div>
    </div>
</div>

<div class="table-wrap reveal rv2">
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>المنتج</th>
                    <th>الوحدة</th>
                    <th>الكمية</th>
                    <th>السعر</th>
                    <th>الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->product?->name }}</td>
                    <td>{{ $item->productUnit?->unit?->name }}</td>
                    <td>{{ number_format((float) $item->quantity, 3) }}</td>
                    <td>{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td>{{ number_format((float) $item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection