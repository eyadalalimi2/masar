@extends('workshop.layout.app')

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <h1 class="workshop-section-title">طلبات الخدمة</h1>
    <p class="workshop-section-subtitle">استقبال حجوزات العملاء ومتابعة حالة كل خدمة حتى الإنهاء.</p>

    <div class="workshop-panel mb-3">
        <h2 class="h6 fw-bold mb-3">إنشاء طلب خدمة</h2>
        <form action="{{ route('workshop.orders.service.store') }}" method="POST" class="row g-2">
            @csrf
            <div class="col-md-3">
                <select name="service_id" class="form-select">
                    <option value="">الخدمة (اختياري)</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="appointment_id" class="form-select">
                    <option value="">ربط بموعد (اختياري)</option>
                    @foreach ($appointments as $appointment)
                        <option value="{{ $appointment->id }}">{{ $appointment->customer_name }} -
                            {{ $appointment->appointment_at?->format('m-d H:i') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="customer_name" class="form-control" placeholder="اسم العميل" required>
            </div>
            <div class="col-md-2">
                <input type="text" name="customer_phone" class="form-control" placeholder="الهاتف" required>
            </div>
            <div class="col-md-2">
                <input type="text" name="vehicle_plate_number" class="form-control" placeholder="رقم اللوحة">
            </div>
            <div class="col-md-2">
                <input type="text" name="vehicle_brand" class="form-control" placeholder="الماركة">
            </div>
            <div class="col-md-2">
                <input type="text" name="vehicle_model" class="form-control" placeholder="الموديل">
            </div>
            <div class="col-md-1">
                <input type="number" min="1950" max="2100" name="vehicle_production_year" class="form-control"
                    placeholder="سنة">
            </div>
            <div class="col-md-1">
                <input type="number" min="0" name="odometer_km" class="form-control" placeholder="كم">
            </div>
            <div class="col-md-1">
                <input type="number" step="0.01" min="0" name="service_fee" class="form-control"
                    placeholder="الخدمة" required>
            </div>
            <div class="col-md-1">
                <input type="number" step="0.01" min="0" name="products_total" class="form-control"
                    placeholder="المنتج" required>
            </div>
            <div class="col-12">
                <input type="text" name="notes" class="form-control" placeholder="ملاحظات إضافية">
            </div>
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary btn-sm">إنشاء الطلب</button>
            </div>
        </form>
    </div>

    <div class="workshop-panel">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>رقم الطلب</th>
                        <th>العميل</th>
                        <th>المركبة</th>
                        <th>الخدمة</th>
                        <th>الإجمالي</th>
                        <th>بعد عمولة المنصة</th>
                        <th>الحالة</th>
                        <th>تحديث الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>{{ $order->order_number }}</td>
                            <td>{{ $order->customer_name }}<br><small
                                    class="text-muted">{{ $order->customer_phone }}</small></td>
                            <td>
                                @php
                                    $vehicle = trim(
                                        implode(' ', array_filter([$order->vehicle_brand, $order->vehicle_model])),
                                    );
                                @endphp
                                {{ $vehicle !== '' ? $vehicle : '—' }}
                                <br><small
                                    class="text-muted">{{ $order->vehicle_plate_number ?: '—' }}{{ $order->odometer_km ? ' | ' . number_format((int) $order->odometer_km) . ' كم' : '' }}</small>
                            </td>
                            <td>{{ $order->service?->name ?: '—' }}</td>
                            <td>{{ number_format((float) $order->total_amount, 0) }} ر.ي</td>
                            <td>
                                <div>{{ number_format((float) ($order->payable_total ?? $order->total_amount), 0) }} ر.ي
                                </div>
                                <small class="text-muted">رسوم المنصة:
                                    {{ number_format((float) (($order->commission_value ?? 0) + ($order->platform_service_fee ?? 0) + ($order->platform_fixed_fee ?? 0)), 0) }}
                                    ر.ي</small>
                            </td>
                            <td><span
                                    class="workshop-badge">{{ \App\Support\StatusLabel::workshopServiceOrder($order->status) }}</span>
                            </td>
                            <td>
                                <form action="{{ route('workshop.orders.service.status', $order) }}" method="POST"
                                    class="d-flex gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="requested" @selected($order->status === 'requested')>مطلوب</option>
                                        <option value="in_progress" @selected($order->status === 'in_progress')>قيد التنفيذ</option>
                                        <option value="completed" @selected($order->status === 'completed')>مكتمل</option>
                                        <option value="cancelled" @selected($order->status === 'cancelled')>ملغي</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">حفظ</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">لا توجد طلبات خدمة بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
