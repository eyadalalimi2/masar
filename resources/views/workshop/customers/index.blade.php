@extends('workshop.layout.app')

@section('content')
    <h1 class="workshop-section-title">إدارة العملاء</h1>
    <p class="workshop-section-subtitle">عرض سجل العملاء الفعلي ونسب الولاء بناء على طلبات الخدمة المنفذة.</p>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="workshop-panel h-100">
                <h2 class="h6 fw-bold mb-3">سجل العملاء</h2>
                @if ($customers->isEmpty())
                    <p class="mb-0 text-muted">لا يوجد سجل عملاء حتى الآن.</p>
                @else
                    <ul class="workshop-list">
                        @foreach ($customers as $customer)
                            <li>
                                {{ $customer->customer_name ?? 'عميل بدون اسم' }} - {{ $customer->orders_count }} خدمة -
                                آخر زيارة {{ \Illuminate\Support\Carbon::parse($customer->last_visit_at)->diffForHumans() }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
        <div class="col-lg-5">
            <div class="workshop-panel h-100">
                <h2 class="h6 fw-bold mb-3">ولاء العملاء</h2>
                <p class="mb-2">العملاء المتكررين هذا الشهر: <strong>{{ $repeatPercent }}%</strong></p>
                <p class="mb-2">العملاء الجدد: <strong>{{ $newPercent }}%</strong></p>
                <p class="mb-0">متوسط عدد الخدمات لكل عميل: <strong>{{ $avgOrdersPerCustomer }}</strong></p>
            </div>
        </div>
    </div>
@endsection
