@extends('pos.layout.app')

@section('content')
    <style>
        .dev-shell {
            background: radial-gradient(circle at 14% 0%, #e8f2ff 0%, #f8fbff 45%, #ffffff 100%);
            border: 1px solid #dbe7f7;
            border-radius: 18px;
            padding: 18px;
            overflow: visible;
        }

        .dev-hero {
            border-radius: 16px;
            background: linear-gradient(135deg, #0a2550 0%, #0f3a79 62%, #1462aa 100%);
            color: #f8fbff;
            padding: 20px;
            margin-bottom: 14px;
            position: relative;
            overflow: visible;
            box-shadow: 0 14px 28px rgba(8, 26, 58, 0.24);
        }

        .dev-hero::after {
            content: '';
            position: absolute;
            left: -28px;
            top: -44px;
            width: 168px;
            height: 168px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.16);
            z-index: 0;
        }

        .dev-hero>* {
            position: relative;
            z-index: 1;
        }

        .dev-tag {
            display: inline-block;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(191, 219, 254, 0.5);
            color: #eff6ff;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .dev-profile-head {
            margin-top: 12px;
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .dev-avatar-wrap {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            border: 2px solid rgba(191, 219, 254, 0.75);
            background: rgba(255, 255, 255, 0.16);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: visible;
            box-shadow: 0 8px 18px rgba(8, 26, 58, 0.2);
            position: relative;
            cursor: zoom-in;
        }

        .dev-avatar {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .dev-avatar-preview {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            width: 240px;
            height: 240px;
            border-radius: 22px;
            border: 2px solid rgba(191, 219, 254, 0.7);
            box-shadow: 0 22px 38px rgba(8, 26, 58, 0.35);
            overflow: hidden;
            opacity: 0;
            transform: translateY(8px) scale(0.95);
            transform-origin: top right;
            transition: all 0.2s ease;
            pointer-events: none;
            z-index: 60;
            background: #0f172a;
        }

        .dev-avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .dev-avatar-wrap:hover .dev-avatar-preview {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .dev-avatar-fallback {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.7rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, #1b4f8f 0%, #3b82f6 100%);
        }

        .dev-profile-meta h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .dev-verified {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #38bdf8 0%, #2563eb 100%);
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            box-shadow: 0 6px 14px rgba(14, 116, 219, 0.42);
        }

        .dev-profile-meta p {
            margin: 6px 0 0;
            color: #dbeafe;
            font-size: 0.95rem;
        }

        .dev-card {
            border: 1px solid #dbe7f7;
            border-radius: 14px;
            background: #ffffff;
            padding: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
            height: 100%;
        }

        .dev-label {
            color: #64748b;
            font-size: 0.84rem;
            margin-bottom: 4px;
        }

        .dev-value {
            color: #0f172a;
            font-size: 1.06rem;
            font-weight: 700;
            word-break: break-word;
        }

        .dev-bio {
            border: 1px solid #dbe7f7;
            border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
            padding: 14px;
            margin-top: 12px;
            color: #334155;
            line-height: 1.9;
        }

        .dev-actions {
            margin-top: 14px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .dev-action-btn {
            text-decoration: none;
            border-radius: 10px;
            padding: 8px 13px;
            font-weight: 700;
            font-size: 0.92rem;
            border: 1px solid transparent;
            transition: all 0.18s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .dev-action-btn:hover {
            transform: translateY(-1px);
        }

        .dev-action-facebook {
            color: #ffffff;
            background: linear-gradient(90deg, #1877f2 0%, #1459b8 100%);
            border-color: rgba(255, 255, 255, 0.35);
        }

        .dev-action-facebook:hover {
            color: #ffffff;
            background: linear-gradient(90deg, #166de0 0%, #124ea3 100%);
        }

        .dev-action-whatsapp {
            color: #ffffff;
            background: linear-gradient(90deg, #22c55e 0%, #16a34a 100%);
            border-color: rgba(255, 255, 255, 0.35);
        }

        .dev-action-whatsapp:hover {
            color: #ffffff;
            background: linear-gradient(90deg, #1fb054 0%, #128f40 100%);
        }

        .dev-back-link {
            display: inline-block;
            margin-top: 14px;
            text-decoration: none;
            border: 1px solid #b7cdf1;
            border-radius: 10px;
            padding: 8px 12px;
            font-weight: 700;
            color: #0b2a57;
            background: #eef5ff;
        }

        .dev-back-link:hover {
            background: #e1ecff;
            color: #092249;
        }

        @media (max-width: 767.98px) {
            .dev-profile-head {
                align-items: flex-start;
            }

            .dev-avatar-preview {
                display: none;
            }
        }
    </style>

    <div class="dev-shell">
        <div class="dev-hero">
            <span class="dev-tag">مبرمج ومطور المنصه</span>
            <h1 class="h4 fw-bold mb-1">تفاصيل المطور</h1>
            <p class="mb-0 text-white-50">صفحة تعريفية بالمطور المسؤول عن تصميم وبرمجة النظام.</p>

            <div class="dev-profile-head">
                <div class="dev-avatar-wrap" aria-label="صورة المطور">
                    <img src="{{ asset('assets/images/developer-avatar.JPG') }}" alt="صورة المطور" class="dev-avatar"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="dev-avatar-fallback" style="display:none;">EJ</div>
                    <div class="dev-avatar-preview" aria-hidden="true">
                        <img src="{{ asset('assets/images/developer-avatar.JPG') }}" alt="معاينة مكبرة لصورة المطور">
                    </div>
                </div>

                <div class="dev-profile-meta">
                    <h2>
                        اياد جابر العليمي
                        <span class="dev-verified" title="حساب موثق">✓</span>
                    </h2>
                    <p>مطور ومهندس نظم - تصميم وتنفيذ حلول أعمال احترافية.</p>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6 col-lg-4">
                <div class="dev-card">
                    <div class="dev-label">الاسم</div>
                    <div class="dev-value">اياد جابر العليمي</div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="dev-card">
                    <div class="dev-label">الدور</div>
                    <div class="dev-value">مطور ومهندس نظم</div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="dev-card">
                    <div class="dev-label">المجال</div>
                    <div class="dev-value">تطوير تطبيقات الويب وإدارة المنصات</div>
                </div>
            </div>
        </div>

        <div class="dev-bio">
            أنا اياد جابر العليمي، أعمل على بناء أنظمة إدارية وتجارية تركّز على الأداء، الوضوح،
            وقابلية التوسع. كما أعمل أيضًا على تطوير تطبيقات الجوال وتطبيقات الأعمال المتكاملة.
            هدفي تقديم تجربة استخدام دقيقة وسلسة تساعد فريق العمل على إدارة العمليات اليومية
            بسرعة وثقة، مع بنية تقنية مرنة تسهّل التطوير المستقبلي.
        </div>

        <div class="dev-actions">
            <a href="https://www.facebook.com/eyadalalimi2018" target="_blank" rel="noopener noreferrer"
                class="dev-action-btn dev-action-facebook">
                Facebook
            </a>

            <a href="https://wa.me/967779570601" target="_blank" rel="noopener noreferrer"
                class="dev-action-btn dev-action-whatsapp">
                WhatsApp
            </a>
        </div>

        <a href="{{ route('pos.dashboard') }}" class="dev-back-link">العودة إلى لوحة التحكم</a>
    </div>
@endsection
