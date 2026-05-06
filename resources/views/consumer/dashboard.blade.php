@extends('consumer.layout.app')

@section('title', 'لوحة المستهلك الفردي')

@section('content')
    <style>
        .consumer-stat {
            border: 1px solid rgba(148, 163, 184, 0.28);
            border-radius: 14px;
            background: linear-gradient(140deg, #334155 0%, #1e293b 100%);
            padding: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .consumer-stat::before {
            content: '';
            position: absolute;
            top: -20px;
            left: -24px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.14);
        }

        .consumer-stat-label {
            color: rgba(255, 255, 255, 0.9);
            font-size: .85rem;
            position: relative;
            z-index: 1;
        }

        .consumer-stat-value {
            font-size: 1.45rem;
            font-weight: 700;
            color: #ffffff;
            position: relative;
            z-index: 1;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .consumer-stats-row>div:nth-child(1) .consumer-stat {
            background: linear-gradient(140deg, #2563eb 0%, #1d4ed8 55%, #1e40af 100%);
        }

        .consumer-stats-row>div:nth-child(2) .consumer-stat {
            background: linear-gradient(140deg, #d97706 0%, #b45309 55%, #92400e 100%);
        }

        .consumer-stats-row>div:nth-child(3) .consumer-stat {
            background: linear-gradient(140deg, #059669 0%, #047857 55%, #065f46 100%);
        }

        .consumer-stats-row>div:nth-child(4) .consumer-stat {
            background: linear-gradient(140deg, #7c3aed 0%, #6d28d9 55%, #5b21b6 100%);
        }
    </style>

    <div class="container-fluid py-2">
        <div class="p-4 rounded-4 text-white mb-3" style="background: linear-gradient(135deg, #0f172a 0%, #0f766e 100%);">
            <h1 class="h4 mb-1">مرحبًا {{ $consumer->name }}</h1>
            <p class="mb-0 text-white-50">متابعة طلباتك الفردية الخاصة بك</p>
        </div>

        <div class="row g-3 mb-3 consumer-stats-row">
            <div class="col-md-3 col-6">
                <div class="consumer-stat">
                    <div class="consumer-stat-label">إجمالي الطلبات</div>
                    <div class="consumer-stat-value">{{ $stats['orders_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="consumer-stat">
                    <div class="consumer-stat-label">طلبات قيد التنفيذ</div>
                    <div class="consumer-stat-value">{{ $stats['pending_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="consumer-stat">
                    <div class="consumer-stat-label">طلبات مسلمة</div>
                    <div class="consumer-stat-value">{{ $stats['delivered_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="consumer-stat">
                    <div class="consumer-stat-label">إجمالي الإنفاق الصافي</div>
                    <div class="consumer-stat-value">{{ number_format((float) $stats['total_spend'], 2) }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="consumer-stat">
                    <div class="consumer-stat-label">منتجات مقترحة لإعادة الطلب</div>
                    <div class="consumer-stat-value">{{ number_format((int) ($dueProductsCount ?? 0)) }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="consumer-stat">
                    <div class="consumer-stat-label">خدمات مقترحة لإعادة الطلب</div>
                    <div class="consumer-stat-value">{{ number_format((int) ($dueServicesCount ?? 0)) }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="consumer-stat">
                    <div class="consumer-stat-label">رصيد نقاط الولاء</div>
                    <div class="consumer-stat-value">{{ number_format((int) ($loyaltyBalance ?? 0)) }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="consumer-stat">
                    <div class="consumer-stat-label">عدد المركبات المحفوظة</div>
                    <div class="consumer-stat-value">{{ number_format((int) ($vehiclesCount ?? 0)) }}</div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-6">
                <div class="border rounded-4 bg-white p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="h6 mb-0">التنبيهات الذكية</h2>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge text-bg-primary">غير مقروء:
                                {{ (int) ($consumerUnreadAlertsCount ?? 0) }}</span>
                            @if ((int) ($consumerUnreadAlertsCount ?? 0) > 0)
                                <form method="POST" action="{{ route('consumer.alerts.read-all') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">تعليم الكل
                                        مقروء</button>
                                </form>
                            @endif
                        </div>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse ($recentConsumerAlerts as $alert)
                            <div class="list-group-item px-0">
                                <div class="fw-semibold">{{ $alert->title }}</div>
                                <div class="small text-muted">{{ $alert->body }}</div>
                                <div class="small text-secondary mt-1">{{ $alert->created_at?->diffForHumans() }}</div>
                            </div>
                        @empty
                            <div class="text-muted small">لا توجد تنبيهات حالية.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="border rounded-4 bg-white p-3 h-100">
                    <h2 class="h6 mb-2">إجراءات ذكية سريعة</h2>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('consumer.history') }}" class="btn btn-sm btn-outline-primary">الذهاب إلى السجل
                            وإعادة الطلب</a>
                        <a href="{{ route('consumer.browse') }}" class="btn btn-sm btn-outline-dark">تصفح العروض
                            والخدمات</a>
                        <a href="{{ route('consumer.home') }}" class="btn btn-sm btn-outline-secondary">العودة إلى
                            الرئيسية</a>
                    </div>
                    <div class="small text-muted mt-2">
                        يتم تحديث المؤشرات الذكية تلقائيا بناء على سجل الطلبات المكتملة.
                    </div>
                </div>
            </div>
        </div>

        <div class="border rounded-4 bg-white overflow-hidden">
            <div class="p-3 border-bottom fw-bold">آخر الطلبات</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>الإجمالي</th>
                            <th>نوع البائع</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
                            <tr>
                                <td>{{ $order->id }}</td>
                                <td>{{ number_format((float) ($order->payable_total ?? $order->total_price), 2) }}</td>
                                <td>{{ \App\Support\StatusLabel::sellerType($order->seller_type) }}</td>
                                <td>{{ \App\Support\StatusLabel::order($order->status) }}</td>
                                <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">لا توجد طلبات بعد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
