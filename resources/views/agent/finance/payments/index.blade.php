@extends('agent.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">مدفوعات {{ $sectionLabel }}</h1>
        <p class="text-muted mb-0">عرض عمليات الدفع والتحصيل حسب القسم</p>
    </div>
    <a href="{{ route($createRoute) }}" class="btn btn-dark">تسجيل دفع</a>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-2 d-flex gap-2 flex-wrap">
        <a href="{{ route('agent.payments.commercial-stores.index') }}"
            class="btn btn-sm {{ ($sectionRoute ?? '') === 'agent.payments.commercial-stores.index' ? 'btn-dark' : 'btn-outline-dark' }}">
            مدفوعات المحلات التجارية
        </a>
        <a href="{{ route('agent.payments.workshops.index') }}"
            class="btn btn-sm {{ ($sectionRoute ?? '') === 'agent.payments.workshops.index' ? 'btn-dark' : 'btn-outline-dark' }}">
            مدفوعات الورش
        </a>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="table-responsive">
    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>الطلب</th>
                <th>المبلغ</th>
                <th>نوع الدفع</th>
                <th>الحالة</th>
                <th>المندوب</th>
                <th>التاريخ</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payments as $payment)
            <tr>
                <td>#{{ $payment->order_id }}</td>
                <td>{{ number_format((float) $payment->amount, 2) }}</td>
                <td>{{ $payment->payment_type === 'cash' ? 'كاش' : 'آجل' }}</td>
                <td>{{ \App\Support\StatusLabel::paymentStatus($payment->status) }}</td>
                <td>{{ $payment->order?->distributor?->name ?: '-' }}</td>
                <td>{{ optional($payment->paid_at)->format('Y-m-d H:i') ?: '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-muted py-4">لا توجد مدفوعات</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $payments->links() }}
@endsection