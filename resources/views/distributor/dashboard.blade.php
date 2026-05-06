@extends('distributor.layout.app')

@section('title', 'لوحة المندوب')

@section('content')
    <style>
        .dist-shell {
            background: radial-gradient(circle at 0% 0%, #ecfeff 0%, #ffffff 45%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 16px;
        }

        .dist-hero {
            border-radius: 16px;
            background: linear-gradient(135deg, #0f172a 0%, #0f766e 100%);
            color: #fff;
            padding: 16px;
            margin-bottom: 12px;
        }

        .dist-stat {
            border: 1px solid rgba(148, 163, 184, 0.28);
            border-radius: 14px;
            background: linear-gradient(140deg, #334155 0%, #1e293b 100%);
            padding: 14px;
            height: 100%;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            position: relative;
            overflow: hidden;
        }

        .dist-stat::before {
            content: '';
            position: absolute;
            top: -20px;
            left: -24px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.14);
        }

        .dist-stat .text-muted {
            color: rgba(255, 255, 255, 0.9) !important;
            position: relative;
            z-index: 1;
        }

        .dist-stat .fw-semibold,
        .dist-stat .fs-5 {
            color: #ffffff !important;
            position: relative;
            z-index: 1;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .dist-stats-row>div:nth-child(1) .dist-stat {
            background: linear-gradient(140deg, #2563eb 0%, #1d4ed8 55%, #1e40af 100%);
        }

        .dist-stats-row>div:nth-child(2) .dist-stat {
            background: linear-gradient(140deg, #059669 0%, #047857 55%, #065f46 100%);
        }

        .dist-stats-row>div:nth-child(3) .dist-stat {
            background: linear-gradient(140deg, #d97706 0%, #b45309 55%, #92400e 100%);
        }

        .dist-stats-row>div:nth-child(4) .dist-stat {
            background: linear-gradient(140deg, #db2777 0%, #be185d 55%, #9d174d 100%);
        }

        .dist-stats-row>div:nth-child(5) .dist-stat {
            background: linear-gradient(140deg, #dc2626 0%, #b91c1c 55%, #991b1b 100%);
        }

        .dist-stats-row>div:nth-child(6) .dist-stat {
            background: linear-gradient(140deg, #7c3aed 0%, #6d28d9 55%, #5b21b6 100%);
        }

        .dist-stats-row>div:nth-child(7) .dist-stat {
            background: linear-gradient(140deg, #0891b2 0%, #0e7490 55%, #155e75 100%);
        }

        .dist-stats-row>div:nth-child(8) .dist-stat {
            background: linear-gradient(140deg, #ca8a04 0%, #a16207 55%, #854d0e 100%);
        }

        .dist-stats-row>div:nth-child(9) .dist-stat {
            background: linear-gradient(140deg, #4f46e5 0%, #4338ca 50%, #3730a3 100%);
        }

        .dist-stats-row>div:nth-child(10) .dist-stat {
            background: linear-gradient(140deg, #0d9488 0%, #0f766e 55%, #115e59 100%);
        }

        .dist-table {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
        }
    </style>

    <div class="container-fluid py-2 dist-shell">
        <div class="dist-hero">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div>
                        @if ($distributor->image)
                            <img src="{{ asset('storage/' . $distributor->image) }}" alt="صورة المندوب"
                                style="width:72px;height:72px;object-fit:cover;border-radius:50%;border:2px solid rgba(255,255,255,.35);">
                        @else
                            <div class="border rounded-circle d-flex align-items-center justify-content-center text-white-50"
                                style="width:72px;height:72px;border-color:rgba(255,255,255,.35)!important;">-</div>
                        @endif
                    </div>
                    <div>
                        <h1 class="h4 fw-bold mb-1">مرحبًا {{ $distributor->name }}</h1>
                        <div class="d-flex align-items-center gap-2">
                            @if ($distributor->supplier?->logo_url)
                                <img src="{{ $distributor->supplier->logo_url }}" alt="لوجو نشاط الوكيل"
                                    style="width:40px;height:40px;object-fit:cover;border-radius:8px;border:1px solid rgba(255,255,255,.35);background:#fff;">
                            @endif
                            <p class="mb-0 text-white-50">
                                {{ $distributor->supplier?->business_name ?? $distributor->supplier?->owner_name }}</p>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3 mt-md-0">
                    <a href="{{ route('distributor.orders.index') }}" class="btn btn-light btn-sm">طلباتي</a>
                    <a href="{{ route('distributor.payments.index') }}" class="btn btn-outline-light btn-sm">التحصيل</a>
                </div>
            </div>
        </div>

        <div class="row g-3 dist-stats-row">
            <div class="col-md-3 col-6">
                <div class="dist-stat">
                    <div class="text-muted small">أماكن التوزيع</div>
                    <div class="fw-semibold" style="white-space: pre-line;">
                        {{ $distributor->distribution_points ?: '-' }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="dist-stat">
                    <div class="text-muted small">رقم الهاتف</div>
                    <div class="fw-semibold">{{ $distributor->phone }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="dist-stat">
                    <div class="text-muted small">الفرع</div>
                    <div class="fw-semibold">{{ $distributor->branch?->name ?? '-' }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="dist-stat">
                    <div class="text-muted small">الحالة</div>
                    <div class="fw-semibold">{{ $distributor->status === 'active' ? 'مفعل' : 'معطل' }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="dist-stat">
                    <div class="text-muted small">إجمالي الطلبات</div>
                    <div class="fw-semibold fs-5">{{ $stats['orders_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="dist-stat">
                    <div class="text-muted small">طلبات جديدة مخصصة</div>
                    <div class="fw-semibold fs-5">{{ $stats['new_assigned_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="dist-stat">
                    <div class="text-muted small">طلبات قيد التوصيل</div>
                    <div class="fw-semibold fs-5">{{ $stats['in_delivery_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="dist-stat">
                    <div class="text-muted small">طلبات مسلّمة</div>
                    <div class="fw-semibold fs-5">{{ $stats['delivered_orders_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="dist-stat">
                    <div class="text-muted small">عدد طلبات اليوم</div>
                    <div class="fw-semibold fs-5">{{ $stats['today_orders_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="dist-stat">
                    <div class="text-muted small">تحصيلات اليوم</div>
                    <div class="fw-semibold fs-5">{{ number_format((float) $stats['today_collections'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-lg-6">
                <div class="dist-table h-100">
                    <div class="p-3 border-bottom fw-semibold">إدارة اليوم</div>
                    <div class="p-3">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="dist-stat">
                                    <div class="text-muted small">طلبات منجزة اليوم</div>
                                    <div class="fw-semibold fs-5">{{ $activity['completed_today'] }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="dist-stat">
                                    <div class="text-muted small">طلبات مقبولة اليوم</div>
                                    <div class="fw-semibold fs-5">{{ $activity['accepted_today'] }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="dist-stat">
                                    <div class="text-muted small">ساعات العمل اليوم</div>
                                    <div class="fw-semibold fs-5">{{ number_format((float) $activity['work_hours'], 2) }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="dist-stat">
                                    <div class="text-muted small">نسبة الإنجاز</div>
                                    <div class="fw-semibold fs-5">
                                        {{ number_format((float) $activity['performance_rate'], 1) }}%</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="dist-stat">
                                    <div class="text-muted small">تسليم ضمن الوقت (اليوم)</div>
                                    <div class="fw-semibold fs-5">
                                        {{ number_format((float) $activity['on_time_deliveries_today']) }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="dist-stat">
                                    <div class="text-muted small">تسليم متأخر (اليوم)</div>
                                    <div class="fw-semibold fs-5">
                                        {{ number_format((float) $activity['late_deliveries_today']) }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="dist-stat">
                                    <div class="text-muted small">نسبة الالتزام الزمني</div>
                                    <div class="fw-semibold fs-5">
                                        {{ number_format((float) $activity['on_time_rate_today'], 1) }}%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="dist-table h-100">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">تنبيهات المندوب</span>
                        <div class="d-flex gap-2 align-items-center">
                            <span class="badge text-bg-info">{{ $unreadAlertsCount }}</span>
                            <a href="{{ route('distributor.alerts.index') }}" class="btn btn-sm btn-outline-dark">عرض
                                الكل</a>
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
                                                action="{{ route('distributor.alerts.mark-read', $alert->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-outline-primary">تعليم</button>
                                            </form>
                                        @endif
                                    </div>
                                </li>
                            @empty
                                <li class="list-group-item px-0 text-muted">لا توجد تنبيهات حالياً.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="dist-table mt-3">
            <div class="p-3 border-bottom fw-semibold">لوحة المهام السريعة</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>العميل</th>
                            <th>العنوان</th>
                            <th>الهاتف</th>
                            <th>مرحلة المندوب</th>
                            <th>التاريخ</th>
                            <th>الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentOrders as $order)
                            <tr>
                                <td>{{ $order->id }}</td>
                                <td>{{ $order->customer_name }}</td>
                                <td>{{ $order->customer_address }}</td>
                                <td dir="ltr">{{ $order->customer_phone }}</td>
                                <td>{{ $order->distributor_stage ?: 'assigned' }}</td>
                                <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    <a href="{{ route('distributor.orders.show', $order) }}"
                                        class="btn btn-sm btn-outline-primary">تفاصيل</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">لا توجد طلبات حديثة.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
