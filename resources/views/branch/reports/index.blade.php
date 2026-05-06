@extends('branch.layout.app')

@section('title', 'تقارير الفرع')

@section('content')
    <div class="container-fluid py-2">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h1 class="h4 fw-bold mb-0">تقارير الفرع</h1>
            <div class="d-flex gap-2">
                <a href="{{ route('branch.reports.export', ['format' => 'excel']) }}" class="btn btn-success btn-sm">تصدير
                    Excel</a>
                <a href="{{ route('branch.reports.export', ['format' => 'pdf']) }}" class="btn btn-danger btn-sm">تصدير
                    PDF</a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-bold">صافي المبيعات اليومية</h2>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>اليوم</th>
                                        <th>صافي الإيراد</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($dailySales as $row)
                                        <tr>
                                            <td>{{ $row->day }}</td>
                                            <td>{{ number_format((float) $row->total, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-muted text-center">لا توجد بيانات</td>
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
                        <h2 class="h6 fw-bold">المبيعات حسب المنتج</h2>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>المنتج</th>
                                        <th>الكمية</th>
                                        <th>صافي الإيراد</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($salesByProduct as $row)
                                        <tr>
                                            <td>{{ $row->name }}</td>
                                            <td>{{ number_format((float) $row->sold_quantity) }}</td>
                                            <td>{{ number_format((float) $row->revenue, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-muted text-center">لا توجد بيانات</td>
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
                        <h2 class="h6 fw-bold">أداء المندوبين</h2>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>المندوب</th>
                                        <th>طلبات مسلمة</th>
                                        <th>صافي الإيراد</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($distributorPerformance as $row)
                                        <tr>
                                            <td>{{ $row->name }}</td>
                                            <td>{{ number_format((float) $row->delivered_orders) }}</td>
                                            <td>{{ number_format((float) $row->revenue, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-muted text-center">لا توجد بيانات</td>
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
                        <h2 class="h6 fw-bold">أفضل العملاء</h2>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>العميل</th>
                                        <th>الهاتف</th>
                                        <th>طلبات</th>
                                        <th>قيمة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($bestClients as $row)
                                        <tr>
                                            <td>{{ $row->customer_name }}</td>
                                            <td>{{ $row->customer_phone }}</td>
                                            <td>{{ number_format((float) $row->delivered_orders) }}</td>
                                            <td>{{ number_format((float) $row->total_value, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-muted text-center">لا توجد بيانات</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
