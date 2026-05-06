@extends('agent.layout.app')

@section('content')
    <style>
        .release-shell {
            background: radial-gradient(circle at 12% 0%, #eaf3ff 0%, #f8fbff 42%, #ffffff 100%);
            border: 1px solid #dbe7f7;
            border-radius: 18px;
            padding: 18px;
        }

        .release-hero {
            position: relative;
            overflow: hidden;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 14px;
            color: #f8fbff;
            background: linear-gradient(135deg, #0a2550 0%, #0f3a79 62%, #1462aa 100%);
            box-shadow: 0 14px 28px rgba(8, 26, 58, 0.28);
        }

        .release-hero::after {
            content: '';
            position: absolute;
            left: -30px;
            top: -46px;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.14);
        }

        .release-version-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 8px 14px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(191, 219, 254, 0.45);
            font-weight: 700;
            margin-top: 10px;
        }

        .release-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 12px;
        }

        .release-card {
            border: 1px solid #dbe7f7;
            background: #ffffff;
            border-radius: 14px;
            padding: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
            height: 100%;
        }

        .release-card-label {
            color: #64748b;
            font-size: 0.85rem;
            margin-bottom: 4px;
        }

        .release-card-value {
            color: #0f172a;
            font-size: 1.08rem;
            font-weight: 700;
            word-break: break-word;
        }

        .release-col-4 {
            grid-column: span 4;
        }

        .release-col-6 {
            grid-column: span 6;
        }

        .release-col-12 {
            grid-column: span 12;
        }

        .release-notes {
            border: 1px solid #dbe7f7;
            border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
            padding: 14px;
            margin-top: 12px;
        }

        .release-notes-title {
            margin-bottom: 8px;
            color: #0b2a57;
            font-weight: 700;
        }

        .release-notes ul {
            margin: 0;
            padding-right: 18px;
            color: #334155;
        }

        .release-notes li {
            margin-bottom: 6px;
        }

        @media (max-width: 991.98px) {

            .release-col-4,
            .release-col-6 {
                grid-column: span 12;
            }
        }
    </style>

    <div class="release-shell">
        <div class="release-hero">
            <h1 class="h4 mb-1 fw-bold">إصدار المنصة</h1>
            <p class="mb-0 text-white-50">عرض مركزي لنسخة النظام الحالية ومعلومات بيئة التشغيل.</p>
            <div class="release-version-badge">
                النسخة الحالية
                <span>{{ $platformVersion }}</span>
            </div>
        </div>

        <div class="release-grid">
            <div class="release-col-4">
                <div class="release-card">
                    <div class="release-card-label">رقم الإصدار</div>
                    <div class="release-card-value">{{ $platformVersion }}</div>
                </div>
            </div>

            <div class="release-col-4">
                <div class="release-card">
                    <div class="release-card-label">تاريخ التحديث</div>
                    <div class="release-card-value">{{ $releaseDate }}</div>
                </div>
            </div>

            <div class="release-col-4">
                <div class="release-card">
                    <div class="release-card-label">بيئة التشغيل</div>
                    <div class="release-card-value">{{ $environmentName }}</div>
                </div>
            </div>

            <div class="release-col-6">
                <div class="release-card">
                    <div class="release-card-label">Laravel</div>
                    <div class="release-card-value">{{ $laravelVersion }}</div>
                </div>
            </div>

            <div class="release-col-6">
                <div class="release-card">
                    <div class="release-card-label">PHP</div>
                    <div class="release-card-value">{{ $phpVersion }}</div>
                </div>
            </div>

            <div class="release-col-12">
                <div class="release-notes">
                    <div class="release-notes-title">ملاحظات الإصدار</div>
                    <ul>
                        <li>واجهة موحدة لجميع لوحات التحكم مع تحسين تجربة الاستخدام.</li>
                        <li>تحسينات على التنقل والوضوح البصري لعناصر الإدارة الأساسية.</li>
                        <li>تهيئة جاهزة لتتبع الإصدارات القادمة عبر APP_VERSION.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
