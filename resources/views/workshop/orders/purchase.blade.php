@extends('workshop.layout.app')

@section('content')
@if (session('status'))
<div class="alert alert-success">{{ session('status') }}</div>
@endif

<h1 class="workshop-section-title">طلبات الشراء</h1>
<p class="workshop-section-subtitle">إدارة طلبات شراء المنتجات من الفروع لدعم تنفيذ خدمات الورشة.</p>

<div class="workshop-panel mb-3">
    <h2 class="h6 fw-bold mb-3">إنشاء طلب شراء</h2>
    <form action="{{ route('workshop.orders.purchase.store') }}" method="POST" class="row g-2">
        @csrf
        <div class="col-md-4">
            <input type="text" name="supplier_branch_name" class="form-control" placeholder="اسم الفرع المورد"
                required>
        </div>
        <div class="col-md-3">
            <input type="number" step="0.01" min="0" name="total_amount" class="form-control"
                placeholder="القيمة الإجمالية" required>
        </div>
        <div class="col-md-5">
            <input type="text" name="notes" class="form-control" placeholder="ملاحظات الطلب">
        </div>
        <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary btn-sm">إرسال الطلب</button>
        </div>
    </form>
</div>

<div class="workshop-panel">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>رقم الطلب</th>
                    <th>الفرع</th>
                    <th>طريقة الدفع</th>
                    <th>القيمة</th>
                    <th>الحالة</th>
                    <th>العناصر</th>
                    <th>تحديث الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->supplierBranch?->name ?: $order->supplier_branch_name }}</td>
                    <td>
                        <div>{{ $order->payment_method_name ?: '-' }}</div>
                        @if ($order->payment_account_number || $order->payment_account_name)
                        <div class="small text-muted">
                            {{ $order->payment_account_name ?: '-' }}
                            ({{ $order->payment_account_number ?: '-' }})
                        </div>
                        @endif
                    </td>
                    <td>{{ number_format((float) $order->total_amount, 0) }} ر.ي</td>
                    <td><span
                            class="workshop-badge">{{ \App\Support\StatusLabel::workshopPurchaseOrder($order->status) }}</span>
                    </td>
                    <td>
                        @if ($order->items->isNotEmpty())
                        <ul class="mb-0 small" style="padding-right: 16px;">
                            @foreach ($order->items as $item)
                            <li>
                                {{ $item->product?->name ?: 'منتج' }}
                                ({{ $item->productUnit?->unit?->name ?: '-' }})
                                × {{ number_format((float) $item->quantity, 3) }}
                            </li>
                            @endforeach
                        </ul>
                        @else
                        <span class="text-muted small">بدون عناصر تفصيلية</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('workshop.orders.purchase.status', $order) }}" method="POST"
                            class="d-flex gap-1">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="form-select form-select-sm">
                                <option value="pending" @selected($order->status === 'pending')>قيد الانتظار</option>
                                <option value="approved" @selected($order->status === 'approved')>معتمد</option>
                                <option value="in_transit" @selected($order->status === 'in_transit')>قيد النقل</option>
                                <option value="received" @selected($order->status === 'received')>تم الاستلام</option>
                                <option value="cancelled" @selected($order->status === 'cancelled')>ملغي</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary">حفظ</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">لا توجد طلبات شراء بعد.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection