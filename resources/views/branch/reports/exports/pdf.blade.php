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
                    <div style="font-size:12px; color:#334155;">تقرير أداء الفرع</div>
                </td>
                <td style="border:0; width:30%; text-align:left; vertical-align:middle;">
                    <div style="font-size:11px; color:#475569;">{{ $branchName }}</div>
                    <div style="font-size:10px; color:#64748b;">{{ $exportedAt }}</div>
                </td>
            </tr>
        </table>

        <table style="border:0; border-collapse:collapse; margin-top:6px; width:100%; background:#f1f5f9;">
            <tr>
                <td style="border:0; text-align:center; padding:7px 6px; font-size:10.5px; color:#0f172a;">المؤشرات
                    العامة</td>
                <td style="border:0; text-align:center; padding:7px 6px; font-size:10.5px; color:#0f172a;">المبيعات
                    اليومية</td>
                <td style="border:0; text-align:center; padding:7px 6px; font-size:10.5px; color:#0f172a;">المبيعات حسب
                    المنتج</td>
                <td style="border:0; text-align:center; padding:7px 6px; font-size:10.5px; color:#0f172a;">أداء
                    المندوبين</td>
                <td style="border:0; text-align:center; padding:7px 6px; font-size:10.5px; color:#0f172a;">أفضل العملاء
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
        <div class="cover-meta-row"><strong>اسم الفرع:</strong> {{ $branchName }}</div>
        <div class="cover-meta-row"><strong>تاريخ التصدير:</strong> {{ $exportedAt }}</div>
        <div class="cover-meta-row"><strong>التقرير:</strong> أداء تشغيلي ومبيعات الفرع</div>
    </div>

    <div class="section">
        <h2>المؤشرات العامة</h2>
        <table class="kpi-table">
            <tr>
                <th>أيام البيع</th>
                <th>إجمالي الإيراد</th>
                <th>أداء المندوبين</th>
                <th>أفضل العملاء</th>
            </tr>
            <tr>
                <td>{{ number_format((float) $dailySales->count()) }}<span>عدد الأيام المباعة</span></td>
                <td>{{ number_format((float) $dailySales->sum('total'), 2) }}<span>إيراد الفترات المعروضة</span></td>
                <td>{{ number_format((float) $distributorPerformance->count()) }}<span>مندوب لديه تسليمات</span></td>
                <td>{{ number_format((float) $bestClients->count()) }}<span>عميل في القائمة</span></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>صافي المبيعات اليومية</h2>
        <table>
            <tr>
                <th>اليوم</th>
                <th>صافي الإيراد</th>
            </tr>
            @forelse ($dailySales as $row)
                <tr>
                    <td>{{ $row->day }}</td>
                    <td>{{ number_format((float) $row->total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">لا توجد بيانات</td>
                </tr>
            @endforelse
        </table>
    </div>

    <div class="section">
        <h2>المبيعات حسب المنتج</h2>
        <table>
            <tr>
                <th>المنتج</th>
                <th>الكمية</th>
                <th>صافي الإيراد</th>
            </tr>
            @forelse ($salesByProduct as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td>{{ number_format((float) $row->sold_quantity) }}</td>
                    <td>{{ number_format((float) $row->revenue, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">لا توجد بيانات</td>
                </tr>
            @endforelse
        </table>
    </div>

    <div class="section">
        <h2>أداء المندوبين</h2>
        <table>
            <tr>
                <th>المندوب</th>
                <th>طلبات مسلمة</th>
                <th>صافي الإيراد</th>
            </tr>
            @forelse ($distributorPerformance as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td>{{ number_format((float) $row->delivered_orders) }}</td>
                    <td>{{ number_format((float) $row->revenue, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">لا توجد بيانات</td>
                </tr>
            @endforelse
        </table>
    </div>

    <div class="section">
        <h2>أفضل العملاء</h2>
        <table>
            <tr>
                <th>العميل</th>
                <th>الهاتف</th>
                <th>طلبات</th>
                <th>قيمة</th>
            </tr>
            @forelse ($bestClients as $row)
                <tr>
                    <td>{{ $row->customer_name }}</td>
                    <td>{{ $row->customer_phone }}</td>
                    <td>{{ number_format((float) $row->delivered_orders) }}</td>
                    <td>{{ number_format((float) $row->total_value, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">لا توجد بيانات</td>
                </tr>
            @endforelse
        </table>
        <div class="small-note">تم إنشاء هذا التقرير آليًا بواسطة {{ $platformName }}.</div>
    </div>
</body>

</html>
