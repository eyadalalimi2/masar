<div class="pos-navbar d-flex flex-wrap justify-content-between align-items-center gap-2">
    @php
        $routeName = request()->route()?->getName() ?? '';

        $pageTitle = 'لوحة المحلات التجارية';
        $pageSubtitle = 'إدارة العمليات اليومية';

        if ($routeName === 'pos.dashboard') {
            $pageTitle = 'الرئيسية';
            $pageSubtitle = 'ملخص عمليات المحل التجاري اليومية';
        } elseif (str_starts_with($routeName, 'pos.marketplace')) {
            $pageTitle = 'السوق';
            $pageSubtitle = 'استعراض عروض السوق والمنتجات';
        } elseif (str_starts_with($routeName, 'pos.orders')) {
            $pageTitle = 'الطلبات';
            $pageSubtitle = 'متابعة الطلبات وإدارة حالاتها';
        } elseif (str_starts_with($routeName, 'pos.catalog')) {
            $pageTitle = 'المنتجات المحلية';
            $pageSubtitle = 'إدارة المنتجات المحلية المتاحة';
        } elseif (str_starts_with($routeName, 'pos.sales')) {
            $pageTitle = 'المبيعات';
            $pageSubtitle = 'تحليل المبيعات وحركة التشغيل';
        } elseif (str_starts_with($routeName, 'pos.customers')) {
            $pageTitle = 'العملاء';
            $pageSubtitle = 'متابعة حسابات العملاء وطلباتهم';
        } elseif (str_starts_with($routeName, 'pos.reports')) {
            $pageTitle = 'التقارير';
            $pageSubtitle = 'تقارير تشغيلية لاتخاذ قرارات أفضل';
        } elseif (str_starts_with($routeName, 'pos.alerts')) {
            $pageTitle = 'التنبيهات';
            $pageSubtitle = 'إشعارات وتنبيهات مهمة للمحل التجاري';
        } elseif (str_starts_with($routeName, 'pos.profile')) {
            $pageTitle = 'الملف الشخصي';
            $pageSubtitle = 'إدارة بيانات المحل التجاري';
        } elseif ($routeName === 'pos.developer-profile.index') {
            $pageTitle = 'تفاصيل المطور';
            $pageSubtitle = 'بيانات المطور المسؤول عن بناء المنصة';
        }
    @endphp

    <div>
        <div class="fw-bold">{{ $pageTitle }}</div>
        <div class="small text-muted">{{ $pageSubtitle }}</div>
    </div>
    <div class="d-flex align-items-center gap-2 pos-top-actions">
        <form method="POST" action="{{ route('pos.logout') }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-pos-logout">تسجيل الخروج</button>
        </form>
    </div>
</div>
