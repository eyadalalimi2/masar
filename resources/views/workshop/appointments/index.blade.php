@extends('workshop.layout.app')

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <h1 class="workshop-section-title">إدارة المواعيد</h1>
    <p class="workshop-section-subtitle">تنظيم ساعات العمل والحجوزات اليومية وتوزيع المهام على الفنيين.</p>

    <div class="workshop-panel mb-3">
        <h2 class="h6 fw-bold mb-3">حجز موعد جديد</h2>
        <div class="small text-muted mb-2">
            @if (!empty($suggestedSlot))
                الموعد الذكي المقترح: {{ $suggestedSlot->format('Y-m-d H:i') }}
            @else
                لا يوجد موعد ذكي متاح حاليا، اختر وقتا يدويا.
            @endif
        </div>
        <form action="{{ route('workshop.appointments.store') }}" method="POST" class="row g-2">
            @csrf
            <div class="col-md-3">
                <select name="service_id" class="form-select">
                    <option value="">الخدمة (اختياري)</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}">{{ $service->name }} ({{ $service->duration_minutes }} د)
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="customer_name" class="form-control" placeholder="اسم العميل" required>
            </div>
            <div class="col-md-2">
                <input type="text" name="customer_phone" class="form-control" placeholder="هاتف العميل" required>
            </div>
            <div class="col-md-2">
                <input type="datetime-local" name="appointment_at" class="form-control">
            </div>
            <div class="col-md-1">
                <input type="number" name="estimated_minutes" min="10" max="480" class="form-control"
                    placeholder="د" required>
            </div>
            <div class="col-md-2">
                <input type="text" name="vehicle_details" class="form-control" placeholder="تفاصيل المركبة">
            </div>
            <div class="col-12">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="auto_schedule" value="1" id="autoSchedule">
                    <label class="form-check-label" for="autoSchedule">استخدم الجدولة الذكية تلقائيا</label>
                </div>
                <textarea name="notes" class="form-control" rows="2" placeholder="ملاحظات"></textarea>
            </div>
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary btn-sm">تأكيد الحجز</button>
            </div>
        </form>
    </div>

    <div class="workshop-panel">
        <h2 class="h6 fw-bold mb-3">جدول المواعيد</h2>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>التاريخ والوقت</th>
                        <th>الخدمة</th>
                        <th>العميل</th>
                        <th>الهاتف</th>
                        <th>المدة</th>
                        <th>الحالة</th>
                        <th>تحديث الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($appointments as $appointment)
                        <tr>
                            <td>{{ $appointment->appointment_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $appointment->service?->name ?: '—' }}</td>
                            <td>{{ $appointment->customer_name }}</td>
                            <td>{{ $appointment->customer_phone }}</td>
                            <td>{{ $appointment->estimated_minutes }} دقيقة</td>
                            <td><span
                                    class="workshop-badge">{{ \App\Support\StatusLabel::workshopAppointment($appointment->status) }}</span>
                            </td>
                            <td>
                                <form action="{{ route('workshop.appointments.status', $appointment) }}" method="POST"
                                    class="d-flex gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="scheduled" @selected($appointment->status === 'scheduled')>مجدول</option>
                                        <option value="in_progress" @selected($appointment->status === 'in_progress')>قيد التنفيذ</option>
                                        <option value="completed" @selected($appointment->status === 'completed')>مكتمل</option>
                                        <option value="cancelled" @selected($appointment->status === 'cancelled')>ملغي</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">حفظ</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">لا توجد مواعيد حتى الآن.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
