@extends('agent.layout.app')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">تقارير {{ $segmentLabel }}</h1>
            <p class="text-muted mb-0">تحليلات الأداء الخاصة بوكالتك حسب القسم</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route($exportRoute, array_merge(['format' => 'excel'], request()->only(['from_date', 'to_date', 'branch_id']))) }}"
                class="btn btn-success btn-sm">
                تصدير Excel
            </a>
            <a href="{{ route($exportRoute, array_merge(['format' => 'pdf'], request()->only(['from_date', 'to_date', 'branch_id']))) }}"
                class="btn btn-danger btn-sm">
                تصدير PDF
            </a>
            <form method="POST" action="{{ route('agent.reports.alerts.low-demand') }}">
                @csrf
                <button class="btn btn-outline-warning btn-sm">توليد تنبيهات انخفاض الطلب</button>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-2 d-flex gap-2 flex-wrap">
            <a href="{{ route('agent.reports.commercial-stores.index') }}"
                class="btn btn-sm {{ ($indexRoute ?? '') === 'agent.reports.commercial-stores.index' ? 'btn-dark' : 'btn-outline-dark' }}">
                تقارير المحلات التجارية
            </a>
            <a href="{{ route('agent.reports.workshops.index') }}"
                class="btn btn-sm {{ ($indexRoute ?? '') === 'agent.reports.workshops.index' ? 'btn-dark' : 'btn-outline-dark' }}">
                تقارير الورش
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route($indexRoute) }}" class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">من تاريخ</label>
                    <input type="date" name="from_date" value="{{ $filters['from_date'] }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">إلى تاريخ</label>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">الفرع</label>
                    <select name="branch_id" class="form-select">
                        <option value="">كل الفروع</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) ($filters['branch_id'] ?? 0) === (int) $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100">تطبيق</button>
                    <a href="{{ route($indexRoute) }}" class="btn btn-outline-secondary w-100">إعادة ضبط</a>
                </div>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">إجمالي الطلبات</div>
                    <div class="h4 fw-bold mb-0">{{ number_format($cards['total_orders']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">إجمالي صافي الإيرادات</div>
                    <div class="h4 fw-bold mb-0">{{ number_format((float) $cards['total_revenue'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">عدد العملاء</div>
                    <div class="h4 fw-bold mb-0">{{ number_format($cards['customers_count']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">عدد الوكلاء</div>
                    <div class="h4 fw-bold mb-0">{{ number_format($cards['agents_count']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">توقع المبيعات (30 يوم)</h2>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>طلبات آخر 30 يوم</span>
                        <strong>{{ number_format((float) $demandForecast['current_orders_30d']) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>التغير مقابل 30 يوم سابقة</span>
                        <strong>{{ number_format((float) $demandForecast['orders_change_percent'], 1) }}%</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>الطلبات المتوقعة القادمة</span>
                        <strong>{{ number_format((float) $demandForecast['projected_orders_next_30d']) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>الإيراد المتوقع القادم</span>
                        <strong>{{ number_format((float) $demandForecast['projected_revenue_next_30d'], 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">منتجات منخفضة الطلب</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>المنتج</th>
                                    <th>المباع (30 يوم)</th>
                                    <th>المخزون</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lowDemandProducts->take(8) as $product)
                                    <tr>
                                        <td>{{ $product->product_name }}</td>
                                        <td>{{ number_format((float) $product->sold_quantity_30d) }}</td>
                                        <td>{{ number_format((float) $product->stock_quantity, 3) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">لا توجد منتجات منخفضة الطلب
                                            حاليًا.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">تقرير المبيعات</h2>
                    <div class="small text-muted mb-1">إجمالي المبيعات</div>
                    <div class="fw-bold mb-2">{{ number_format((float) $salesSummary['total_sales'], 2) }}</div>

                    <div class="small text-muted mb-1">عدد الطلبات</div>
                    <div class="fw-bold mb-2">{{ number_format($salesSummary['orders_count']) }}</div>

                    <div class="small text-muted mb-1">متوسط الطلب</div>
                    <div class="fw-bold">{{ number_format((float) $salesSummary['average_order'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">تقرير الطلبات</h2>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>قيد الانتظار</span>
                        <strong>{{ number_format($ordersStats['pending']) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>تم التوصيل</span>
                        <strong>{{ number_format($ordersStats['delivered']) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>ملغي</span>
                        <strong>{{ number_format($ordersStats['cancelled']) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">تقرير الديون</h2>
                    <div class="small text-muted mb-1">العملاء المدينون</div>
                    <div class="fw-bold mb-2">{{ number_format($debtReport['debtors_count']) }}</div>

                    <div class="small text-muted mb-1">إجمالي الديون</div>
                    <div class="fw-bold">{{ number_format((float) $debtReport['total_debt'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">صافي المبيعات اليومية</h2>
                    <canvas id="dailySalesChart" height="95"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">توزيع الطلبات</h2>
                    <canvas id="ordersDistributionChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">أفضل المنتجات</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>المنتج</th>
                                    <th>الكمية</th>
                                    <th>الإيراد</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topProducts as $product)
                                    <tr>
                                        <td>{{ $product->name }}</td>
                                        <td>{{ number_format((float) $product->sold_quantity) }}</td>
                                        <td>{{ number_format((float) $product->revenue, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">لا توجد بيانات</td>
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
                    <h2 class="h6 fw-bold mb-3">أفضل المندوبين</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>المندوب</th>
                                    <th>الطلبات</th>
                                    <th>الإيرادات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topDistributors as $distributor)
                                    <tr>
                                        <td>{{ $distributor->name }}</td>
                                        <td>{{ number_format((float) $distributor->orders_count) }}</td>
                                        <td>{{ number_format((float) $distributor->revenue, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">لا توجد بيانات</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 fw-bold mb-0">ملخص التغطية الجغرافية</h2>
                <a href="{{ route('agent.coverage.index') }}" class="btn btn-sm btn-outline-dark">عرض التفاصيل</a>
            </div>
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>الفرع</th>
                                    <th>طلبات مسلمة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($coverageInsights['branch_performance']->take(5) as $row)
                                    <tr>
                                        <td>{{ $row->name }}</td>
                                        <td>{{ number_format((float) $row->delivered_orders) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">لا توجد بيانات</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>منطقة</th>
                                    <th>طلبات مسلمة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($coverageInsights['active_areas']->take(5) as $row)
                                    <tr>
                                        <td>{{ $row->customer_address }}</td>
                                        <td>{{ number_format((float) $row->delivered_orders) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">لا توجد بيانات</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">مقارنة أداء الفروع</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الفرع</th>
                                    <th>طلبات</th>
                                    <th>إيراد</th>
                                    <th>حصة الإيراد</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($branchComparison as $row)
                                    <tr>
                                        <td>{{ $row->rank }}</td>
                                        <td>{{ $row->name }}</td>
                                        <td>{{ number_format((float) $row->delivered_orders) }}</td>
                                        <td>{{ number_format((float) $row->delivered_revenue, 2) }}</td>
                                        <td>{{ number_format((float) $row->revenue_share_percent, 1) }}%</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">لا توجد بيانات فروع.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">مناطق غير مغطاة</h2>
                    <ul class="list-group list-group-flush">
                        @forelse ($uncoveredAreas as $row)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>{{ $row->customer_address }}</span>
                                <span class="badge text-bg-warning">{{ (int) $row->unmet_orders }} طلب</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted px-0">لا توجد مناطق غير مغطاة وفق البيانات الحالية.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">العملاء المدينون</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>العميل</th>
                            <th>الهاتف</th>
                            <th>الرصيد المستحق</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($debtReport['debtors'] as $debtor)
                            <tr>
                                <td>{{ $debtor->customer_name }}</td>
                                <td>{{ $debtor->customer_phone }}</td>
                                <td>{{ number_format((float) $debtor->balance, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">لا يوجد عملاء مدينون</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">الإيرادات الشهرية</h2>
            <canvas id="monthlyRevenueChart" height="95"></canvas>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const dailySalesCtx = document.getElementById('dailySalesChart');
        const ordersDistributionCtx = document.getElementById('ordersDistributionChart');
        const monthlyRevenueCtx = document.getElementById('monthlyRevenueChart');

        new Chart(dailySalesCtx, {
            type: 'line',
            data: {
                labels: @json($revenueReport['daily']['labels']),
                datasets: [{
                    label: 'صافي المبيعات اليومية',
                    data: @json($revenueReport['daily']['values']),
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.15)',
                    fill: true,
                    tension: 0.35,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        new Chart(ordersDistributionCtx, {
            type: 'doughnut',
            data: {
                labels: ['قيد الانتظار', 'تم التوصيل', 'ملغي'],
                datasets: [{
                    data: [
                        {{ $ordersStats['pending'] }},
                        {{ $ordersStats['delivered'] }},
                        {{ $ordersStats['cancelled'] }}
                    ],
                    backgroundColor: ['#ffc107', '#198754', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        new Chart(monthlyRevenueCtx, {
            type: 'bar',
            data: {
                labels: @json($revenueReport['monthly']['labels']),
                datasets: [{
                    label: 'الإيرادات الشهرية',
                    data: @json($revenueReport['monthly']['values']),
                    backgroundColor: '#0dcaf0',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
@endpush
