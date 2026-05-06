@extends('pos.layout.app')

@section('title', 'طلبات التوريد')

@section('content')
<div class="hero-box reveal rv1">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="h4 mb-1">إدارة طلبات التوريد</h1>
            <p class="mb-0 text-white-50">متابعة حالة الطلبات الواردة من السوق حتى التسليم.</p>
        </div>
        <a href="{{ route('pos.marketplace.index') }}" class="btn btn-light btn-sm">إنشاء طلب جديد</a>
    </div>
</div>

<form method="GET" class="table-wrap reveal rv1 mb-3">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label mb-1">حالة الطلب</label>
            <select name="status" class="form-select">
                <option value="">الكل</option>
                @foreach (['pending', 'approved', 'assigned', 'out_for_delivery', 'delivered', 'cancelled'] as $status)
                <option value="{{ $status }}" @selected(request('status')===$status)>
                    {{ \App\Support\StatusLabel::order($status) }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-dark w-100">تطبيق</button></div>
    </div>
</form>

<div class="table-wrap reveal rv2">
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>الفرع</th>
                    <th>طريقة الدفع</th>
                    <th>الإجمالي</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td>#{{ $order->id }}</td>
                    <td>{{ $order->branch?->name }}</td>
                    <td>{{ $order->payment_method_name ?: '-' }}</td>
                    <td>{{ number_format((float) ($order->payable_total ?? $order->total_price), 2) }}</td>
                    <td>{{ \App\Support\StatusLabel::order($order->status) }}</td>
                    <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                    <td><a href="{{ route('pos.orders.show', $order) }}"
                            class="btn btn-sm btn-outline-primary">تفاصيل</a></td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">لا توجد طلبات توريد بعد.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $orders->links() }}</div>
</div>
@endsection