@extends('customer.layout.app')

@section('title', 'إدارة العملاء')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">إدارة العملاء</h1>
            <p class="text-muted mb-0">العملاء الذين اشتروا من تاجر الجملة.</p>
        </div>
    </div>

    <form method="GET" class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-10">
                    <label class="form-label mb-1">بحث</label>
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="اسم العميل أو الهاتف أو العنوان">
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
                        <th>العميل</th>
                        <th>الهاتف</th>
                        <th>العنوان</th>
                        <th>عدد الطلبات</th>
                        <th>إجمالي المشتريات</th>
                        <th>آخر طلب</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($buyers as $buyer)
                    <tr>
                        <td>{{ $buyer->customer_name ?: '-' }}</td>
                        <td dir="ltr">{{ $buyer->customer_phone ?: '-' }}</td>
                        <td>{{ $buyer->customer_address ?: '-' }}</td>
                        <td>{{ (int) $buyer->orders_count }}</td>
                        <td>{{ number_format((float) $buyer->total_spent, 2) }}</td>
                        <td>{{ $buyer->last_order_at ? \Illuminate\Support\Carbon::parse($buyer->last_order_at)->format('Y-m-d H:i') : '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">لا توجد بيانات عملاء حتى الآن.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($buyers->hasPages())
        <div class="card-footer bg-white">
            {{ $buyers->links() }}
        </div>
        @endif
    </div>
</div>
@endsection