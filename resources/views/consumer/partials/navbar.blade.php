<div class="consumer-navbar d-flex flex-wrap justify-content-between align-items-center gap-2">
    @php
        $routeName = request()->route()?->getName() ?? '';

        $pageTitle = 'لوحة المستهلك الفردي';
        $pageSubtitle = 'متابعة الطلبات والعروض الخاصة بك';

        if ($routeName === 'consumer.dashboard' || $routeName === 'consumer.home') {
            $pageTitle = 'الرئيسية';
            $pageSubtitle = 'المتاجر القريبة والعروض والخدمات الشائعة';
        } elseif ($routeName === 'consumer.browse' || $routeName === 'consumer.store.show') {
            $pageTitle = 'التصفح واختيار المتجر';
            $pageSubtitle = 'فلترة المنتجات والخدمات ومقارنة الخيارات';
        } elseif ($routeName === 'consumer.tracking') {
            $pageTitle = 'تتبع الطلب';
            $pageSubtitle = 'متابعة الحالة ومعلومات التنفيذ';
        } elseif ($routeName === 'consumer.history') {
            $pageTitle = 'سجل الطلبات';
            $pageSubtitle = 'الطلبات السابقة وإعادة الطلب بسهولة';
        } elseif (str_starts_with($routeName, 'consumer.addresses.')) {
            $pageTitle = 'إدارة العناوين';
            $pageSubtitle = 'إضافة عناوين وتحديد الموقع الجغرافي';
        } elseif (str_starts_with($routeName, 'consumer.ratings.')) {
            $pageTitle = 'التقييمات والمراجعات';
            $pageSubtitle = 'تقييم المتاجر والخدمات بعد التجربة';
        } elseif (str_starts_with($routeName, 'consumer.profile.')) {
            $pageTitle = 'الحساب الشخصي';
            $pageSubtitle = 'تعديل البيانات وإعدادات الحساب';
        } elseif ($routeName === 'consumer.developer-profile.index') {
            $pageTitle = 'تفاصيل المطور';
            $pageSubtitle = 'بيانات المطور المسؤول عن بناء المنصة';
        }
    @endphp

    <div>
        <div class="fw-bold">{{ $pageTitle }}</div>
        <div class="small text-muted">{{ $pageSubtitle }}</div>
    </div>
    <div class="d-flex align-items-center gap-2 consumer-top-actions">
        <form action="{{ route('consumer.logout') }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-consumer-logout">تسجيل الخروج</button>
        </form>
    </div>
</div>
