@extends('admin.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">تفاصيل الطلب #{{ $order->id }}</h1>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">رجوع</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="fw-bold mb-2">بيانات العميل</div>
                    <div>الاسم: {{ $order->customer?->name ?? ($order->consumer?->name ?? $order->customer_name) }}</div>
                    <div>الهاتف: {{ $order->customer?->phone ?? ($order->consumer?->phone ?? $order->customer_phone) }}
                    </div>
                    <div>العنوان:
                        {{ $order->customer?->address ?? ($order->consumer?->address ?? $order->customer_address) }}
                    </div>
                    <div>النوع: {{ $order->customer_type === 'b2b' ? 'عميل تجاري' : 'مستهلك فردي' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="fw-bold mb-2">بيانات الطلب</div>
                    <div>الوكيل: {{ $order->supplier?->owner_name }}</div>
                    <div>الفرع: {{ $order->branch?->name ?: '-' }}</div>
                    <div>المندوب: {{ $order->distributor?->name ?: '-' }}</div>
                    <div>الحالة: {{ \App\Support\StatusLabel::order($order->status) }}</div>
                    <div>الإجمالي الأساسي: {{ number_format((float) $order->total_price, 2) }}</div>
                    <div>نسبة العمولة: {{ number_format((float) ($order->commission_percent ?? 0), 2) }}%</div>
                    <div>قيمة العمولة: {{ number_format((float) ($order->commission_value ?? 0), 2) }}</div>
                    <div>رسوم المنصة:
                        {{ number_format((float) (($order->platform_service_fee ?? 0) + ($order->platform_fixed_fee ?? 0)), 2) }}
                    </div>
                    <div class="fw-bold">الإجمالي بعد العمولة:
                        {{ number_format((float) ($order->payable_total ?? $order->total_price), 2) }}</div>

                    <hr>
                    <form method="POST" action="{{ route('admin.orders.smart-dispatch', $order) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-outline-primary">توزيع ذكي تلقائي</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="fw-bold mb-3">عناصر الطلب</div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
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
@endsection
