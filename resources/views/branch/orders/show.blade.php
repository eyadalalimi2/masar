@extends('branch.layout.app')

@section('title', 'تفاصيل طلب الفرع')

@section('content')
@php
$statusLabels = [
'pending' => 'قيد الانتظار',
'approved' => 'معتمد',
'assigned' => 'مُسند',
'out_for_delivery' => 'خرج للتوصيل',
'delivered' => 'تم التسليم',
'cancelled' => 'ملغي',
];
@endphp

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 fw-bold mb-0">تفاصيل الطلب #{{ $order->id }}</h1>
        <a href="{{ route('branch.orders.index') }}" class="btn btn-outline-secondary">رجوع</a>
    </div>

    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div>العميل: {{ $order->customer?->name ?? ($order->consumer?->name ?? $order->customer_name) }}</div>
            <div>الهاتف: {{ $order->customer?->phone ?? ($order->consumer?->phone ?? $order->customer_phone) }}</div>
            <div>العنوان: {{ $order->customer?->address ?? ($order->consumer?->address ?? $order->customer_address) }}
            </div>
            <div>الحالة الحالية: {{ \App\Support\StatusLabel::order($order->status) }}</div>
            <div>المندوب الحالي: {{ $order->distributor?->name ?? 'غير معيّن' }}</div>
            <div>الإجمالي: {{ number_format((float) ($order->payable_total ?? $order->total_price), 2) }}</div>
            <div>طريقة الدفع: {{ $order->payment_method_name ?: '-' }}</div>
            <div>رقم الحساب: {{ $order->payment_account_number ?: '-' }}</div>
            <div>اسم الحساب: {{ $order->payment_account_name ?: '-' }}</div>
            <div>ملاحظة الدفع: {{ $order->payment_note ?: '-' }}</div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('branch.orders.assign-distributor', $order) }}"
                class="d-flex gap-2 flex-wrap mb-3">
                @csrf
                @method('PATCH')
                <select name="distributor_id" class="form-select" style="max-width: 280px;">
                    <option value="">بدون مندوب</option>
                    @foreach ($distributors as $distributor)
                    <option value="{{ $distributor->id }}" @selected((int) ($order->distributor_id ?? 0) === (int) $distributor->id)>
                        {{ $distributor->name }} ({{ $distributor->phone }})
                    </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-outline-primary">تعيين مندوب</button>
            </form>

            <form method="POST" action="{{ route('branch.orders.smart-dispatch', $order) }}" class="mb-3">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline-dark">توزيع ذكي تلقائي</button>
            </form>

            <form method="POST" action="{{ route('branch.orders.status', $order) }}" class="d-flex gap-2 flex-wrap">
                @csrf
                @method('PATCH')
                <select name="status" class="form-select" style="max-width: 240px;">
                    <option value="approved" @selected($order->status === 'approved')>معتمد</option>
                    <option value="assigned" @selected($order->status === 'assigned')>مُسند</option>
                    <option value="out_for_delivery" @selected($order->status === 'out_for_delivery')>خرج للتوصيل</option>
                    <option value="delivered" @selected($order->status === 'delivered')>تم التسليم</option>
                    <option value="cancelled" @selected($order->status === 'cancelled')>ملغي</option>
                </select>
                <button type="submit" class="btn btn-dark">تحديث الحالة</button>
            </form>

            <form method="POST" action="{{ route('branch.orders.reject', $order) }}"
                class="d-flex gap-2 flex-wrap mt-2">
                @csrf
                @method('PATCH')
                <input type="text" name="reason" class="form-control" style="max-width: 320px;"
                    placeholder="سبب الرفض (اختياري)">
                <button type="submit" class="btn btn-outline-danger">رفض الطلب</button>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>المنتج</th>
                            <th>الكمية</th>
                            <th>سعر الوحدة</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                        <tr>
                            <td>{{ $item->product?->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td>{{ number_format((float) $item->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection