<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin-top: 44mm;
            margin-bottom: 24mm;
            margin-left: 12mm;
            margin-right: 12mm;
            header: page-header;
            footer: page-footer;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11.5px;
            color: #111;
            direction: rtl;
        }

        h2 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #0f172a;
        }

        .cover-meta {
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #f8fafc;
            padding: 10px 12px;
            margin-bottom: 14px;
        }

        .cover-meta-row {
            width: 100%;
            font-size: 11px;
            color: #334155;
            margin-bottom: 3px;
        }

        .cover-meta-row:last-child {
            margin-bottom: 0;
        }

        .section {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        th,
        td {
            border: 1px solid #d6dbe1;
            padding: 6px;
            text-align: right;
            direction: rtl;
            vertical-align: middle;
        }

        th {
            background: #eef2f7;
            color: #1e293b;
            font-weight: 700;
        }

        .small-note {
            color: #64748b;
            font-size: 10px;
        }

        .kpi-table td {
            font-weight: 600;
        }

        .kpi-table td span {
            display: block;
            color: #64748b;
            font-weight: 400;
            font-size: 10px;
            margin-top: 2px;
        }
    </style>
</head>

<body>
    @php
        $platformName = 'منصة مسار';
        $logoFilePath = str_replace('\\', '/', public_path('assets/images/logo.png'));
        $hasLogo = file_exists($logoFilePath);
    @endphp

    <htmlpageheader name="page-header">
        <table style="border:0; border-collapse:collapse; margin:0; width:100%;">
            <tr>
                <td style="border:0; width:18%; text-align:right; vertical-align:middle;">
                    @if ($hasLogo)
                        <img src="{{ $logoFilePath }}" alt="logo" style="height:36px;">
                    @endif
                </td>
                <td style="border:0; width:52%; text-align:center; vertical-align:middle;">
                    <div style="font-size:16px; font-weight:700; color:#0f172a;">{{ $platformName }}</div>
                    <div style="font-size:12px; color:#334155;">تقرير أداء الوكيل - {{ $segmentLabel ?? 'عام' }}</div>
                </td>
                <td style="border:0; width:30%; text-align:left; vertical-align:middle;">
                    <div style="font-size:11px; color:#475569;">{{ $businessName }}</div>
                    <div style="font-size:10px; color:#64748b;">{{ $exportedAt }}</div>
                </td>
            </tr>
        </table>

        <table style="border:0; border-collapse:collapse; margin-top:6px; width:100%; background:#f1f5f9;">
            <tr>
                <td style="border:0; text-align:center; padding:7px 6px; font-size:10.5px; color:#0f172a;">المؤشرات
                    العامة</td>
                <td style="border:0; text-align:center; padding:7px 6px; font-size:10.5px; color:#0f172a;">أفضل المنتجات
                </td>
                <td style="border:0; text-align:center; padding:7px 6px; font-size:10.5px; color:#0f172a;">أفضل
                    المندوبين</td>
                <td style="border:0; text-align:center; padding:7px 6px; font-size:10.5px; color:#0f172a;">فرص التوسع
                </td>
            </tr>
        </table>
    </htmlpageheader>

    <htmlpagefooter name="page-footer">
        <table style="border:0; border-collapse:collapse; margin:0; width:100%; border-top:1px solid #cbd5e1;">
            <tr>
                <td style="border:0; width:40%; padding-top:6px; font-size:10px; color:#64748b; text-align:right;">
                    {{ $platformName }}
                </td>
                <td style="border:0; width:20%; padding-top:6px; font-size:10px; color:#64748b; text-align:center;">
                    {PAGENO} / {nbpg}
                </td>
                <td style="border:0; width:40%; padding-top:6px; font-size:10px; color:#64748b; text-align:left;">
                    تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}
                </td>
            </tr>
        </table>
    </htmlpagefooter>

    <div class="cover-meta">
        <div class="cover-meta-row"><strong>الاسم التجاري:</strong> {{ $businessName }}</div>
        <div class="cover-meta-row"><strong>الفترة:</strong> {{ $filterSummary['from_date'] }} -
            {{ $filterSummary['to_date'] }}</div>
        <div class="cover-meta-row"><strong>الفرع:</strong>
            {{ $filterSummary['branch_id'] ? '#' . $filterSummary['branch_id'] : 'كل الفروع' }}</div>
        <div class="cover-meta-row"><strong>تاريخ التصدير:</strong> {{ $exportedAt }}</div>
    </div>

    <div class="section">
        <h2>المؤشرات العامة</h2>
        <table class="kpi-table">
            <tr>
                <th>إجمالي الطلبات</th>
                <th>إجمالي صافي الإيرادات</th>
                <th>عدد العملاء</th>
                <th>طلبات مكتملة</th>
            </tr>
            <tr>
                <td>
                    {{ number_format((float) $cards['total_orders']) }}
                    <span>طلب</span>
                </td>
                <td>
                    {{ number_format((float) $cards['total_revenue'], 2) }}
                    <span>إجمالي صافي الإيرادات</span>
                </td>
                <td>
                    {{ number_format((float) $cards['customers_count']) }}
                    <span>عميل</span>
                </td>
                <td>
                    {{ number_format((float) $ordersStats['delivered']) }}
                    <span>تم التسليم</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>أفضل المنتجات</h2>
        <table>
            <tr>
                <th>المنتج</th>
                <th>الكمية</th>
                <th>الإيراد</th>
            </tr>
            @forelse ($topProducts as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>{{ number_format((float) $product->sold_quantity) }}</td>
                    <td>{{ number_format((float) $product->revenue, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">لا توجد بيانات</td>
                </tr>
            @endforelse
        </table>
    </div>

    <div class="section">
        <h2>أفضل المندوبين</h2>
        <table>
            <tr>
                <th>المندوب</th>
                <th>الطلبات</th>
                <th>الإيراد</th>
            </tr>
            @forelse ($topDistributors as $distributor)
                <tr>
                    <td>{{ $distributor->name }}</td>
                    <td>{{ number_format((float) $distributor->orders_count) }}</td>
                    <td>{{ number_format((float) $distributor->revenue, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">لا توجد بيانات</td>
                </tr>
            @endforelse
        </table>
    </div>

    <div class="section">
        <h2>فرص التوسع (30 يوم)</h2>
        <table>
            <tr>
                <th>المنطقة</th>
                <th>عدد الطلبات</th>
            </tr>
            @forelse ($coverageInsights['expansion_opportunities'] as $row)
                <tr>
                    <td>{{ $row->customer_address }}</td>
                    <td>{{ (int) $row->orders_count }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">لا توجد بيانات</td>
                </tr>
            @endforelse
        </table>
        <div class="small-note">تم إنشاء هذا التقرير آليًا بواسطة {{ $platformName }}.</div>
    </div>
</body>

</html>
