@extends('admin.layout.app')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">لوحة التقارير والتحليلات</h1>
            <p class="text-muted mb-0">رؤية شاملة للمبيعات والطلبات والإيرادات والديون</p>
        </div>
    </div>

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
