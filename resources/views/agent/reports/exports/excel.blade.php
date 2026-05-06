<table>
    <tr>
        <td colspan="4"><strong>تقرير الوكيل</strong></td>
    </tr>
    <tr>
        <td colspan="4">الاسم التجاري: {{ $businessName }}</td>
    </tr>
    <tr>
        <td colspan="4">تاريخ التصدير: {{ $exportedAt }}</td>
    </tr>
    <tr>
        <td colspan="4">الفترة: {{ $filterSummary['from_date'] }} - {{ $filterSummary['to_date'] }}</td>
    </tr>
    <tr>
        <td colspan="4">الفرع: {{ $filterSummary['branch_id'] ? '#' . $filterSummary['branch_id'] : 'كل الفروع' }}
        </td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th colspan="4">المؤشرات العامة</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>إجمالي الطلبات</td>
            <td>{{ $cards['total_orders'] }}</td>
            <td>إجمالي صافي الإيرادات</td>
            <td>{{ number_format((float) $cards['total_revenue'], 2, '.', '') }}</td>
        </tr>
        <tr>
            <td>عدد العملاء</td>
            <td>{{ $cards['customers_count'] }}</td>
            <td>عدد الوكلاء</td>
            <td>{{ $cards['agents_count'] }}</td>
        </tr>
        <tr>
            <td>طلبات معلقة</td>
            <td>{{ $ordersStats['pending'] }}</td>
            <td>طلبات مكتملة</td>
            <td>{{ $ordersStats['delivered'] }}</td>
        </tr>
    </tbody>
</table>

<table>
    <thead>
        <tr>
            <th colspan="3">أفضل المنتجات</th>
        </tr>
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
                <td>{{ number_format((float) $product->sold_quantity, 2, '.', '') }}</td>
                <td>{{ number_format((float) $product->revenue, 2, '.', '') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3">لا توجد بيانات</td>
            </tr>
        @endforelse
    </tbody>
</table>

<table>
    <thead>
        <tr>
            <th colspan="3">أفضل المندوبين</th>
        </tr>
        <tr>
            <th>المندوب</th>
            <th>الطلبات</th>
            <th>الإيراد</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($topDistributors as $distributor)
            <tr>
                <td>{{ $distributor->name }}</td>
                <td>{{ number_format((float) $distributor->orders_count, 2, '.', '') }}</td>
                <td>{{ number_format((float) $distributor->revenue, 2, '.', '') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3">لا توجد بيانات</td>
            </tr>
        @endforelse
    </tbody>
</table>

<table>
    <thead>
        <tr>
            <th colspan="2">فرص التوسع</th>
        </tr>
        <tr>
            <th>المنطقة</th>
            <th>عدد الطلبات</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($coverageInsights['expansion_opportunities'] as $area)
            <tr>
                <td>{{ $area->customer_address }}</td>
                <td>{{ (int) $area->orders_count }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="2">لا توجد بيانات</td>
            </tr>
        @endforelse
    </tbody>
</table>
