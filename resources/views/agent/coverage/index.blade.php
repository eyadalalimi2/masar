@extends('agent.layout.app')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">إدارة التغطية الجغرافية</h1>
            <p class="text-muted mb-0">تحليل الانتشار الحالي وتحديد مناطق التوسع وفرص التحسين.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">أداء الفروع</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>الفرع</th>
                                    <th>طلبات مسلمة</th>
                                    <th>إيراد</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($coverageInsights['branch_performance'] as $row)
                                    <tr>
                                        <td>{{ $row->name }}</td>
                                        <td>{{ number_format((float) $row->delivered_orders) }}</td>
                                        <td>{{ number_format((float) $row->delivered_revenue, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">لا توجد بيانات.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">المناطق النشطة</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>المنطقة</th>
                                    <th>طلبات مسلمة</th>
                                    <th>إيراد</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($coverageInsights['active_areas'] as $row)
                                    <tr>
                                        <td>{{ $row->customer_address }}</td>
                                        <td>{{ number_format((float) $row->delivered_orders) }}</td>
                                        <td>{{ number_format((float) $row->delivered_revenue, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">لا توجد بيانات.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">فروع بحاجة دعم (آخر 30 يوم)</h2>
                    <ul class="list-group list-group-flush">
                        @forelse ($coverageInsights['underperforming_branches'] as $row)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>{{ $row->name }}</span>
                                <span class="badge text-bg-warning">{{ (int) $row->delivered_orders_30d }} طلب</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted px-0">كل الفروع لديها مبيعات مسلمة خلال آخر 30 يوم.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">مناطق غير مغطاة</h2>
                    <ul class="list-group list-group-flush mb-3">
                        @forelse (($coverageInsights['uncovered_areas'] ?? collect()) as $row)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>{{ $row->customer_address }}</span>
                                <span class="badge text-bg-warning">{{ (int) $row->unmet_orders }} طلب</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted px-0">لا توجد مناطق غير مغطاة حاليًا.</li>
                        @endforelse
                    </ul>

                    <h2 class="h6 fw-bold mb-3">فرص التوسع المقترحة</h2>
                    <ul class="list-group list-group-flush">
                        @forelse ($coverageInsights['expansion_opportunities'] as $row)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>{{ $row->customer_address }}</span>
                                <span class="badge text-bg-primary">{{ (int) $row->orders_count }} طلب</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted px-0">لا توجد فرص واضحة حاليًا.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
