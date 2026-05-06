<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>فاتورة {{ $order->order_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8fafc;
            font-family: 'Cairo', 'Tahoma', sans-serif;
            color: #0f172a;
        }

        .invoice-wrap {
            max-width: 820px;
            margin: 24px auto;
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .invoice-header {
            background: linear-gradient(90deg, #0a2550 0%, #12539a 100%);
            color: #fff;
            padding: 18px 20px;
        }

        .invoice-body {
            padding: 20px;
        }

        .invoice-table th,
        .invoice-table td {
            vertical-align: middle;
        }

        .invoice-total {
            background: #f1f5f9;
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            padding: 12px 14px;
            font-weight: 700;
        }

        @media print {
            body {
                background: #fff;
            }

            .no-print {
                display: none !important;
            }

            .invoice-wrap {
                margin: 0;
                border: 0;
                box-shadow: none;
                max-width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-wrap">
        <div class="invoice-header d-flex justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 mb-1">فاتورة خدمة ورشة</h1>
                <div class="small">رقم الفاتورة: {{ $order->order_number }}</div>
            </div>
            <div class="text-end small">
                <div>تاريخ الإصدار: {{ ($order->updated_at ?? now())->format('Y-m-d H:i') }}</div>
                <div>الحالة: {{ \App\Support\StatusLabel::workshopServiceOrder($order->status) }}</div>
            </div>
        </div>

        <div class="invoice-body">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="small text-muted mb-1">بيانات العميل</div>
                        <div><strong>{{ $order->customer_name }}</strong></div>
                        <div>{{ $order->customer_phone }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="small text-muted mb-1">الخدمة</div>
                        <div><strong>{{ $order->service?->name ?? 'خدمة عامة' }}</strong></div>
                        <div class="small text-muted">ملاحظات: {{ $order->notes ?: '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="table-responsive mb-3">
                <table class="table table-bordered invoice-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>البند</th>
                            <th>الكمية</th>
                            <th>سعر الوحدة</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>رسوم الخدمة</td>
                            <td>1</td>
                            <td>{{ number_format((float) $order->service_fee, 2) }} ر.ي</td>
                            <td>{{ number_format((float) $order->service_fee, 2) }} ر.ي</td>
                        </tr>
                        @if (is_array($order->used_products) && count($order->used_products) > 0)
                            @foreach ($order->used_products as $used)
                                <tr>
                                    <td>{{ $used['product_name'] ?? 'منتج' }}</td>
                                    <td>{{ number_format((float) ($used['quantity'] ?? 0), 3) }}</td>
                                    <td>{{ number_format((float) ($used['unit_cost'] ?? 0), 2) }} ر.ي</td>
                                    <td>{{ number_format((float) ($used['line_total'] ?? 0), 2) }} ر.ي</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td>منتجات مستخدمة</td>
                                <td>—</td>
                                <td>—</td>
                                <td>{{ number_format((float) $order->products_total, 2) }} ر.ي</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="row g-2 align-items-center">
                <div class="col-md-7 small text-muted">
                    شكرًا لزيارتكم. هذه الفاتورة صادرة من نظام إدارة الورشة.
                </div>
                <div class="col-md-5">
                    <div class="small text-muted text-end">رسوم المنصة:
                        {{ number_format((float) (($order->commission_value ?? 0) + ($order->platform_service_fee ?? 0) + ($order->platform_fixed_fee ?? 0)), 2) }}
                        ر.ي
                    </div>
                    <div class="invoice-total text-end">الإجمالي النهائي:
                        {{ number_format((float) ($order->payable_total ?? $order->total_amount), 2) }} ر.ي
                    </div>
                    <div class="small text-muted text-end">الإجمالي الأساسي:
                        {{ number_format((float) $order->total_amount, 2) }} ر.ي</div>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-end gap-2 no-print">
                <a href="{{ route('workshop.sales.index') }}" class="btn btn-outline-secondary">العودة للمبيعات</a>
                <button type="button" onclick="window.print()" class="btn btn-primary">طباعة الفاتورة</button>
            </div>
        </div>
    </div>
</body>

</html>
