@extends('agent.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">طلباتي</h1>
        <p class="text-muted mb-0">عرض وإدارة طلبات الوكيل</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="badge text-bg-warning">طلبات متأخرة: {{ number_format((int) ($delayedOrdersCount ?? 0)) }}</span>
        <span class="badge text-bg-info">تنبيهات اليوم: {{ number_format((int) ($delayAlertsTodayCount ?? 0)) }}</span>
        <form method="POST" action="{{ route('agent.orders.delay-alerts.generate') }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-warning">توليد تنبيهات التأخير</button>
        </form>
        <a href="{{ route('agent.orders.create') }}" class="btn btn-dark">إنشاء طلب</a>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="GET" action="{{ route('agent.orders.index') }}" class="row g-2 mb-3">
    <div class="col-md-4">
        <select name="status" class="form-select">
            <option value="">كل الحالات</option>
            @foreach (['pending' => 'قيد الانتظار', 'approved' => 'معتمد', 'assigned' => 'مُسند', 'out_for_delivery' => 'خرج للتوصيل', 'delivered' => 'تم التسليم', 'cancelled' => 'ملغي'] as $k => $label)
            <option value="{{ $k }}" @selected(request('status')===$k)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-dark w-100">تطبيق</button>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>رقم الطلب</th>
                <th>العميل</th>
                <th>النوع</th>
                <th>طريقة الدفع</th>
                <th>المندوب</th>
                <th>العناصر</th>
                <th>الإجمالي</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
            <tr>
                <td>#{{ $order->id }}</td>
                <td>{{ $order->customer?->name ?? ($order->consumer?->name ?? $order->customer_name) }}</td>
                <td>{{ $order->customer_type === 'b2b' ? 'عميل تجاري' : 'مستهلك فردي' }}</td>
                <td>{{ $order->payment_method_name ?: '-' }}</td>
                <td>{{ $order->distributor?->name ?: '-' }}</td>
                <td>{{ $order->items->count() }}</td>
                <td>{{ number_format((float) ($order->payable_total ?? $order->total_price), 2) }}</td>
                <td>
                    @switch($order->status)
                    @case('pending')
                    <span class="badge text-bg-secondary">قيد الانتظار</span>
                    @break

                    @case('approved')
                    <span class="badge text-bg-info">معتمد</span>
                    @break

                    @case('out_for_delivery')
                    <span class="badge text-bg-warning">خرج للتوصيل</span>
                    @break

                    @case('delivered')
                    <span class="badge text-bg-success">تم التسليم</span>
                    @break

                    @case('cancelled')
                    <span class="badge text-bg-danger">ملغي</span>
                    @break

                    @default
                    <span class="badge text-bg-light">{{ \App\Support\StatusLabel::order($order->status) }}</span>
                    @endswitch
                </td>
                <td>
                    <a href="{{ route('agent.orders.show', $order) }}"
                        class="btn btn-sm btn-outline-primary">تفاصيل</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center text-muted py-4">لا يوجد طلبات</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $orders->links() }}
@endsection