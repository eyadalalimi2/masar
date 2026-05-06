@extends('distributor.layout.app')

@section('title', 'تسجيل تحصيل')

@section('content')
    <div class="container-fluid py-2">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 fw-bold mb-0">تسجيل تحصيل</h1>
            <a href="{{ route('distributor.payments.index') }}" class="btn btn-outline-secondary">رجوع</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('distributor.payments.store') }}" class="card border-0 shadow-sm">
            @csrf
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الطلب</label>
                        <select name="order_id" class="form-select" required>
                            <option value="">اختر الطلب</option>
                            @foreach ($orders as $order)
                                @php
                                    $paid = (float) $order->payments->sum('amount');
                                    $remaining = max(0, (float) $order->total_price - $paid);
                                @endphp
                                <option value="{{ $order->id }}" @selected(old('order_id') == $order->id)>
                                    #{{ $order->id }} - {{ $order->customer_name }} - متبقي
                                    {{ number_format($remaining, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">نوع الدفع</label>
                        <select name="payment_type" class="form-select" required>
                            <option value="credit" @selected(old('payment_type', 'credit') === 'credit')>آجل</option>
                            <option value="cash" @selected(old('payment_type') === 'cash')>كاش</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">المبلغ</label>
                        <input type="number" step="0.01" min="0" name="amount" class="form-control"
                            value="{{ old('amount', 0) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white">
                <button type="submit" class="btn btn-dark">حفظ التحصيل</button>
            </div>
        </form>
    </div>
@endsection
