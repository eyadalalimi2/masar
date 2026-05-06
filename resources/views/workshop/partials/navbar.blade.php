<div class="workshop-navbar d-flex flex-wrap justify-content-between align-items-center gap-2">
    @php
        $routeName = request()->route()?->getName() ?? '';

        $pageTitle = 'لوحة ورشة الصيانة';
        $pageSubtitle = 'إدارة الخدمات والطلبات والمواعيد';

        if ($routeName === 'workshop.dashboard') {
            $pageTitle = 'الرئيسية';
            $pageSubtitle = 'ملخص يومي لأداء الورشة والخدمات';
        } elseif (str_starts_with($routeName, 'workshop.services')) {
            $pageTitle = 'إدارة الخدمات';
            $pageSubtitle = 'إضافة وتحديث خدمات الورشة وأسعارها';
        } elseif (str_starts_with($routeName, 'workshop.marketplace')) {
            $pageTitle = 'السوق';
            $pageSubtitle = 'طلب المنتجات من الفروع لخدمة العملاء';
        } elseif (str_starts_with($routeName, 'workshop.orders.purchase')) {
            $pageTitle = 'طلبات الشراء';
            $pageSubtitle = 'متابعة طلبات التوريد من الفروع';
        } elseif (str_starts_with($routeName, 'workshop.orders.service')) {
            $pageTitle = 'طلبات الخدمة';
            $pageSubtitle = 'استقبال ومتابعة طلبات العملاء';
        } elseif (str_starts_with($routeName, 'workshop.appointments')) {
            $pageTitle = 'المواعيد';
            $pageSubtitle = 'تنظيم جدول الحجوزات وأوقات العمل';
        } elseif (str_starts_with($routeName, 'workshop.execution')) {
            $pageTitle = 'تنفيذ الخدمة';
            $pageSubtitle = 'تسجيل بدء الخدمة والمواد المستخدمة';
        } elseif (str_starts_with($routeName, 'workshop.sales')) {
            $pageTitle = 'المبيعات والفواتير';
            $pageSubtitle = 'بيع الخدمة والمنتج وتوثيق العملية';
        } elseif (str_starts_with($routeName, 'workshop.pricing')) {
            $pageTitle = 'إدارة الأسعار';
            $pageSubtitle = 'ضبط أسعار الخدمات والمنتجات وهوامش الربح';
        } elseif (str_starts_with($routeName, 'workshop.customers')) {
            $pageTitle = 'إدارة العملاء';
            $pageSubtitle = 'بناء قاعدة العملاء وسجل الخدمات';
        } elseif (str_starts_with($routeName, 'workshop.reports')) {
            $pageTitle = 'التقارير';
            $pageSubtitle = 'تحليل الأداء والإيرادات واستهلاك المنتجات';
        } elseif ($routeName === 'workshop.developer-profile.index') {
            $pageTitle = 'تفاصيل المطور';
            $pageSubtitle = 'بيانات المطور المسؤول عن بناء المنصة';
        }
    @endphp

    <div>
        <div class="fw-bold">{{ $pageTitle }}</div>
        <div class="small text-muted">{{ $pageSubtitle }}</div>
    </div>

    <div class="d-flex align-items-center gap-2 workshop-top-actions">
        <form action="{{ route('workshop.logout') }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-workshop-logout">تسجيل الخروج</button>
        </form>
    </div>
</div>
