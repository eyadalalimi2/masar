@extends('admin.layout.app')

@section('content')
    <style>
        .admin-dash-shell {
            background: radial-gradient(circle at 15% 0%, #eef6ff 0%, #f8fafc 40%, #ffffff 100%);
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 18px;
        }

        .admin-dash-hero {
            border-radius: 16px;
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);
            color: #fff;
            position: relative;
            overflow: hidden;
            padding: 18px;
            margin-bottom: 14px;
        }

        .admin-dash-hero::after {
            content: '';
            position: absolute;
            top: -34px;
            left: -22px;
            width: 160px;
            height: 160px;
            background: rgba(255, 255, 255, .14);
            border-radius: 50%;
        }

        .admin-kpi-card {
            border: 1px solid rgba(148, 163, 184, 0.28);
            border-radius: 14px;
            background: linear-gradient(140deg, #334155 0%, #1e293b 100%);
            padding: 16px;
            height: 100%;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            position: relative;
            overflow: hidden;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease, filter .18s ease;
        }

        .admin-kpi-card::before {
            content: '';
            position: absolute;
            top: -24px;
            left: -28px;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.14);
            pointer-events: none;
        }

        .admin-kpi-link {
            text-decoration: none;
            display: block;
            height: 100%;
        }

        .admin-kpi-link:hover .admin-kpi-card {
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.45);
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.18);
            filter: saturate(1.12) contrast(1.04);
        }

        .admin-kpi-label {
            color: rgba(255, 255, 255, 0.9);
            font-size: .85rem;
            margin-bottom: 2px;
            position: relative;
            z-index: 1;
        }

        .admin-kpi-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 6px;
            position: relative;
            z-index: 1;
        }

        .admin-kpi-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.24);
            color: #fff;
            flex-shrink: 0;
            backdrop-filter: blur(1px);
        }

        .admin-kpi-icon svg {
            width: 22px;
            height: 22px;
            stroke: currentColor;
        }

        .admin-kpi-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: #ffffff;
            position: relative;
            z-index: 1;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .admin-kpi-link:hover .admin-kpi-value {
            color: #ffffff;
        }

        .kpi-gradient-1 {
            background: linear-gradient(140deg, #2563eb 0%, #1d4ed8 55%, #1e40af 100%);
        }

        .kpi-gradient-2 {
            background: linear-gradient(140deg, #059669 0%, #047857 55%, #065f46 100%);
        }

        .kpi-gradient-3 {
            background: linear-gradient(140deg, #d97706 0%, #b45309 55%, #92400e 100%);
        }

        .kpi-gradient-4 {
            background: linear-gradient(140deg, #db2777 0%, #be185d 55%, #9d174d 100%);
        }

        .kpi-gradient-5 {
            background: linear-gradient(140deg, #dc2626 0%, #b91c1c 55%, #991b1b 100%);
        }

        .kpi-gradient-6 {
            background: linear-gradient(140deg, #7c3aed 0%, #6d28d9 55%, #5b21b6 100%);
        }

        .kpi-gradient-7 {
            background: linear-gradient(140deg, #0891b2 0%, #0e7490 55%, #155e75 100%);
        }

        .kpi-gradient-8 {
            background: linear-gradient(140deg, #ca8a04 0%, #a16207 55%, #854d0e 100%);
        }

        .kpi-gradient-9 {
            background: linear-gradient(140deg, #4f46e5 0%, #4338ca 50%, #3730a3 100%);
        }

        .kpi-gradient-10 {
            background: linear-gradient(140deg, #0d9488 0%, #0f766e 55%, #115e59 100%);
        }

        .admin-panel {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
            height: 100%;
        }

        .admin-action-link {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
            padding: 12px;
            text-decoration: none;
            color: #0f172a;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all .18s ease;
        }

        .admin-action-link:hover {
            transform: translateY(-1px);
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #1e3a8a;
        }

        .admin-reveal {
            opacity: 0;
            transform: translateY(8px);
            animation: adminDashReveal .45s ease-out forwards;
        }

        .d1 {
            animation-delay: .05s;
        }

        .d2 {
            animation-delay: .12s;
        }

        .d3 {
            animation-delay: .18s;
        }

        @keyframes adminDashReveal {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .admin-role-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 3px 10px;
            font-size: .75rem;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .role-supplier {
            background: #dcfce7;
            color: #166534;
            border-color: #bbf7d0;
        }

        .role-branch {
            background: #dbeafe;
            color: #1e3a8a;
            border-color: #bfdbfe;
        }

        .role-distributor {
            background: #fef3c7;
            color: #92400e;
            border-color: #fde68a;
        }
    </style>

    <div class="container-fluid admin-dash-shell">
        <div class="admin-dash-hero admin-reveal d1">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 position-relative"
                style="z-index:1;">
                <div>
                    <h1 class="h4 mb-1">لوحة تحكم الإدارة</h1>
                    <p class="mb-0 text-white-50">نظرة مركزية على أداء النظام والطلبات والمستخدمين.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-light btn-sm">الطلبات</a>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-light btn-sm">المستخدمون</a>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6 admin-reveal d1">
                <a href="{{ route('admin.suppliers.index') }}" class="admin-kpi-link">
                    <div class="admin-kpi-card kpi-gradient-1">
                        <div class="admin-kpi-head">
                            <div class="admin-kpi-label">الوكلاء</div>
                            <span class="admin-kpi-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="8.5" cy="7" r="3.5" />
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M15 3.13a3.5 3.5 0 0 1 0 6.74" />
                                </svg>
                            </span>
                        </div>
                        <div class="admin-kpi-value">{{ $stats['suppliers_count'] }}</div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6 admin-reveal d1">
                <a href="{{ route('admin.branches.index') }}" class="admin-kpi-link">
                    <div class="admin-kpi-card kpi-gradient-2">
                        <div class="admin-kpi-head">
                            <div class="admin-kpi-label">الفروع</div>
                            <span class="admin-kpi-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2" />
                                    <path d="M9 21V9h6v12" />
                                    <path d="M7 7h.01M12 7h.01M17 7h.01" />
                                </svg>
                            </span>
                        </div>
                        <div class="admin-kpi-value">{{ $stats['branches_count'] }}</div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6 admin-reveal d2">
                <a href="{{ route('admin.distributors.index') }}" class="admin-kpi-link">
                    <div class="admin-kpi-card kpi-gradient-3">
                        <div class="admin-kpi-head">
                            <div class="admin-kpi-label">المندوبون</div>
                            <span class="admin-kpi-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M10 17h4V5H2v12h3" />
                                    <path d="M14 8h5l3 3v6h-2" />
                                    <circle cx="7" cy="17" r="2" />
                                    <circle cx="17" cy="17" r="2" />
                                </svg>
                            </span>
                        </div>
                        <div class="admin-kpi-value">{{ $stats['distributors_count'] }}</div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6 admin-reveal d2">
                <a href="{{ route('admin.commercial-stores.index') }}" class="admin-kpi-link">
                    <div class="admin-kpi-card kpi-gradient-4">
                        <div class="admin-kpi-head">
                            <div class="admin-kpi-label">المحلات التجارية</div>
                            <span class="admin-kpi-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M3 9l2-5h14l2 5" />
                                    <path d="M4 9h16v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z" />
                                    <path d="M9 21v-6h6v6" />
                                </svg>
                            </span>
                        </div>
                        <div class="admin-kpi-value">{{ $stats['commercial_stores_count'] }}</div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6 admin-reveal d2">
                <a href="{{ route('admin.workshops.index') }}" class="admin-kpi-link">
                    <div class="admin-kpi-card kpi-gradient-5">
                        <div class="admin-kpi-head">
                            <div class="admin-kpi-label">ورش الصيانة</div>
                            <span class="admin-kpi-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path
                                        d="M14.7 6.3a4 4 0 0 1-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 1 5.4-5.4l-3 3 2 2 3-3a4 4 0 0 1-5.3-5.3z" />
                                </svg>
                            </span>
                        </div>
                        <div class="admin-kpi-value">{{ $stats['workshops_count'] }}</div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6 admin-reveal d2">
                <a href="{{ route('admin.products.index') }}" class="admin-kpi-link">
                    <div class="admin-kpi-card kpi-gradient-6">
                        <div class="admin-kpi-head">
                            <div class="admin-kpi-label">المنتجات</div>
                            <span class="admin-kpi-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path
                                        d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                                    <path d="M3.3 7l8.7 5 8.7-5" />
                                    <path d="M12 22V12" />
                                </svg>
                            </span>
                        </div>
                        <div class="admin-kpi-value">{{ $stats['products_count'] }}</div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6 admin-reveal d2">
                <a href="{{ route('admin.orders.index') }}" class="admin-kpi-link">
                    <div class="admin-kpi-card kpi-gradient-7">
                        <div class="admin-kpi-head">
                            <div class="admin-kpi-label">طلبات اليوم</div>
                            <span class="admin-kpi-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8l-6-5H9z" />
                                    <path d="M9 3v5h6" />
                                    <path d="M8 13h8M8 17h8" />
                                </svg>
                            </span>
                        </div>
                        <div class="admin-kpi-value">{{ $stats['today_orders_count'] }}</div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6 admin-reveal d2">
                <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="admin-kpi-link">
                    <div class="admin-kpi-card kpi-gradient-8">
                        <div class="admin-kpi-head">
                            <div class="admin-kpi-label">طلبات قيد التنفيذ</div>
                            <span class="admin-kpi-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 7v5l3 3" />
                                </svg>
                            </span>
                        </div>
                        <div class="admin-kpi-value">{{ $stats['pending_orders_count'] }}</div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6 admin-reveal d3">
                <a href="{{ route('admin.reports.index') }}" class="admin-kpi-link">
                    <div class="admin-kpi-card kpi-gradient-9">
                        <div class="admin-kpi-head">
                            <div class="admin-kpi-label">صافي مبيعات اليوم</div>
                            <span class="admin-kpi-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M3 3v18h18" />
                                    <path d="M7 14l4-4 3 3 5-6" />
                                </svg>
                            </span>
                        </div>
                        <div class="admin-kpi-value">{{ number_format((float) $stats['today_sales'], 2) }}</div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6 admin-reveal d3">
                <a href="{{ route('admin.payments.index') }}" class="admin-kpi-link">
                    <div class="admin-kpi-card kpi-gradient-10">
                        <div class="admin-kpi-head">
                            <div class="admin-kpi-label">مدفوعات مسددة</div>
                            <span class="admin-kpi-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <rect x="2" y="5" width="20" height="14" rx="2" />
                                    <path d="M2 10h20" />
                                    <path d="M16 15l2 2 4-4" />
                                </svg>
                            </span>
                        </div>
                        <div class="admin-kpi-value">{{ number_format((float) $stats['payments_paid'], 2) }}</div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6 admin-reveal d3">
                <div class="admin-kpi-card kpi-gradient-2">
                    <div class="admin-kpi-head">
                        <div class="admin-kpi-label">المستخدمون النشطون</div>
                        <span class="admin-kpi-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="8.5" cy="7" r="3.5" />
                                <path d="M20 8v6" />
                                <path d="M23 11h-6" />
                            </svg>
                        </span>
                    </div>
                    <div class="admin-kpi-value">{{ $stats['active_users_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6 admin-reveal d3">
                <a href="{{ route('admin.content.index') }}" class="admin-kpi-link">
                    <div class="admin-kpi-card kpi-gradient-5">
                        <div class="admin-kpi-head">
                            <div class="admin-kpi-label">تنبيهات مهمة (غير مقروءة)</div>
                            <span class="admin-kpi-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M12 9v4" />
                                    <path d="M12 17h.01" />
                                    <path
                                        d="M10.29 3.86l-7.5 13a2 2 0 0 0 1.71 3h15a2 2 0 0 0 1.71-3l-7.5-13a2 2 0 0 0-3.42 0z" />
                                </svg>
                            </span>
                        </div>
                        <div class="admin-kpi-value">{{ $stats['important_alerts_count'] }}</div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6 admin-reveal d3">
                <a href="{{ route('admin.orders.index', ['delayed_only' => 1]) }}" class="admin-kpi-link">
                    <div class="admin-kpi-card kpi-gradient-8">
                        <div class="admin-kpi-head">
                            <div class="admin-kpi-label">الطلبات المتأخرة</div>
                            <span class="admin-kpi-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 7v5l3 3" />
                                </svg>
                            </span>
                        </div>
                        <div class="admin-kpi-value">{{ number_format((int) ($stats['delayed_orders_count'] ?? 0)) }}
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6 admin-reveal d3">
                <a href="{{ route('admin.orders.index', ['delayed_only' => 1]) }}" class="admin-kpi-link">
                    <div class="admin-kpi-card kpi-gradient-10">
                        <div class="admin-kpi-head">
                            <div class="admin-kpi-label">تنبيهات التأخير اليوم</div>
                            <span class="admin-kpi-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M12 9v4" />
                                    <path d="M12 17h.01" />
                                    <path
                                        d="M10.29 3.86l-7.5 13a2 2 0 0 0 1.71 3h15a2 2 0 0 0 1.71-3l-7.5-13a2 2 0 0 0-3.42 0z" />
                                </svg>
                            </span>
                        </div>
                        <div class="admin-kpi-value">
                            {{ number_format((int) ($stats['admin_delay_alerts_today_count'] ?? 0)) }}</div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 admin-reveal d3">
                <div class="admin-panel p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div class="fw-semibold">المراقبة الحية (تحديث تلقائي كل 30 ثانية)</div>
                        <span id="admin-live-updated-at" class="small text-muted">آخر تحديث: الآن</span>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-2 col-6">
                            <div class="border rounded-3 p-3 h-100 bg-light">
                                <div class="small text-muted">نشطة الآن</div>
                                <div class="fs-5 fw-bold" data-live-key="active_orders_now">
                                    {{ number_format((int) ($realtime['active_orders_now'] ?? 0)) }}</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="border rounded-3 p-3 h-100 bg-light">
                                <div class="small text-muted">خارج للتوصيل</div>
                                <div class="fs-5 fw-bold" data-live-key="out_for_delivery_now">
                                    {{ number_format((int) ($realtime['out_for_delivery_now'] ?? 0)) }}</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="border rounded-3 p-3 h-100 bg-light">
                                <div class="small text-muted">تم تسليمه اليوم</div>
                                <div class="fs-5 fw-bold" data-live-key="delivered_today">
                                    {{ number_format((int) ($realtime['delivered_today'] ?? 0)) }}</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="border rounded-3 p-3 h-100 bg-light">
                                <div class="small text-muted">صافي مبيعات اليوم</div>
                                <div class="fs-5 fw-bold" data-live-key="sales_today">
                                    {{ number_format((float) ($realtime['sales_today'] ?? 0), 2) }}</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="border rounded-3 p-3 h-100 bg-light">
                                <div class="small text-muted">مستخدمون جدد اليوم</div>
                                <div class="fs-5 fw-bold" data-live-key="new_users_today">
                                    {{ number_format((int) ($realtime['new_users_today'] ?? 0)) }}</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="border rounded-3 p-3 h-100 bg-light">
                                <div class="small text-muted">متأخرة الآن</div>
                                <div class="fs-5 fw-bold text-warning" data-live-key="delayed_orders_now">
                                    {{ number_format((int) ($realtime['delayed_orders_now'] ?? 0)) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-8 admin-reveal d3">
                <div class="admin-panel">
                    <div class="p-3 border-bottom fw-semibold">آخر الطلبات</div>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>العميل</th>
                                    <th>الوكيل</th>
                                    <th>الإجمالي</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($latestOrders as $order)
                                    <tr>
                                        <td>{{ $order->id }}</td>
                                        <td>{{ $order->customer_name }}</td>
                                        <td>{{ $order->supplier?->business_name ?? ($order->supplier?->owner_name ?? '-') }}
                                        </td>
                                        <td>{{ number_format((float) ($order->payable_total ?? $order->total_price), 2) }}
                                        </td>
                                        <td>
                                            @php
                                                $statusLabel = match ($order->status) {
                                                    'pending' => 'قيد الانتظار',
                                                    'approved' => 'معتمد',
                                                    'out_for_delivery' => 'خرج للتوصيل',
                                                    'delivered' => 'تم التسليم',
                                                    'cancelled' => 'ملغي',
                                                    default => $order->status,
                                                };
                                            @endphp
                                            <span class="badge text-bg-light border">{{ $statusLabel }}</span>
                                        </td>
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

            <div class="col-lg-4 admin-reveal d3">
                <div class="admin-panel p-3">
                    <div class="fw-semibold mb-3">اختصارات سريعة</div>
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.tasks.index') }}" class="admin-action-link">
                            <span>إدارة المهام</span>
                            <span>←</span>
                        </a>
                        <a href="{{ route('admin.orders.index') }}" class="admin-action-link">
                            <span>مراجعة الطلبات</span>
                            <span>←</span>
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="admin-action-link">
                            <span>إدارة المستخدمين</span>
                            <span>←</span>
                        </a>
                        <a href="{{ route('admin.reports.index') }}" class="admin-action-link">
                            <span>التقارير</span>
                            <span>←</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 admin-reveal d3">
                <div class="admin-panel">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">أكثر الطلبات تأخرًا (حرجة)</span>
                        <a href="{{ route('admin.orders.index', ['delayed_only' => 1]) }}"
                            class="btn btn-sm btn-outline-warning">
                            عرض كل الطلبات المتأخرة
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>العميل</th>
                                    <th>الوكيل</th>
                                    <th>الحالة</th>
                                    <th>ساعات التأخير التقريبية</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($criticalDelayedOrders as $order)
                                    <tr>
                                        <td>{{ $order->id }}</td>
                                        <td>{{ $order->customer_name }}</td>
                                        <td>{{ $order->supplier?->business_name ?? ($order->supplier?->owner_name ?? '-') }}
                                        </td>
                                        <td><span
                                                class="badge text-bg-warning">{{ \App\Support\StatusLabel::order($order->status) }}</span>
                                        </td>
                                        <td>{{ number_format((int) $order->updated_at->diffInHours(now())) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">لا توجد طلبات حرجة متأخرة
                                            حاليًا.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 admin-reveal d3">
                <div class="admin-panel p-3">
                    <div class="fw-semibold mb-3">تفصيل التأخير حسب المرحلة</div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100 bg-light">
                                <div class="small text-muted">تأخير مرحلة الوكيل</div>
                                <div class="fs-5 fw-bold">
                                    {{ number_format((int) ($stats['supplier_stage_delays'] ?? 0)) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100 bg-light">
                                <div class="small text-muted">تأخير مرحلة الفرع</div>
                                <div class="fs-5 fw-bold">{{ number_format((int) ($stats['branch_stage_delays'] ?? 0)) }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100 bg-light">
                                <div class="small text-muted">تأخير مرحلة التوصيل</div>
                                <div class="fs-5 fw-bold">
                                    {{ number_format((int) ($stats['delivery_stage_delays'] ?? 0)) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6 admin-reveal d3">
                <div class="admin-panel">
                    <div class="p-3 border-bottom fw-semibold">التنبيهات المهمة</div>
                    <div class="list-group list-group-flush">
                        @forelse ($importantAlerts as $alert)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-semibold">{{ $alert->title }}</div>
                                        <div class="small text-muted">{{ $alert->body }}</div>
                                        <div class="small text-muted mt-1">{{ $alert->recipient_type }}</div>
                                    </div>
                                    <span class="small text-muted">{{ $alert->created_at?->diffForHumans() }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="list-group-item text-muted">لا توجد تنبيهات مهمة غير مقروءة.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-6 admin-reveal d3">
                <div class="admin-panel">
                    <div class="p-3 border-bottom fw-semibold">آخر المستخدمين</div>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>الاسم</th>
                                    <th>الهاتف</th>
                                    <th>النوع</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($latestUsers as $user)
                                    <tr>
                                        <td>{{ $user->name }}</td>
                                        <td dir="ltr">{{ $user->phone }}</td>
                                        <td>
                                            @php
                                                $roleText =
                                                    $user->role === 'supplier'
                                                        ? 'وكيل'
                                                        : ($user->role === 'branch'
                                                            ? 'فرع'
                                                            : 'مندوب');
                                                $roleClass =
                                                    $user->role === 'supplier'
                                                        ? 'role-supplier'
                                                        : ($user->role === 'branch'
                                                            ? 'role-branch'
                                                            : 'role-distributor');
                                            @endphp
                                            <span class="admin-role-pill {{ $roleClass }}">{{ $roleText }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">لا يوجد مستخدمون حديثًا.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                const endpoint = @json(route('admin.dashboard.live-metrics'));
                const updatedAt = document.getElementById('admin-live-updated-at');

                function formatValue(key, value) {
                    if (key === 'sales_today') {
                        return Number(value || 0).toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    }

                    return Number(value || 0).toLocaleString();
                }

                function renderMetrics(metrics) {
                    document.querySelectorAll('[data-live-key]').forEach((node) => {
                        const key = node.getAttribute('data-live-key');
                        if (!key || !(key in metrics)) {
                            return;
                        }

                        node.textContent = formatValue(key, metrics[key]);
                    });

                    if (updatedAt) {
                        updatedAt.textContent = 'آخر تحديث: ' + new Date().toLocaleTimeString();
                    }
                }

                async function pollLiveMetrics() {
                    try {
                        const response = await fetch(endpoint, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin',
                        });

                        if (!response.ok) {
                            return;
                        }

                        const payload = await response.json();
                        if (payload && payload.success && payload.metrics) {
                            renderMetrics(payload.metrics);
                        }
                    } catch (error) {
                        // Silence intermittent network errors; next poll may recover.
                    }
                }

                setInterval(pollLiveMetrics, 30000);
                pollLiveMetrics();
            })();
        </script>
    @endpush
@endsection
