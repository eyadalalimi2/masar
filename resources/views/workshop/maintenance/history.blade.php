@extends('workshop.layout.app')

@section('content')
    <h1 class="workshop-section-title">سجل الصيانة</h1>
    <p class="workshop-section-subtitle">عرض العمليات المكتملة المرتبطة ببيانات المركبات لتتبع تاريخ الخدمة لكل عميل.</p>

    <div class="workshop-panel">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>رقم الطلب</th>
                        <th>العميل</th>
                        <th>المركبة</th>
                        <th>آخر عداد</th>
                        <th>الخدمة</th>
                        <th>المبلغ</th>
                        <th>تاريخ الإكمال</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history as $row)
                        <tr>
                            <td>{{ $row->order_number }}</td>
                            <td>{{ $row->customer_name }}<br><small class="text-muted">{{ $row->customer_phone }}</small>
                            </td>
                            <td>
                                {{ trim(implode(' ', array_filter([$row->vehicle_brand, $row->vehicle_model]))) ?: '—' }}
                                <br><small
                                    class="text-muted">{{ $row->vehicle_plate_number ?: '—' }}{{ $row->vehicle_production_year ? ' | ' . $row->vehicle_production_year : '' }}</small>
                            </td>
                            <td>{{ $row->odometer_km ? number_format((int) $row->odometer_km) . ' كم' : '—' }}</td>
                            <td>{{ $row->service?->name ?: '—' }}</td>
                            <td>{{ number_format((float) $row->total_amount, 0) }} ر.ي</td>
                            <td>{{ $row->updated_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">لا يوجد سجل صيانة مكتمل حتى الآن.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
