<div class="branch-navbar d-flex flex-wrap justify-content-between align-items-center gap-2">
    @php
        $routeName = request()->route()?->getName() ?? '';

        $pageTitle = 'لوحة الفرع';
        $pageSubtitle = 'متابعة الطلبات والمخزون والتحصيلات';

        if ($routeName === 'branch.dashboard') {
            $pageTitle = 'الرئيسية';
            $pageSubtitle = 'ملخص أداء الفرع وعملياته اليومية';
        } elseif ($routeName === 'branch.profile') {
            $pageTitle = 'البروفايل';
            $pageSubtitle = 'إدارة بيانات الفرع الشخصية';
        } elseif (str_starts_with($routeName, 'branch.orders')) {
            $pageTitle = 'الطلبات';
            $pageSubtitle = 'متابعة الطلبات وحالتها التشغيلية';
        } elseif (str_starts_with($routeName, 'branch.inventory')) {
            $pageTitle = 'المخزون والتسعير';
            $pageSubtitle = 'إدارة المخزون وتسعير المنتجات';
        } elseif (str_starts_with($routeName, 'branch.distributors')) {
            $pageTitle = 'المندوبون';
            $pageSubtitle = 'تنظيم أداء المندوبين في الفرع';
        } elseif (str_starts_with($routeName, 'branch.clients')) {
            $pageTitle = 'العملاء';
            $pageSubtitle = 'إدارة حسابات العملاء المرتبطين بالفرع';
        } elseif (str_starts_with($routeName, 'branch.reports')) {
            $pageTitle = 'التقارير';
            $pageSubtitle = 'قراءة تقارير الأداء والتحليلات';
        } elseif (str_starts_with($routeName, 'branch.replenishment')) {
            $pageTitle = 'طلب توريد';
            $pageSubtitle = 'متابعة طلبات التوريد الخاصة بالفرع';
        } elseif (str_starts_with($routeName, 'branch.alerts')) {
            $pageTitle = 'التنبيهات';
            $pageSubtitle = 'مراقبة التنبيهات المهمة للفرع';
        } elseif (str_starts_with($routeName, 'branch.products')) {
            $pageTitle = 'المنتجات';
            $pageSubtitle = 'متابعة منتجات الفرع وتوفرها';
        } elseif (str_starts_with($routeName, 'branch.payments')) {
            $pageTitle = 'المدفوعات والتحصيل';
            $pageSubtitle = 'متابعة التحصيلات والحالة المالية';
        } elseif ($routeName === 'branch.developer-profile.index') {
            $pageTitle = 'تفاصيل المطور';
            $pageSubtitle = 'بيانات المطور المسؤول عن بناء المنصة';
        }
    @endphp

    <div>
        <div class="fw-bold">{{ $pageTitle }}</div>
        <div class="small text-muted">{{ $pageSubtitle }}</div>
    </div>
    <div class="d-flex align-items-center gap-2 branch-top-actions">
        <form action="{{ route('branch.logout') }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-branch-logout">تسجيل الخروج</button>
        </form>
    </div>
</div>
