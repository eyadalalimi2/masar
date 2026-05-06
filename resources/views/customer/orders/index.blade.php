@extends('customer.layout.app')

@section('title', 'طلبات العميل')

@section('content')
    <div class="container-fluid py-2">
        <form method="GET" class="card border-0 shadow-sm mb-3">
            <div class="card-body row g-2">
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="">كل الحالات</option>
                        <option value="pending" @selected($status === 'pending')>قيد الانتظار</option>
                        <option value="approved" @selected($status === 'approved')>معتمد</option>
                        <option value="out_for_delivery" @selected($status === 'out_for_delivery')>خرج للتوصيل</option>
                        <option value="delivered" @selected($status === 'delivered')>مسلّم</option>
                        <option value="cancelled" @selected($status === 'cancelled')>ملغي</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select name="seller_type" class="form-select">
                        <option value="">كل البائعين</option>
                        <option value="supplier" @selected($sellerType === 'supplier')>مورد</option>
                        <option value="branch" @selected($sellerType === 'branch')>فرع</option>
                        <option value="distributor" @selected($sellerType === 'distributor')>مندوب</option>
                        <option value="customer" @selected($sellerType === 'customer')>عميل</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-outline-dark" type="submit">تصفية</button>
                </div>
            </div>
        </form>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>البائع</th>
                            <th>عدد العناصر</th>
                            <th>الإجمالي</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td>{{ $order->id }}</td>
                                <td>{{ \App\Support\StatusLabel::sellerType($order->seller_type) }}</td>
                                <td>{{ $order->items->count() }}</td>
                                <td>{{ number_format((float) ($order->payable_total ?? $order->total_price), 2) }}</td>
                                <td>{{ \App\Support\StatusLabel::order($order->status) }}</td>
                                <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">لا توجد طلبات مطابقة.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $orders->links() }}</div>
    </div>
@endsection
