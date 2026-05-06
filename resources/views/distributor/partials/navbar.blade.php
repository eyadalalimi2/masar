<div class="distributor-navbar d-flex flex-wrap justify-content-between align-items-center gap-2">
    @php
        $routeName = request()->route()?->getName() ?? '';

        $pageTitle = 'لوحة المندوب';
        $pageSubtitle = 'إدارة مسارات التوصيل وحالات الطلب';

        if ($routeName === 'distributor.dashboard') {
            $pageTitle = 'الرئيسية';
            $pageSubtitle = 'ملخص الأداء الميداني اليومي';
        } elseif (str_starts_with($routeName, 'distributor.orders')) {
            $pageTitle = 'الطلبات';
            $pageSubtitle = 'متابعة طلبات التوصيل وحالتها';
        } elseif (str_starts_with($routeName, 'distributor.products')) {
            $pageTitle = 'المنتجات';
            $pageSubtitle = 'عرض المنتجات المرتبطة بمساراتك';
        } elseif (str_starts_with($routeName, 'distributor.profile')) {
            $pageTitle = 'الملف الشخصي';
            $pageSubtitle = 'تحديث بياناتك الشخصية وبيانات المندوب';
        } elseif (str_starts_with($routeName, 'distributor.payments')) {
            $pageTitle = 'التحصيلات';
            $pageSubtitle = 'متابعة المدفوعات والتحصيل الميداني';
        } elseif (str_starts_with($routeName, 'distributor.alerts')) {
            $pageTitle = 'التنبيهات';
            $pageSubtitle = 'التنبيهات المهمة المرتبطة بالعمل اليومي';
        } elseif ($routeName === 'distributor.developer-profile.index') {
            $pageTitle = 'تفاصيل المطور';
            $pageSubtitle = 'بيانات المطور المسؤول عن بناء المنصة';
        }
    @endphp

    <div>
        <div class="fw-bold">{{ $pageTitle }}</div>
        <div class="small text-muted">{{ $pageSubtitle }}</div>
    </div>
    <div class="d-flex align-items-center gap-2 distributor-top-actions">
        <form action="{{ route('distributor.logout') }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-distributor-logout">تسجيل الخروج</button>
        </form>
    </div>
</div>
