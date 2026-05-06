@extends('workshop.layout.app')

@section('content')
    <h1 class="workshop-section-title">إدارة الأسعار</h1>
    <p class="workshop-section-subtitle">مقارنة التسعير الحالي مع متوسط الفاتورة وقيمة المنتجات لكل خدمة.</p>

    <div class="workshop-panel">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>الخدمة</th>
                        <th>السعر الحالي</th>
                        <th>متوسط تكلفة المنتجات</th>
                        <th>متوسط الفاتورة</th>
                        <th>هامش تقريبي</th>
                        <th>طلبات مكتملة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($serviceRows as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ number_format($row['listed_price'], 2) }} ر.ي</td>
                            <td>{{ number_format($row['avg_products_cost'], 2) }} ر.ي</td>
                            <td>{{ number_format($row['avg_invoice'], 2) }} ر.ي</td>
                            <td>{{ $row['margin'] }}%</td>
                            <td>{{ $row['completed_orders'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">لا توجد خدمات مسجلة في الورشة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
