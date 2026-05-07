<nav class="navbar admin-navbar">
    @php
    $routeName = request()->route()?->getName() ?? '';

    $pageTitle = 'لوحة التحكم';
    $pageSubtitle = 'إدارة النظام والعمليات العامة';

    if ($routeName === 'admin.dashboard') {
    $pageTitle = 'الرئيسية';
    $pageSubtitle = 'ملخص الأداء العام وحالة النظام';
    } elseif (str_starts_with($routeName, 'admin.tasks')) {
    $pageTitle = 'إدارة المهام';
    $pageSubtitle = 'متابعة المهام اليومية وتنظيم التنفيذ';
    } elseif (str_starts_with($routeName, 'admin.users')) {
    $pageTitle = 'إدارة المستخدمين';
    $pageSubtitle = 'التحكم بحسابات المستخدمين والصلاحيات';
    } elseif (str_starts_with($routeName, 'admin.auth-verification')) {
    $pageTitle = 'إدارة التحقق والتوثيق';
    $pageSubtitle = 'مراجعة التوثيق والتحكم بحالات حسابات النظام';
    } elseif ($routeName === 'admin.settings.index') {
    $pageTitle = 'إعدادات النظام';
    $pageSubtitle = 'ضبط الإعدادات الأساسية والتشغيلية';
    } elseif ($routeName === 'admin.platform-release.index') {
    $pageTitle = 'إصدار المنصة';
    $pageSubtitle = 'معلومات النسخة الحالية وبيئة التشغيل';
    } elseif ($routeName === 'admin.developer-profile.index') {
    $pageTitle = 'تفاصيل المطور';
    $pageSubtitle = 'بيانات المطور المسؤول عن بناء المنصة';
    } elseif (str_starts_with($routeName, 'admin.suppliers')) {
    $pageTitle = 'إدارة الوكلاء';
    $pageSubtitle = 'إدارة بيانات الوكلاء والعلاقات التشغيلية';
    } elseif (str_starts_with($routeName, 'admin.branches')) {
    $pageTitle = 'إدارة الفروع';
    $pageSubtitle = 'متابعة الفروع والعمليات المرتبطة بها';
    } elseif (str_starts_with($routeName, 'admin.distributors')) {
    $pageTitle = 'إدارة المندوبين';
    $pageSubtitle = 'تنظيم المندوبين والمهام الميدانية';
    } elseif (str_starts_with($routeName, 'admin.products')) {
    $pageTitle = 'إدارة المنتجات';
    $pageSubtitle = 'إدارة الكتالوج والتسعير والمخزون';
    } elseif (str_starts_with($routeName, 'admin.orders')) {
    $pageTitle = 'إدارة الطلبات';
    $pageSubtitle = 'متابعة دورة الطلبات من الإنشاء حتى الإغلاق';
    } elseif (str_starts_with($routeName, 'admin.payments')) {
    $pageTitle = 'المدفوعات';
    $pageSubtitle = 'مراجعة عمليات الدفع والحالة المالية';
    } elseif (str_starts_with($routeName, 'admin.commercial-stores')) {
    $pageTitle = 'إدارة المحلات التجارية';
    $pageSubtitle = 'إدارة حسابات المحلات التجارية بشكل مستقل';
    } elseif (str_starts_with($routeName, 'admin.workshops')) {
    $pageTitle = 'إدارة ورش الصيانة';
    $pageSubtitle = 'إدارة حسابات ورش الصيانة بشكل مستقل';
    } elseif (str_starts_with($routeName, 'admin.wholesale-traders')) {
    $pageTitle = 'إدارة تجار الجملة';
    $pageSubtitle = 'إدارة حسابات تجار الجملة بشكل مستقل';
    } elseif (str_starts_with($routeName, 'admin.accounts') || str_starts_with($routeName, 'admin.customers')) {
    $pageTitle = 'إدارة العملاء';
    $pageSubtitle = 'إدارة حسابات العملاء وبياناتهم التجارية';
    } elseif (str_starts_with($routeName, 'admin.reports')) {
    $pageTitle = 'التقارير والتحليلات';
    $pageSubtitle = 'قراءة المؤشرات واتخاذ قرارات مبنية على البيانات';
    } elseif (str_starts_with($routeName, 'admin.notifications')) {
    $pageTitle = 'مركز الإشعارات';
    $pageSubtitle = 'إدارة الإشعارات المركزية ومتابعة التنبيهات';
    }
    @endphp

    <div class="container-fluid px-1">

        <button class="btn btn-sm btn-primary d-lg-none" type="button" id="sidebarToggle">
            القائمة
        </button>

        <a class="navbar-brand fw-semibold" href="{{ url('/admin/dashboard') }}">
            <span>
                <span class="d-block">{{ $pageTitle }}</span>
                <small class="text-muted fw-normal">{{ $pageSubtitle }}</small>
            </span>
        </a>

        @auth
        <div class="ms-auto d-flex align-items-center admin-top-actions">
            <a href="{{ route('admin.tasks.index') }}" class="btn btn-sm btn-admin-task">
                المهام
            </a>

            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm btn-admin-logout">
                    تسجيل الخروج
                </button>
            </form>
        </div>
        @endauth

    </div>
</nav>