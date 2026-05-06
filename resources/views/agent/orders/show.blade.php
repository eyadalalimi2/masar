@extends('agent.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 fw-bold mb-0">تفاصيل الطلب #{{ $order->id }}</h1>
    <a href="{{ route('agent.orders.index') }}" class="btn btn-outline-secondary">رجوع</a>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

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
                <div class="fw-bold mb-2">إدارة الطلب</div>
                <div>الفرع: {{ $order->branch?->name ?: '-' }}</div>
                <div>المندوب الحالي: {{ $order->distributor?->name ?: '-' }}</div>
                <div>الإجمالي: {{ number_format((float) ($order->payable_total ?? $order->total_price), 2) }}</div>
                <div>طريقة الدفع: {{ $order->payment_method_name ?: '-' }}</div>
                <div>رقم الحساب: {{ $order->payment_account_number ?: '-' }}</div>
                <div>اسم الحساب: {{ $order->payment_account_name ?: '-' }}</div>
                <div>ملاحظة الدفع: {{ $order->payment_note ?: '-' }}</div>

                <hr>

                <form method="POST" action="{{ route('agent.orders.assignDistributor', $order) }}" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <label class="form-label">تعيين مندوب</label>
                    <div class="d-flex gap-2">
                        <select name="distributor_id" class="form-select">
                            <option value="">بدون</option>
                            @foreach ($distributors as $distributor)
                            <option value="{{ $distributor->id }}" @selected($order->distributor_id == $distributor->id)>
                                {{ $distributor->name }}
                            </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-outline-dark">حفظ</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('agent.orders.smart-dispatch', $order) }}" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline-primary">توزيع ذكي تلقائي</button>
                </form>

                <form method="POST" action="{{ route('agent.orders.status', $order) }}">
                    @csrf
                    @method('PATCH')
                    <label class="form-label">تغيير الحالة</label>
                    <div class="d-flex gap-2">
                        <select name="status" class="form-select">
                            @foreach (['approved' => 'معتمد', 'assigned' => 'مُسند', 'out_for_delivery' => 'خرج للتوصيل', 'delivered' => 'تم التسليم', 'cancelled' => 'ملغي'] as $k => $label)
                            <option value="{{ $k }}" @selected($order->status === $k)>{{ $label }}
                            </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-dark">تحديث</button>
                    </div>
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
                        <th>الوحدة</th>
                        <th>المواصفة</th>
                        <th>الكمية</th>
                        <th>سعر الوحدة</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $item->product?->name }}</td>
                        <td>{{ $item->productUnit?->unit?->name ?: '-' }}</td>
                        <td>
                            @if ($item->productVariant?->variantValue)
                            {{ $item->productVariant->variantValue?->type?->name }}:
                            {{ $item->productVariant->variantValue?->value }}
                            @else
                            -
                            @endif
                        </td>
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