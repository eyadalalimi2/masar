@extends('pos.layout.app')

@section('title', 'لوحة التحكم')

@section('content')
    <div class="hero-box reveal rv1">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h1 class="h4 mb-1">لوحة المحل التجاري: {{ $pos->name }}</h1>
                <p class="mb-0 text-white-50">متابعة الطلبات والتشغيل والمبيعات اليومية من مكان واحد.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('pos.marketplace.index') }}" class="btn btn-light btn-sm">السوق</a>
                <a href="{{ route('pos.orders.index') }}" class="btn btn-outline-light btn-sm">الطلبات</a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4 col-xl-2 reveal rv1">
            <div class="stat-card">
                <div class="small subtle-text">طلبات جديدة</div>
                <div class="fs-4 fw-bold">{{ number_format($stats['new_orders']) }}</div>
            </div>
        </div>
        <div class="col-md-4 col-xl-2 reveal rv1">
            <div class="stat-card">
                <div class="small subtle-text">طلبات نشطة</div>
                <div class="fs-4 fw-bold">{{ number_format($stats['active_orders']) }}</div>
            </div>
        </div>
        <div class="col-md-4 col-xl-2 reveal rv2">
            <div class="stat-card">
                <div class="small subtle-text">طلبات مكتملة</div>
                <div class="fs-4 fw-bold">{{ number_format($stats['delivered_orders']) }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 reveal rv2">
            <div class="stat-card">
                <div class="small subtle-text">مبيعات اليوم</div>
                <div class="fs-4 fw-bold">{{ number_format($stats['today_sales'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 reveal rv3">
            <div class="stat-card">
                <div class="small subtle-text">ربح اليوم</div>
                <div class="fs-4 fw-bold">{{ number_format($stats['today_profit'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 reveal rv3">
            <div class="stat-card">
                <div class="small subtle-text">أصناف مهددة بالنفاد</div>
                <div class="d-flex align-items-center justify-content-between">
                    <div class="fs-4 fw-bold">{{ number_format((int) ($stats['predicted_stockout_count'] ?? 0)) }}</div>
                    <a href="{{ route('pos.catalog.index') }}" class="btn btn-sm btn-outline-dark">عرض</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7 reveal rv3">
            <div class="table-wrap h-100">
                <div class="p-3 border-bottom fw-semibold">أكثر المنتجات مبيعًا</div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>المنتج</th>
                                <th>الكمية</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topProducts as $row)
                                <tr>
                                    <td>{{ $row->product_name }}</td>
                                    <td>{{ number_format((float) $row->sold_quantity, 3) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">لا توجد بيانات بعد.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5 reveal rv3">
            <div class="table-wrap h-100">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-bold">آخر التنبيهات</span>
                    <a href="{{ route('pos.alerts.index') }}" class="btn btn-sm btn-outline-dark">عرض الكل
                        ({{ $unreadAlertsCount }})</a>
                </div>
                <div class="p-3">
                    <ul class="list-group list-group-flush">
                        @forelse($recentAlerts as $alert)
                            <li class="list-group-item px-0">
                                <div class="fw-semibold">{{ $alert->title }}</div>
                                <div class="small text-muted">{{ $alert->body }}</div>
                                <div class="small text-secondary mt-1">{{ $alert->created_at?->format('Y-m-d H:i') }}</div>
                            </li>
                        @empty
                            <li class="list-group-item px-0 text-muted">لا توجد تنبيهات حاليا.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
