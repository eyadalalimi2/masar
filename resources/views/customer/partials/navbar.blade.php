<div class="customer-navbar d-flex flex-wrap justify-content-between align-items-center gap-2">
    @php
        $routeName = request()->route()?->getName() ?? '';

        $pageTitle = 'لوحة العميل وورش الصيانة والمحلات التجارية';
        $pageSubtitle = 'إدارة الحسابات المرتبطة والطلبات';

        if ($routeName === 'customer.dashboard') {
            $pageTitle = 'الرئيسية';
            $pageSubtitle = 'ملخص الحسابات والعمليات المرتبطة';
        } elseif (str_starts_with($routeName, 'customer.orders')) {
            $pageTitle = 'الطلبات';
            $pageSubtitle = 'متابعة الطلبات التجارية وحالاتها';
        } elseif (str_starts_with($routeName, 'customer.payments')) {
            $pageTitle = 'المدفوعات';
            $pageSubtitle = 'سجل التحصيل والمدفوعات المرتبطة بالطلبات';
        } elseif (str_starts_with($routeName, 'customer.profile')) {
            $pageTitle = 'الملف الشخصي';
            $pageSubtitle = 'تحديث بيانات الحساب والاطلاع على كشف الحساب';
        } elseif ($routeName === 'customer.developer-profile.index') {
            $pageTitle = 'تفاصيل المطور';
            $pageSubtitle = 'بيانات المطور المسؤول عن بناء المنصة';
        }
    @endphp

    <div>
        <div class="fw-bold">{{ $pageTitle }}</div>
        <div class="small text-muted">{{ $pageSubtitle }}</div>
    </div>
    <div class="d-flex align-items-center gap-2 customer-top-actions">
        <form action="{{ route('customer.logout') }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-customer-logout">تسجيل الخروج</button>
        </form>
    </div>
</div>

