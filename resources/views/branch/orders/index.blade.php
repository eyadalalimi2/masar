@extends('branch.layout.app')

@section('title', 'طلبات الفرع')

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
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 fw-bold mb-0">طلبات {{ $branch->name }}</h1>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <span class="badge text-bg-warning">طلبات متأخرة:
                {{ number_format((int) ($delayedOrdersCount ?? 0)) }}</span>
            <form method="POST" action="{{ route('branch.orders.delay-alerts.generate') }}">
                @csrf
                <button class="btn btn-sm btn-outline-warning">توليد تنبيهات التأخير</button>
            </form>
        </div>
    </div>

    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form class="row g-2 mb-3" method="GET" action="{{ route('branch.orders.index') }}">
        <div class="col-md-4">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">كل الحالات</option>
                @foreach (['pending', 'approved', 'assigned', 'out_for_delivery', 'delivered', 'cancelled'] as $status)
                <option value="{{ $status }}" @selected(request('status')===$status)>
                    {{ $statusLabels[$status] ?? $status }}
                </option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="table-responsive bg-white border rounded-3">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>رقم الطلب</th>
                    <th>العميل</th>
                    <th>طريقة الدفع</th>
                    <th>الحالة</th>
                    <th>الإجمالي</th>
                    <th>المندوب</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                <tr>
                    <td>#{{ $order->id }}</td>
                    <td>{{ $order->customer?->name ?? ($order->consumer?->name ?? $order->customer_name) }}</td>
                    <td>{{ $order->payment_method_name ?: '-' }}</td>
                    <td>{{ \App\Support\StatusLabel::order($order->status) }}</td>
                    <td>{{ number_format((float) ($order->payable_total ?? $order->total_price), 2) }}</td>
                    <td>{{ $order->distributor?->name ?? '-' }}</td>
                    <td>
                        <a href="{{ route('branch.orders.show', $order) }}"
                            class="btn btn-sm btn-outline-primary">تفاصيل</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">لا توجد طلبات لهذا الفرع.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $orders->links() }}</div>
</div>
@endsection