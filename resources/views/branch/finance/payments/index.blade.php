@extends('branch.layout.app')

@section('title', 'مدفوعات الفرع')

@section('content')
    <div class="container-fluid py-2">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <h1 class="h4 fw-bold mb-0">مدفوعات {{ $branch->name }}</h1>
            <div class="d-flex gap-2">
                <a href="{{ route('branch.payments.create') }}" class="btn btn-dark">تسجيل دفعة</a>
                <a href="{{ route('branch.dashboard') }}" class="btn btn-outline-secondary">لوحة الفرع</a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="table-responsive bg-white border rounded-3">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>الطلب</th>
                        <th>العميل</th>
                        <th>المبلغ</th>
                        <th>النوع</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>#{{ $payment->order_id }}</td>
                            <td>{{ $payment->order?->customer_name }}</td>
                            <td>{{ number_format((float) $payment->amount, 2) }}</td>
                            <td>{{ $payment->payment_type === 'cash' ? 'كاش' : 'آجل' }}</td>
                            <td>{{ \App\Support\StatusLabel::paymentStatus($payment->status) }}</td>
                            <td>{{ optional($payment->paid_at)->format('Y-m-d H:i') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">لا توجد عمليات دفع للفرع.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $payments->links() }}</div>
    </div>
@endsection
