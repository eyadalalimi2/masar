@extends('customer.layout.app')

@section('title', 'مدفوعات العميل')

@section('content')
    <div class="container-fluid py-2">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="border rounded-4 p-3 bg-white">
                    <div class="small text-muted">إجمالي مدفوع</div>
                    <div class="fs-4 fw-bold">{{ number_format($summary['paid_total'], 2) }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-4 p-3 bg-white">
                    <div class="small text-muted">إجمالي جزئي</div>
                    <div class="fs-4 fw-bold">{{ number_format($summary['partial_total'], 2) }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-4 p-3 bg-white">
                    <div class="small text-muted">عدد السجلات</div>
                    <div class="fs-4 fw-bold">{{ $summary['records_count'] }}</div>
                </div>
            </div>
        </div>

        <form method="GET" class="card border-0 shadow-sm mb-3">
            <div class="card-body row g-2">
                <div class="col-md-4">
                    <select name="payment_status" class="form-select">
                        <option value="">كل حالات السداد</option>
                        <option value="paid" @selected($paymentStatus === 'paid')>مدفوع</option>
                        <option value="partial" @selected($paymentStatus === 'partial')>جزئي</option>
                        <option value="unpaid" @selected($paymentStatus === 'unpaid')>غير مدفوع</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select name="payment_type" class="form-select">
                        <option value="">كل طرق الدفع</option>
                        <option value="cash" @selected($paymentType === 'cash')>نقدي</option>
                        <option value="credit" @selected($paymentType === 'credit')>آجل</option>
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
                            <th>الطلب</th>
                            <th>المبلغ</th>
                            <th>النوع</th>
                            <th>الحالة</th>
                            <th>تاريخ السداد</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td>{{ $payment->id }}</td>
                                <td>#{{ $payment->order_id }}</td>
                                <td>{{ number_format((float) $payment->amount, 2) }}</td>
                                <td>{{ \App\Support\StatusLabel::paymentType($payment->payment_type) }}</td>
                                <td>{{ \App\Support\StatusLabel::paymentStatus($payment->status) }}</td>
                                <td>{{ $payment->paid_at?->format('Y-m-d H:i') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">لا توجد مدفوعات مطابقة.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $payments->links() }}</div>
    </div>
@endsection
