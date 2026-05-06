@extends('workshop.layout.app')

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <h1 class="workshop-section-title">تنفيذ الخدمة</h1>
    <p class="workshop-section-subtitle">عرض حي لطلبات الخدمة وحالتها الحالية مع تسجيل المنتجات المستخدمة أثناء التنفيذ.</p>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="workshop-stat">
                <div class="workshop-stat-label">طلبات SLA النشطة</div>
                <div class="workshop-stat-value">{{ number_format((int) ($slaSummary['active_orders_count'] ?? 0)) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="workshop-stat">
                <div class="workshop-stat-label">طلبات متجاوزة SLA</div>
                <div class="workshop-stat-value text-danger">
                    {{ number_format((int) ($slaSummary['critical_orders_count'] ?? 0)) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="workshop-stat">
                <div class="workshop-stat-label">طلبات عالية الأولوية</div>
                <div class="workshop-stat-value text-warning">
                    {{ number_format((int) ($slaSummary['high_orders_count'] ?? 0)) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="workshop-stat">
                <div class="workshop-stat-label">متوسط الدقائق المتبقية</div>
                <div class="workshop-stat-value">{{ number_format((int) ($slaSummary['average_remaining_minutes'] ?? 0)) }}
                </div>
            </div>
        </div>
    </div>

    <div class="workshop-panel mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h6 fw-bold mb-0">قائمة أولوية التنفيذ الذكية (SLA)</h2>
            <form method="POST" action="{{ route('workshop.execution.sla-alerts.generate') }}">
                @csrf
                <button class="btn btn-sm btn-outline-warning">توليد تنبيهات SLA</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>الطلب</th>
                        <th>الخدمة</th>
                        <th>الحالة</th>
                        <th>المتبقي SLA (دقيقة)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($priorityOrders as $priorityOrder)
                        <tr>
                            <td>{{ $priorityOrder->order_number }}</td>
                            <td>{{ $priorityOrder->service?->name ?? 'خدمة عامة' }}</td>
                            <td>
                                <span
                                    class="badge {{ $priorityOrder->sla_priority_level === 'critical' ? 'text-bg-danger' : ($priorityOrder->sla_priority_level === 'high' ? 'text-bg-warning' : ($priorityOrder->sla_priority_level === 'medium' ? 'text-bg-info' : 'text-bg-secondary')) }}">
                                    {{ $priorityOrder->sla_priority_label }}
                                </span>
                            </td>
                            <td>{{ number_format((int) $priorityOrder->sla_remaining_minutes) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">لا توجد طلبات ضمن نطاق SLA حاليا.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="small text-muted mb-2">آخر تحديث مباشر: <span id="live-updated-at">--:--:--</span></div>

    <div class="workshop-panel">
        <h2 class="h6 fw-bold mb-3">آخر عمليات التنفيذ</h2>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>الطلب</th>
                        <th>الخدمة</th>
                        <th>تفاصيل المنتجات</th>
                        <th>منتجات مستخدمة</th>
                        <th>الحالة</th>
                        <th>تحديث المنتجات</th>
                    </tr>
                </thead>
                <tbody id="execution-table-body">
                    @forelse ($recentOrders as $order)
                        <tr>
                            <td>{{ $order->order_number }}</td>
                            <td>{{ $order->service?->name ?? 'خدمة عامة' }}</td>
                            <td>
                                @if (is_array($order->used_products) && count($order->used_products) > 0)
                                    <ul class="mb-0 small" style="padding-right: 16px;">
                                        @foreach ($order->used_products as $used)
                                            <li>
                                                {{ $used['product_name'] ?? '-' }}
                                                × {{ number_format((float) ($used['quantity'] ?? 0), 3) }}
                                                @ {{ number_format((float) ($used['unit_cost'] ?? 0), 2) }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-muted small">لا توجد منتجات مسجلة</span>
                                @endif
                            </td>
                            <td>{{ number_format((float) $order->products_total, 2) }} ر.ي</td>
                            <td>
                                @php
                                    $statusText = match ($order->status) {
                                        'requested' => 'جديد',
                                        'in_progress' => 'قيد التنفيذ',
                                        'completed' => 'مكتمل',
                                        'cancelled' => 'ملغي',
                                        default => $order->status,
                                    };
                                @endphp
                                <span class="workshop-badge">{{ $statusText }}</span>
                            </td>
                            <td style="min-width: 320px;">
                                <form method="POST" action="{{ route('workshop.execution.products.update', $order) }}">
                                    @csrf
                                    @method('PATCH')
                                    <textarea class="form-control form-control-sm mb-2" rows="2" name="used_products_text"
                                        placeholder="اسم المنتج | الكمية | سعر الوحدة&#10;مثال: زيت محرك | 1 | 3500">
@if (is_array($order->used_products))
@foreach ($order->used_products as $used)
{{ ($used['product_name'] ?? '') . ' | ' . ($used['quantity'] ?? 0) . ' | ' . ($used['unit_cost'] ?? 0) }}
@endforeach
@endif
</textarea>
                                    <button type="submit" class="btn btn-sm btn-outline-primary w-100">حفظ المنتجات
                                        المستخدمة</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">لا توجد عمليات تنفيذ مسجلة حتى الآن.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const liveUrl = @json(route('workshop.live.overview'));
            const updatedAtEl = document.getElementById('live-updated-at');
            const tableBody = document.getElementById('execution-table-body');

            function statusText(status) {
                if (status === 'requested') return 'جديد';
                if (status === 'in_progress') return 'قيد التنفيذ';
                if (status === 'completed') return 'مكتمل';
                if (status === 'cancelled') return 'ملغي';
                return status;
            }

            function renderRows(rows) {
                if (!tableBody || !Array.isArray(rows)) {
                    return;
                }

                const existingRows = Array.from(tableBody.querySelectorAll('tr'));
                existingRows.forEach((row, index) => {
                    if (index >= rows.length) {
                        return;
                    }
                    const cells = row.querySelectorAll('td');
                    if (cells.length < 5) {
                        return;
                    }
                    cells[0].textContent = rows[index].order_number || '';
                    cells[1].textContent = rows[index].service_name || 'خدمة عامة';
                    cells[3].textContent = Number(rows[index].products_total || 0).toFixed(2) + ' ر.ي';
                    const badge = cells[4].querySelector('.workshop-badge');
                    if (badge) {
                        badge.textContent = statusText(rows[index].status || '');
                    }
                });
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
                    if (updatedAtEl && payload.updated_at) {
                        updatedAtEl.textContent = payload.updated_at;
                    }
                    renderRows(payload.recent_orders || []);
                } catch (error) {
                    // keep current rendered values on transient network failures
                }
            }

            fetchLiveOverview();
            setInterval(fetchLiveOverview, 20000);
        })();
    </script>
@endpush
