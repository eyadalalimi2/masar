@extends('customer.layout.app')

@section('title', 'طلبات تاجر الجملة')

@section('content')
<div class="container-fluid py-2">
    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">إدارة الطلبات</h1>
            <p class="text-muted mb-0">طلبات الشراء الواردة إلى تاجر الجملة من العملاء.</p>
        </div>
    </div>

    <form method="GET" class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label mb-1">بحث</label>
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="اسم العميل أو الهاتف أو العنوان">
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\Orders\Order::STATUSES as $orderStatus)
                        <option value="{{ $orderStatus }}" @selected($status===$orderStatus)>
                            {{ \App\Support\StatusLabel::order($orderStatus) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-dark" type="submit">تطبيق</button>
                </div>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>العميل</th>
                        <th>الهاتف</th>
                        <th>عدد العناصر</th>
                        <th>الإجمالي</th>
                        <th>حالة الطلب</th>
                        <th>حالة الدفع</th>
                        <th>التاريخ</th>
                        <th>السجل</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->snapshot_customer_name ?: '-' }}</td>
                        <td dir="ltr">{{ $order->snapshot_customer_phone ?: '-' }}</td>
                        <td>{{ (int) $order->items_count }}</td>
                        <td>{{ number_format((float) ($order->payable_total ?? $order->total_price), 2) }}</td>
                        <td>{{ \App\Support\StatusLabel::order($order->status) }}</td>
                        <td>{{ \App\Support\StatusLabel::paymentStatus($order->latestPayment?->status) }}</td>
                        <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                        <td>
                            @php $historyRows = $order->statusHistories->take(5); @endphp
                            @if ($historyRows->isEmpty())
                            <span class="text-muted small">لا يوجد</span>
                            @else
                            <div class="small">
                                @foreach ($historyRows as $history)
                                <div>
                                    {{ \App\Support\StatusLabel::order($history->from_status) }} ->
                                    {{ \App\Support\StatusLabel::order($history->to_status) }}
                                    <span class="text-muted">({{ $history->created_at?->format('Y-m-d H:i') }})</span>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @if ($order->status === \App\Models\Orders\Order::STATUS_PENDING)
                                <form method="POST" action="{{ route('customer.wholesale.orders.status', $order) }}" class="m-0">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="btn btn-sm btn-success">اعتماد</button>
                                </form>
                                <form method="POST" action="{{ route('customer.wholesale.orders.status', $order) }}" class="m-0"
                                    onsubmit="return confirm('هل تريد إلغاء الطلب؟');">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">إلغاء</button>
                                </form>
                                @elseif ($order->status === \App\Models\Orders\Order::STATUS_APPROVED)
                                <form method="POST" action="{{ route('customer.wholesale.orders.status', $order) }}" class="m-0">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="out_for_delivery">
                                    <button type="submit" class="btn btn-sm btn-primary">بدء التوصيل</button>
                                </form>
                                <form method="POST" action="{{ route('customer.wholesale.orders.status', $order) }}" class="m-0"
                                    onsubmit="return confirm('هل تريد إلغاء الطلب؟');">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">إلغاء</button>
                                </form>
                                @elseif ($order->status === \App\Models\Orders\Order::STATUS_OUT_FOR_DELIVERY)
                                <form method="POST" action="{{ route('customer.wholesale.orders.status', $order) }}" class="m-0">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="delivered">
                                    <button type="submit" class="btn btn-sm btn-dark">تأكيد التسليم</button>
                                </form>
                                @else
                                <span class="text-muted small">لا يوجد</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">لا توجد طلبات مطابقة.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
        <div class="card-footer bg-white">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</div>
@endsection