@extends('agent.layout.app')

@section('title', 'تفاصيل المطور')

@section('content')
<style>
    .dev-page-card {
        border-radius: 1.1rem;
        overflow: hidden;
    }

    .dev-hero {
        background: linear-gradient(125deg, #f1f6ff 0%, #f6fdf8 45%, #fff7ec 100%);
        border: 1px solid #e2e8f3;
        border-radius: 1rem;
        padding: 1.2rem;
    }

    .dev-avatar-wrap {
        width: 92px;
        height: 92px;
        border-radius: 999px;
        overflow: hidden;
        border: 3px solid #ffffff;
        box-shadow: 0 10px 28px rgba(29, 48, 78, 0.16);
        flex: 0 0 auto;
    }

    .dev-avatar {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .dev-verify {
        width: 22px;
        height: 22px;
        max-width: 22px;
        max-height: 22px;
        object-fit: contain;
        display: inline-block;
        vertical-align: middle;
        flex: 0 0 auto;
    }

    .dev-verify-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: linear-gradient(135deg, #ecf8ff 0%, #e9f2ff 100%);
        color: #0d5ea8;
        border: 1px solid #cfe4fb;
        border-radius: 999px;
        padding: 0.24rem 0.58rem;
        font-size: 0.76rem;
        font-weight: 800;
        box-shadow: 0 5px 14px rgba(11, 96, 168, 0.12);
    }

    .dev-verify-badge i {
        font-size: 0.9rem;
        line-height: 1;
    }

    .social-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        border-radius: 0.75rem;
        padding: 0.58rem 0.95rem;
        font-weight: 700;
        text-decoration: none;
        border: 1px solid transparent;
        transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
    }

    .social-btn i {
        font-size: 1rem;
        line-height: 1;
    }

    .social-btn:hover {
        transform: translateY(-1px);
        filter: brightness(1.02);
    }

    .social-btn-whatsapp {
        background: linear-gradient(135deg, #25d366 0%, #19b957 100%);
        color: #ffffff;
        box-shadow: 0 10px 20px rgba(25, 185, 87, 0.24);
    }

    .social-btn-whatsapp:hover {
        color: #ffffff;
        box-shadow: 0 12px 24px rgba(25, 185, 87, 0.3);
    }

    .social-btn-facebook {
        background: linear-gradient(135deg, #1877f2 0%, #115fca 100%);
        color: #ffffff;
        box-shadow: 0 10px 20px rgba(17, 95, 202, 0.24);
    }

    .social-btn-facebook:hover {
        color: #ffffff;
        box-shadow: 0 12px 24px rgba(17, 95, 202, 0.3);
    }

    .dev-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: #ffffff;
        border: 1px solid #dce6f4;
        color: #28405f;
        border-radius: 999px;
        padding: 0.32rem 0.72rem;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .dev-stat-box {
        border: 1px solid #e6edf7;
        border-radius: 0.9rem;
        padding: 0.95rem;
        background: #fff;
        height: 100%;
    }

    .dev-stat-value {
        font-size: 1.2rem;
        font-weight: 800;
        color: #173657;
        line-height: 1.2;
    }

    .dev-section-card {
        border: 1px solid #e8edf5;
        border-radius: 0.9rem;
        padding: 1rem;
        height: 100%;
        background: #fff;
    }

    .dev-bio {
        line-height: 1.95;
        color: #4c5f78;
        font-size: 0.97rem;
        margin-bottom: 0;
    }

    .dev-list {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .dev-list li {
        display: flex;
        align-items: flex-start;
        gap: 0.55rem;
        margin-bottom: 0.65rem;
        color: #3d5068;
        font-size: 0.93rem;
    }

    .dev-list li:last-child {
        margin-bottom: 0;
    }

    .dev-list i {
        color: #198754;
        font-size: 0.95rem;
        margin-top: 0.14rem;
    }

    @media (max-width: 767.98px) {
        .dev-hero {
            padding: 1rem;
        }

        .dev-avatar-wrap {
            width: 78px;
            height: 78px;
        }
    }
</style>

<div class="card border-0 shadow-sm dev-page-card">
    <div class="card-body p-3 p-md-4 p-lg-5">
        <section class="dev-hero mb-4">
            <div class="d-flex align-items-center gap-3 flex-wrap mb-3">
                <div class="dev-avatar-wrap">
                    <img src="{{ asset('assets/images/developer-avatar.JPG') }}" alt="صورة المطور" class="dev-avatar">
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h1 class="h4 fw-bold mb-0">اياد جابر العليمي</h1>
                        <img src="{{ asset('assets/images/viv.png') }}" alt="علامة التحقق" class="dev-verify">
                    </div>
                    <p class="text-secondary mb-2">مطور برمجيات | Backend & Mobile</p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="dev-chip"><i class="bi bi-code-slash"></i> Laravel</span>
                        <span class="dev-chip"><i class="bi bi-phone"></i> Android</span>
                        <span class="dev-chip"><i class="bi bi-diagram-3"></i> API Integration</span>
                        <span class="dev-chip"><i class="bi bi-brush"></i> UI/UX</span>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="dev-stat-box">
                        <small class="text-secondary d-block mb-1">الخبرة العملية</small>
                        <div class="dev-stat-value">تطوير متكامل للأنظمة</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dev-stat-box">
                        <small class="text-secondary d-block mb-1">التركيز الأساسي</small>
                        <div class="dev-stat-value">السرعة + الاستقرار + سهولة الاستخدام</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dev-stat-box">
                        <small class="text-secondary d-block mb-1">مجال العمل</small>
                        <div class="dev-stat-value">لوحات تحكم، API، وتطبيقات إدارية</div>
                    </div>
                </div>
            </div>
        </section>

        <div class="row g-3 mb-4">
            <div class="col-lg-7">
                <section class="dev-section-card">
                    <h2 class="h6 fw-bold mb-3">نبذة احترافية</h2>
                    <p class="dev-bio">
                        مطور برمجيات متخصص في بناء حلول رقمية عملية تبدأ من تحليل الاحتياج وتنتهي بمنتج مستقر وسهل
                        الاستخدام. أعمل على تصميم وتنفيذ بنية Backend قوية باستخدام Laravel مع بناء واجهات إدارة
                        حديثة وربطها بواجهات API واضحة وقابلة للتوسع. أركز دائمًا على جودة الكود، مرونة التطوير،
                        وأداء النظام لضمان تجربة استخدام سلسة وموثوقة على الويب والموبايل.
                    </p>
                </section>
            </div>
            <div class="col-lg-5">
                <section class="dev-section-card">
                    <h2 class="h6 fw-bold mb-3">أبرز الخدمات</h2>
                    <ul class="dev-list">
                        <li><i class="bi bi-check2-circle"></i><span>تطوير لوحات تحكم احترافية متعددة الصلاحيات.</span>
                        </li>
                        <li><i class="bi bi-check2-circle"></i><span>بناء واجهات API آمنة ومنظمة للتطبيقات.</span></li>
                        <li><i class="bi bi-check2-circle"></i><span>تحسين تجربة المستخدم ورفع جودة الواجهات.</span>
                        </li>
                        <li><i class="bi bi-check2-circle"></i><span>صيانة الأنظمة وإصلاح الأعطال وتطوير المزايا.</span>
                        </li>
                    </ul>
                </section>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="https://wa.me/967779570601" target="_blank" rel="noopener noreferrer"
                class="social-btn social-btn-whatsapp" aria-label="واتساب">
                <i class="bi bi-whatsapp"></i>
                تواصل واتساب
            </a>
            <a href="https://www.facebook.com/eyadalalimi2018" target="_blank" rel="noopener noreferrer"
                class="social-btn social-btn-facebook" aria-label="فيسبوك">
                <i class="bi bi-facebook"></i>
                الصفحة الشخصية
            </a>
        </div>
    </div>
</div>
@endsection