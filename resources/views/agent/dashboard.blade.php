@extends('agent.layout.app')

@section('content')
    <style>
        .agent-dashboard-shell {
            background: radial-gradient(circle at 0% 0%, #f0f9ff 0%, #ffffff 48%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 18px;
        }

        .agent-hero {
            border-radius: 16px;
            background: linear-gradient(135deg, #0f172a 0%, #0f766e 100%);
            color: #fff;
            padding: 18px;
        }

        .agent-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.12);
        }

        .agent-stat {
            border: 1px solid rgba(148, 163, 184, 0.28);
            border-radius: 14px;
            background: linear-gradient(140deg, #334155 0%, #1e293b 100%);
            padding: 14px;
            height: 100%;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .agent-stat::before {
            content: '';
            position: absolute;
            top: -20px;
            left: -24px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.14);
        }

        .agent-stat-label {
            color: rgba(255, 255, 255, 0.9);
            font-size: .85rem;
            position: relative;
            z-index: 1;
        }

        .agent-stat-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: #ffffff;
            position: relative;
            z-index: 1;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .agent-dashboard-shell .row.g-3.mb-3>div:nth-child(1) .agent-stat {
            background: linear-gradient(140deg, #2563eb 0%, #1d4ed8 55%, #1e40af 100%);
        }

        .agent-dashboard-shell .row.g-3.mb-3>div:nth-child(2) .agent-stat {
            background: linear-gradient(140deg, #059669 0%, #047857 55%, #065f46 100%);
        }

        .agent-dashboard-shell .row.g-3.mb-3>div:nth-child(3) .agent-stat {
            background: linear-gradient(140deg, #d97706 0%, #b45309 55%, #92400e 100%);
        }

        .agent-dashboard-shell .row.g-3.mb-3>div:nth-child(4) .agent-stat {
            background: linear-gradient(140deg, #db2777 0%, #be185d 55%, #9d174d 100%);
        }

        .agent-dashboard-shell .row.g-3.mb-3>div:nth-child(5) .agent-stat {
            background: linear-gradient(140deg, #dc2626 0%, #b91c1c 55%, #991b1b 100%);
        }

        .agent-dashboard-shell .row.g-3.mb-3>div:nth-child(6) .agent-stat {
            background: linear-gradient(140deg, #7c3aed 0%, #6d28d9 55%, #5b21b6 100%);
        }

        .agent-dashboard-shell .row.g-3.mb-3>div:nth-child(7) .agent-stat {
            background: linear-gradient(140deg, #0891b2 0%, #0e7490 55%, #155e75 100%);
        }

        .agent-dashboard-shell .row.g-3.mb-3>div:nth-child(8) .agent-stat {
            background: linear-gradient(140deg, #ca8a04 0%, #a16207 55%, #854d0e 100%);
        }

        .agent-dashboard-shell .row.g-3.mb-3>div:nth-child(9) .agent-stat {
            background: linear-gradient(140deg, #0d9488 0%, #0f766e 55%, #115e59 100%);
        }

        .agent-dashboard-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #fff;
            overflow: hidden;
        }
    </style>

    <div class="container-fluid agent-dashboard-shell">
        <div class="agent-hero mb-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-3">
                    @if ($supplier->agent_image)
                        <img src="{{ asset('storage/' . $supplier->agent_image) }}" alt="صورة الوكيل" class="agent-avatar">
                    @else
                        <div class="agent-avatar d-flex align-items-center justify-content-center fw-bold">
                            {{ mb_substr($supplier->owner_name ?? 'و', 0, 1) }}</div>
                    @endif

                    <div>
                        <h1 class="h4 fw-bold mb-1">مرحبًا بك في لوحة الوكيل</h1>
                        <p class="mb-0 text-white-50">{{ $supplier->business_name }} - {{ $supplier->owner_name }}</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('agent.products.index') }}" class="btn btn-light btn-sm">المنتجات</a>
                    <a href="{{ route('agent.orders.index') }}" class="btn btn-outline-light btn-sm">الطلبات</a>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3 col-6">
                <div class="agent-stat">
                    <div class="agent-stat-label">المنتجات</div>
                    <div class="agent-stat-value">{{ $stats['products_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="agent-stat">
                    <div class="agent-stat-label">الفروع</div>
                    <div class="agent-stat-value">{{ $stats['branches_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="agent-stat">
                    <div class="agent-stat-label">المندوبون</div>
                    <div class="agent-stat-value">{{ $stats['distributors_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="agent-stat">
                    <div class="agent-stat-label">إجمالي الطلبات</div>
                    <div class="agent-stat-value">{{ $stats['orders_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="agent-stat">
                    <div class="agent-stat-label">طلبات قيد التنفيذ</div>
                    <div class="agent-stat-value">{{ $stats['pending_orders_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="agent-stat">
                    <div class="agent-stat-label">صافي مبيعات اليوم</div>
                    <div class="agent-stat-value">{{ number_format((float) $stats['today_sales'], 2) }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="agent-stat">
                    <div class="agent-stat-label">المدفوعات المسددة</div>
                    <div class="agent-stat-value">{{ number_format((float) $stats['paid_payments'], 2) }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="agent-stat">
                    <div class="agent-stat-label">صافي مبيعات آخر 30 يوم</div>
                    <div class="agent-stat-value">{{ number_format((float) $stats['month_sales'], 2) }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="agent-stat">
                    <div class="agent-stat-label">حالة الحساب</div>
                    <div class="agent-stat-value">{{ $supplier->status === 'active' ? 'مفعل' : 'معطل' }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="agent-stat">
                    <div class="agent-stat-label">الطلبات المتأخرة</div>
                    <div class="agent-stat-value">{{ number_format((int) ($stats['delayed_orders_count'] ?? 0)) }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="agent-stat">
                    <div class="agent-stat-label">تنبيهات التأخير اليوم</div>
                    <div class="agent-stat-value">{{ number_format((int) ($delayAlertsTodayCount ?? 0)) }}</div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <div class="agent-dashboard-card h-100">
                    <div class="p-3 border-bottom fw-semibold">تنبيه مخزون منخفض</div>
                    <div class="p-3">
                        <ul class="list-group list-group-flush">
                            @forelse ($lowStockAlerts as $alert)
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <span>{{ $alert->product_name }} ({{ $alert->unit_name ?? 'وحدة' }})</span>
                                    <span class="badge text-bg-warning">
                                        {{ number_format((float) $alert->stock_quantity, 3) }}
                                    </span>
                                </li>
                            @empty
                                <li class="list-group-item px-0 text-muted">لا توجد تنبيهات مخزون منخفض.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="agent-dashboard-card h-100">
                    <div class="p-3 border-bottom fw-semibold">منتجات منخفضة المبيعات (30 يوم)</div>
                    <div class="p-3">
                        <ul class="list-group list-group-flush">
                            @forelse ($lowSalesProducts as $product)
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <span>{{ $product->name }}</span>
                                    <span class="badge text-bg-secondary">{{ $product->model }}</span>
                                </li>
                            @empty
                                <li class="list-group-item px-0 text-muted">لا يوجد منتجات منخفضة المبيعات.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="agent-dashboard-card h-100">
                    <div class="p-3 border-bottom fw-semibold">المناطق الأكثر نشاطًا</div>
                    <div class="p-3">
                        <ul class="list-group list-group-flush">
                            @forelse ($activeAreas as $area)
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <span>{{ $area->customer_address }}</span>
                                    <span class="badge text-bg-primary">{{ (int) $area->orders_count }}</span>
                                </li>
                            @empty
                                <li class="list-group-item px-0 text-muted">لا توجد بيانات كافية حتى الآن.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="agent-dashboard-card h-100">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">تنبيهات النظام</span>
                        <div class="d-flex gap-2 align-items-center">
                            <span class="badge text-bg-info">{{ $unreadAlertsCount }}</span>
                            <a href="{{ route('agent.alerts.index') }}" class="btn btn-sm btn-outline-dark">عرض الكل</a>
                        </div>
                    </div>
                    <div class="p-3">
                        <ul class="list-group list-group-flush">
                            @forelse ($recentAlerts as $alert)
                                <li class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="fw-semibold small">{{ $alert->title }}</div>
                                            <div class="small text-muted">{{ $alert->body }}</div>
                                            <div class="small text-secondary mt-1">
                                                {{ $alert->created_at?->format('Y-m-d H:i') }}</div>
                                        </div>
                                        @if (!$alert->read_at)
                                            <form method="POST"
                                                action="{{ route('agent.alerts.mark-read', $alert->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-outline-primary">تعليم</button>
                                            </form>
                                        @endif
                                    </div>
                                </li>
                            @empty
                                <li class="list-group-item px-0 text-muted">لا توجد تنبيهات حاليًا.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="agent-dashboard-card">
            <div class="p-3 border-bottom fw-semibold">آخر الطلبات</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>العميل</th>
                            <th>الهاتف</th>
                            <th>الإجمالي</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
                            <tr>
                                <td>{{ $order->id }}</td>
                                <td>{{ $order->customer_name }}</td>
                                <td dir="ltr">{{ $order->customer_phone }}</td>
                                <td>{{ number_format((float) ($order->payable_total ?? $order->total_price), 2) }}</td>
                                <td>{{ \App\Support\StatusLabel::order($order->status) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">لا توجد طلبات حديثة.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
