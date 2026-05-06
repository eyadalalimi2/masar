@extends('branch.layout.app')

@section('title', 'لوحة الفرع')

@section('content')
    <style>
        .page-shell {
            background: radial-gradient(circle at 5% 0%, #f0f9ff 0%, #ffffff 42%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 16px;
        }

        .hero-box {
            border-radius: 16px;
            background: linear-gradient(135deg, #111827 0%, #2563eb 100%);
            color: #fff;
            padding: 16px;
            margin-bottom: 14px;
        }

        .stat-card {
            border: 1px solid rgba(148, 163, 184, 0.28);
            border-radius: 14px;
            background: linear-gradient(140deg, #334155 0%, #1e293b 100%);
            padding: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -20px;
            left: -24px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.14);
        }

        .stat-card .text-muted {
            color: rgba(255, 255, 255, 0.9) !important;
            position: relative;
            z-index: 1;
        }

        .stat-card .fs-4 {
            color: #ffffff;
            position: relative;
            z-index: 1;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .page-shell .row.g-3.mb-4>div:nth-child(1) .stat-card {
            background: linear-gradient(140deg, #2563eb 0%, #1d4ed8 55%, #1e40af 100%);
        }

        .page-shell .row.g-3.mb-4>div:nth-child(2) .stat-card {
            background: linear-gradient(140deg, #059669 0%, #047857 55%, #065f46 100%);
        }

        .page-shell .row.g-3.mb-4>div:nth-child(3) .stat-card {
            background: linear-gradient(140deg, #d97706 0%, #b45309 55%, #92400e 100%);
        }

        .page-shell .row.g-3.mb-4>div:nth-child(4) .stat-card {
            background: linear-gradient(140deg, #db2777 0%, #be185d 55%, #9d174d 100%);
        }

        .page-shell .row.g-3.mb-4>div:nth-child(5) .stat-card {
            background: linear-gradient(140deg, #7c3aed 0%, #6d28d9 55%, #5b21b6 100%);
        }

        .page-shell .row.g-3.mb-4>div:nth-child(6) .stat-card {
            background: linear-gradient(140deg, #0891b2 0%, #0e7490 55%, #155e75 100%);
        }

        .table-wrap {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
        }

        .reveal {
            opacity: 0;
            transform: translateY(10px);
            animation: reveal .45s ease-out forwards;
        }

        .rv1 {
            animation-delay: .05s;
        }

        .rv2 {
            animation-delay: .12s;
        }

        .rv3 {
            animation-delay: .18s;
        }

        @keyframes reveal {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <div class="container-fluid py-2 page-shell">
        <div class="hero-box reveal rv1">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h1 class="h4 mb-1">لوحة الفرع: {{ $branch->name }}</h1>
                    <p class="mb-0 text-white-50">إدارة ومتابعة أداء الفرع والطلبات اليومية</p>
                    <div class="d-flex align-items-center gap-2 mt-2">
                        @if ($branch->supplier?->logo_url)
                            <img src="{{ $branch->supplier->logo_url }}" alt="لوجو نشاط الوكيل"
                                style="width:40px;height:40px;object-fit:cover;border-radius:8px;border:1px solid rgba(255,255,255,.35);background:#fff;">
                        @endif
                        <span class="badge bg-light text-dark">
                            {{ $branch->supplier?->business_name ?? ($branch->supplier?->owner_name ?? '-') }}
                        </span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('branch.orders.index') }}" class="btn btn-light btn-sm">إدارة الطلبات</a>
                    <a href="{{ route('branch.payments.index') }}" class="btn btn-outline-light btn-sm">التحصيلات</a>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6 reveal rv1">
                <div class="stat-card">
                    <div class="text-muted small">إجمالي الطلبات</div>
                    <div class="fs-4 fw-bold">{{ $stats['orders_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6 reveal rv1">
                <div class="stat-card">
                    <div class="text-muted small">الطلبات الجديدة</div>
                    <div class="fs-4 fw-bold">{{ $stats['new_orders_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6 reveal rv1">
                <div class="stat-card">
                    <div class="text-muted small">طلبات قيد التنفيذ</div>
                    <div class="fs-4 fw-bold">{{ $stats['pending_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6 reveal rv2">
                <div class="stat-card">
                    <div class="text-muted small">طلبات مسلّمة</div>
                    <div class="fs-4 fw-bold">{{ $stats['delivered_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6 reveal rv2">
                <div class="stat-card">
                    <div class="text-muted small">قيد التوصيل</div>
                    <div class="fs-4 fw-bold">{{ $stats['out_for_delivery_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6 reveal rv2">
                <div class="stat-card">
                    <div class="text-muted small">صافي مبيعات اليوم</div>
                    <div class="fs-4 fw-bold">{{ number_format((float) $stats['today_sales'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="table-wrap reveal rv3">
            <table class="table table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>العميل</th>
                        <th>الهاتف</th>
                        <th>الإجمالي</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                        <tr>
                            <td>{{ $order->id }}</td>
                            <td>{{ $order->customer_name }}</td>
                            <td>{{ $order->customer_phone }}</td>
                            <td>{{ number_format((float) ($order->payable_total ?? $order->total_price), 2) }}</td>
                            <td>{{ \App\Support\StatusLabel::order($order->status) }}</td>
                            <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">لا توجد طلبات مرتبطة بهذا الفرع حتى
                                الآن.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6 reveal rv2">
                <div class="table-wrap">
                    <div class="p-3 border-bottom fw-semibold">تنبيهات المخزون</div>
                    <div class="p-3">
                        <ul class="list-group list-group-flush">
                            @forelse ($lowStockAlerts as $stock)
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>{{ $stock->product?->name }} ({{ $stock->productUnit?->unit?->name }})</span>
                                    <span
                                        class="badge text-bg-warning">{{ number_format((float) $stock->quantity, 3) }}</span>
                                </li>
                            @empty
                                <li class="list-group-item px-0 text-muted">لا يوجد تنبيهات مخزون حاليًا.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 reveal rv3">
                <div class="table-wrap">
                    <div class="p-3 border-bottom fw-semibold">أفضل أداء للمندوبين</div>
                    <div class="p-3">
                        <ul class="list-group list-group-flush">
                            @forelse ($topDistributorPerformance as $row)
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>{{ $row->name }}</span>
                                    <span class="badge text-bg-primary">{{ (int) $row->delivered_orders }} طلب</span>
                                </li>
                            @empty
                                <li class="list-group-item px-0 text-muted">لا توجد بيانات كافية بعد.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-wrap mt-4 reveal rv3">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h2 class="h6 fw-bold mb-0">تنبيهات النظام</h2>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge text-bg-info">غير مقروء: {{ $unreadAlertsCount }}</span>
                    <a href="{{ route('branch.alerts.index') }}" class="btn btn-sm btn-outline-dark">عرض الكل</a>
                </div>
            </div>
            <div class="p-3">
                <ul class="list-group list-group-flush">
                    @forelse ($recentAlerts as $alert)
                        <li class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <div class="fw-semibold">{{ $alert->title }}</div>
                                    <div class="small text-muted">{{ $alert->body }}</div>
                                    <div class="small text-secondary mt-1">{{ $alert->created_at?->format('Y-m-d H:i') }}
                                    </div>
                                </div>
                                @if (!$alert->read_at)
                                    <form method="POST" action="{{ route('branch.alerts.mark-read', $alert->id) }}">
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

        <div class="table-wrap mt-4 reveal rv3">
            <div class="p-3 border-bottom">
                <h2 class="h6 fw-bold mb-0">مندوبي الفرع</h2>
            </div>
            <table class="table table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>الصورة</th>
                        <th>الاسم</th>
                        <th>الهاتف</th>
                        <th>أماكن التوزيع</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($branchDistributors as $distributor)
                        <tr>
                            <td style="width:90px;">
                                @if ($distributor->image)
                                    <img src="{{ asset('storage/' . $distributor->image) }}" alt="صورة المندوب"
                                        style="width:52px;height:52px;object-fit:cover;border-radius:50%;">
                                @else
                                    <span class="text-muted small">لا يوجد</span>
                                @endif
                            </td>
                            <td>{{ $distributor->name }}</td>
                            <td>{{ $distributor->phone }}</td>
                            <td style="white-space: pre-line;">{{ $distributor->distribution_points ?: '-' }}</td>
                            <td>{{ $distributor->status === 'active' ? 'مفعل' : 'معطل' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">لا يوجد مندوبون مرتبطون بهذا الفرع
                                حالياً.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
