@extends('consumer.layout.app')

@section('title', 'تتبع الطلبات | المستهلك')

@section('content')
    <div class="container-fluid py-2">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="border rounded-4 bg-white p-3 h-100">
                    <h2 class="h6 mb-3">طلبات المنتجات</h2>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الحالة</th>
                                    <th>طريقة الاستلام</th>
                                    <th>موقع المندوب</th>
                                    <th>الإجمالي</th>
                                    <th>آخر تحديث</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($productOrders as $order)
                                    @php
                                        $isPickup = str_starts_with(
                                            (string) $order->customer_address,
                                            'استلام من المتجر',
                                        );
                                        $latestLocation = $order->locationLogs->first();
                                    @endphp
                                    <tr>
                                        <td>#{{ $order->id }}</td>
                                        <td>{{ \App\Support\StatusLabel::order($order->status) }}</td>
                                        <td>{{ $isPickup ? 'استلام' : 'توصيل' }}</td>
                                        <td>
                                            @if ($latestLocation)
                                                <span
                                                    dir="ltr">{{ number_format((float) $latestLocation->latitude, 5) }},
                                                    {{ number_format((float) $latestLocation->longitude, 5) }}</span>
                                            @else
                                                <span class="text-muted">غير متاح</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="small text-muted">أساسي:</div>
                                            <div>{{ number_format((float) $order->total_price, 2) }}</div>
                                            <div class="small text-muted">بعد العمولة:
                                                {{ number_format((float) ($order->payable_total ?? $order->total_price), 2) }}
                                            </div>
                                        </td>
                                        <td>{{ $order->updated_at?->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">لا توجد طلبات منتجات.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="border rounded-4 bg-white p-3 h-100">
                    <h2 class="h6 mb-3">طلبات الخدمات</h2>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>رقم الطلب</th>
                                    <th>الخدمة</th>
                                    <th>الحالة</th>
                                    <th>الإجمالي</th>
                                    <th>آخر تحديث</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($serviceOrders as $order)
                                    <tr>
                                        <td>{{ $order->order_number }}</td>
                                        <td>{{ $order->service?->name }}</td>
                                        <td>{{ \App\Support\StatusLabel::workshopServiceOrder($order->status) }}</td>
                                        <td>
                                            <div class="small text-muted">أساسي:</div>
                                            <div>{{ number_format((float) $order->total_amount, 2) }}</div>
                                            <div class="small text-muted">بعد العمولة:
                                                {{ number_format((float) ($order->payable_total ?? $order->total_amount), 2) }}
                                            </div>
                                        </td>
                                        <td>{{ $order->updated_at?->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">لا توجد طلبات خدمات.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
