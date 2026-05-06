<!doctype html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <title>تقرير المحل التجاري</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: right;
        }

        th {
            background: #f3f4f6;
        }

        .title {
            margin-bottom: 8px;
            font-size: 16px;
            font-weight: bold;
        }

        .meta {
            margin-bottom: 12px;
            color: #555;
        }
    </style>
</head>

<body>
    <div class="title">تقرير المحل التجاري</div>
    <div class="meta">الفترة: {{ $from->toDateString() }} إلى {{ $to->toDateString() }}</div>

    <table>
        <thead>
            <tr>
                <th>إجمالي المبيعات</th>
                <th>إجمالي الربح</th>
                <th>عدد العمليات</th>
                <th>الكمية</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ number_format($stats['sales_total'], 2) }}</td>
                <td>{{ number_format($stats['profit_total'], 2) }}</td>
                <td>{{ number_format($stats['sales_count']) }}</td>
                <td>{{ number_format($stats['quantity_total'], 3) }}</td>
            </tr>
        </tbody>
    </table>

    <table>
        <thead>
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
</body>

</html>

