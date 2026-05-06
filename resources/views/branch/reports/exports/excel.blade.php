<table>
    <tr>
        <td colspan="4"><strong>تقرير الفرع</strong></td>
    </tr>
    <tr>
        <td colspan="4">اسم الفرع: {{ $branchName }}</td>
    </tr>
    <tr>
        <td colspan="4">تاريخ التصدير: {{ $exportedAt }}</td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th colspan="2">صافي المبيعات اليومية</th>
        </tr>
        <tr>
            <th>اليوم</th>
            <th>صافي الإيراد</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($dailySales as $row)
            <tr>
                <td>{{ $row->day }}</td>
                <td>{{ number_format((float) $row->total, 2, '.', '') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="2">لا توجد بيانات</td>
            </tr>
        @endforelse
    </tbody>
</table>

<table>
    <thead>
        <tr>
            <th colspan="3">المبيعات حسب المنتج</th>
        </tr>
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
                <td>{{ number_format((float) $row->sold_quantity, 2, '.', '') }}</td>
                <td>{{ number_format((float) $row->revenue, 2, '.', '') }}</td>
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
            <th colspan="3">أداء المندوبين</th>
        </tr>
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
                <td>{{ number_format((float) $row->delivered_orders, 2, '.', '') }}</td>
                <td>{{ number_format((float) $row->revenue, 2, '.', '') }}</td>
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
            <th colspan="4">أفضل العملاء</th>
        </tr>
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
                <td>{{ number_format((float) $row->delivered_orders, 2, '.', '') }}</td>
                <td>{{ number_format((float) $row->total_value, 2, '.', '') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4">لا توجد بيانات</td>
            </tr>
        @endforelse
    </tbody>
</table>
