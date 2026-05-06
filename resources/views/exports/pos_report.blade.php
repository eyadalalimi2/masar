<table>
    <thead>
        <tr>
            <th>الفترة من</th>
            <th>{{ $from->toDateString() }}</th>
            <th>الفترة إلى</th>
            <th>{{ $to->toDateString() }}</th>
            <th></th>
            <th></th>
        </tr>
        <tr>
            <th>إجمالي المبيعات</th>
            <th>إجمالي الربح</th>
            <th>عدد العمليات</th>
            <th>الكمية</th>
            <th></th>
            <th></th>
        </tr>
        <tr>
            <th>{{ number_format($stats['sales_total'], 2) }}</th>
            <th>{{ number_format($stats['profit_total'], 2) }}</th>
            <th>{{ number_format($stats['sales_count']) }}</th>
            <th>{{ number_format($stats['quantity_total'], 3) }}</th>
            <th></th>
            <th></th>
        </tr>
        <tr>
            <th>التاريخ</th>
            <th>المنتج</th>
            <th>الكمية</th>
            <th>القيمة</th>
            <th>الربح</th>
            <th>القناة</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($sales as $sale)
            <tr>
                <td>{{ $sale->sold_at?->format('Y-m-d H:i') }}</td>
                <td>{{ $sale->product_name }}</td>
                <td>{{ number_format((float) $sale->quantity, 3) }}</td>
                <td>{{ number_format((float) $sale->total_amount, 2) }}</td>
                <td>{{ number_format((float) $sale->profit_amount, 2) }}</td>
                <td>{{ $sale->sale_channel }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
