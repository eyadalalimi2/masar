@extends('workshop.layout.app')

@section('content')
    @php
        $metrics = $metrics ?? [
            'new_service_orders' => 0,
            'today_appointments' => 0,
            'today_completed_services' => 0,
            'today_revenue' => 0,
            'pending_purchase_orders' => 0,
        ];
        $upcomingAppointments = $upcomingAppointments ?? collect();
    @endphp

    <h1 class="workshop-section-title">مرحبًا {{ $workshop->name }}</h1>
    <p class="workshop-section-subtitle">لوحة موحدة لإدارة خدمات الورشة، المواعيد، الطلبات، والمبيعات اليومية.</p>

    <div class="row g-3 mb-3">
        <div class="col-md-6 col-lg-3">
            <div class="workshop-stat">
                <div class="workshop-stat-label">طلبات جديدة</div>
                <div class="workshop-stat-value" id="metric-new-orders">{{ $metrics['new_service_orders'] }}</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="workshop-stat">
                <div class="workshop-stat-label">مواعيد اليوم</div>
                <div class="workshop-stat-value">{{ $metrics['today_appointments'] }}</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="workshop-stat">
                <div class="workshop-stat-label">خدمات منفذة اليوم</div>
                <div class="workshop-stat-value" id="metric-completed-today">{{ $metrics['today_completed_services'] }}
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="workshop-stat">
                <div class="workshop-stat-label">صافي الإيراد اليومي</div>
                <div class="workshop-stat-value" id="metric-revenue-today">
                    {{ number_format((float) $metrics['today_revenue'], 0) }} ر.ي</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="workshop-panel h-100">
                <h2 class="h6 fw-bold mb-3">تدفق الخدمة</h2>
                <ul class="workshop-list">
                    <li>استقبال طلب خدمة من العميل.</li>
                    <li>تحديد الموعد وإسناد الفني.</li>
                    <li>بدء التنفيذ وتسجيل المنتجات المستخدمة.</li>
                    <li>إنهاء الطلب وإصدار الفاتورة.</li>
                </ul>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="workshop-panel h-100">
                <h2 class="h6 fw-bold mb-3">تدفق التوريد</h2>
                <ul class="workshop-list">
                    <li>طلب المنتجات من السوق (الفروع).</li>
                    <li>اعتماد الطلب ومراجعته من الفرع.</li>
                    <li>تجهيز الشحنة والتوصيل عبر المندوب.</li>
                    <li>تحديث المخزون وربط المواد بالخدمات.</li>
                </ul>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="workshop-panel h-100">
                <h2 class="h6 fw-bold mb-3">أقرب المواعيد</h2>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>الوقت</th>
                                <th>الخدمة</th>
                                <th>العميل</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($upcomingAppointments as $appointment)
                                <tr>
                                    <td>{{ $appointment->appointment_at?->format('Y-m-d H:i') }}</td>
                                    <td>{{ $appointment->service?->name ?: '—' }}</td>
                                    <td>{{ $appointment->customer_name }}</td>
                                    <td><span class="workshop-badge">{{ $appointment->status }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">لا توجد مواعيد قادمة.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="workshop-panel h-100">
                <h2 class="h6 fw-bold mb-3">قواعد تشغيلية</h2>
                <ul class="workshop-list">
                    <li>الورشة ترى منتجات السوق فقط.</li>
                    <li>لا يسمح بتعديل إعدادات النظام العامة.</li>
                    <li>كل خدمة يمكن ربطها بمنتجات بشكل اختياري.</li>
                    <li>الربح = قيمة الخدمة + هامش المنتج.</li>
                    <li>طلبات شراء معلقة: <span
                            id="metric-pending-purchase">{{ $metrics['pending_purchase_orders'] }}</span></li>
                </ul>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const liveUrl = @json(route('workshop.live.overview'));
            const newOrdersEl = document.getElementById('metric-new-orders');
            const completedTodayEl = document.getElementById('metric-completed-today');
            const revenueTodayEl = document.getElementById('metric-revenue-today');
            const pendingPurchaseEl = document.getElementById('metric-pending-purchase');

            function formatCurrency(value) {
                return new Intl.NumberFormat('ar-YE', {
                    maximumFractionDigits: 0
                }).format(Number(value || 0)) + ' ر.ي';
            }

            async function fetchLiveOverview() {
                try {
                    const response = await fetch(liveUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!response.ok) return;

                    const payload = await response.json();
                    const metrics = payload.metrics || {};

                    if (newOrdersEl) newOrdersEl.textContent = String(metrics.new_service_orders ?? 0);
                    if (completedTodayEl) completedTodayEl.textContent = String(metrics.today_completed_services ??
                        0);
                    if (revenueTodayEl) revenueTodayEl.textContent = formatCurrency(metrics.today_revenue ?? 0);
                    if (pendingPurchaseEl) pendingPurchaseEl.textContent = String(metrics.pending_purchase_orders ??
                        0);
                } catch (error) {
                    // keep server-rendered values when polling fails
                }
            }

            fetchLiveOverview();
            setInterval(fetchLiveOverview, 20000);
        })();
    </script>
@endpush
