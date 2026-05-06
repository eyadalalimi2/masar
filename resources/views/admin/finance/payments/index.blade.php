@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">المدفوعات</h1>
        <p class="text-muted mb-0">عرض جميع عمليات الدفع والتحصيل</p>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="GET" action="{{ route('admin.payments.index') }}" class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-1">الحالة</label>
                <select name="status" class="form-select">
                    <option value="">الكل</option>
                    <option value="paid" @selected(request('status')==='paid' )>مدفوع</option>
                    <option value="partial" @selected(request('status')==='partial' )>جزئي</option>
                    <option value="unpaid" @selected(request('status')==='unpaid' )>غير مدفوع</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">المحذوفات</label>
                <select name="trashed" class="form-select">
                    <option value="" @selected(request('trashed')==='' )>الافتراضي</option>
                    <option value="all" @selected(request('trashed')==='all' )>الكل</option>
                    <option value="only" @selected(request('trashed')==='only' )>المحذوف فقط</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-dark w-100" type="submit">تطبيق</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary w-100">إعادة التعيين</a>
            </div>
        </div>
    </div>
</form>

@php
$paymentsTrashedCount = $payments->getCollection()->filter(fn($item) => $item->trashed())->count();
@endphp

<div class="d-flex gap-2 align-items-center mb-2">
    <span class="badge text-bg-dark">عدد السجلات في الصفحة: {{ $payments->count() }}</span>
    <span class="badge text-bg-warning">المحذوف في الصفحة: {{ $paymentsTrashedCount }}</span>
</div>

<div class="table-responsive">
    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>الطلب</th>
                <th>الوكيل</th>
                <th>المندوب</th>
                <th>المبلغ</th>
                <th>نوع الدفع</th>
                <th>الحالة</th>
                <th>التاريخ</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payments as $payment)
            <tr class="{{ $payment->trashed() ? 'table-warning' : '' }}">
                <td>#{{ $payment->order_id }}</td>
                <td>{{ $payment->order?->supplier?->owner_name }}</td>
                <td>{{ $payment->order?->distributor?->name ?: '-' }}</td>
                <td>{{ number_format((float) $payment->amount, 2) }}</td>
                <td>{{ $payment->payment_type === 'cash' ? 'كاش' : 'آجل' }}</td>
                <td>
                    @if ($payment->trashed())
                    <span class="badge text-bg-danger">محذوف</span>
                    @else
                    {{ \App\Support\StatusLabel::paymentStatus($payment->status) }}
                    @endif
                </td>
                <td>{{ optional($payment->paid_at)->format('Y-m-d H:i') ?: '-' }}</td>
                <td>
                    @if (! $payment->trashed())
                    <form method="POST" action="{{ route('admin.payments.destroy', $payment) }}" class="d-inline"
                        onsubmit="return confirm('هل أنت متأكد من حذف الدفعة؟');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                    </form>
                    @else
                    <form method="POST" action="{{ route('admin.payments.restore', $payment->id) }}" class="d-inline"
                        onsubmit="return confirm('هل تريد استرجاع الدفعة؟');">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-sm btn-outline-success" type="submit">استرجاع</button>
                    </form>
                    <form method="POST" action="{{ route('admin.payments.force-delete', $payment->id) }}"
                        class="d-inline" onsubmit="return confirm('سيتم حذف الدفعة نهائيًا. متابعة؟');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger" type="submit">حذف نهائي</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center text-muted py-4">لا توجد مدفوعات</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $payments->links() }}
@endsection