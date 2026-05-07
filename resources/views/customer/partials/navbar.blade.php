<div class="customer-navbar d-flex flex-wrap justify-content-between align-items-center gap-2">
    @php
    $routeName = request()->route()?->getName() ?? '';
    $customer = auth('customer')->user();
    $customerType = (string) ($customer->type ?? '');
    $typeLabel = match ($customerType) {
    'wholesale_trader' => 'تاجر الجملة',
    'retail_store' => 'المحل التجاري',
    'workshop' => 'ورشة الصيانة',
    default => 'العميل',
    };

    $pageTitle = 'لوحة ' . $typeLabel;
    $pageSubtitle = 'إدارة الحساب والطلبات والمدفوعات';

    if ($routeName === 'customer.dashboard') {
    $pageTitle = 'الرئيسية';
    $pageSubtitle = 'ملخص عمليات ' . $typeLabel;
    } elseif (str_starts_with($routeName, 'customer.orders')) {
    $pageTitle = 'الطلبات';
    $pageSubtitle = 'متابعة طلبات ' . $typeLabel . ' وحالاتها';
    } elseif (str_starts_with($routeName, 'customer.payments')) {
    $pageTitle = 'المدفوعات';
    $pageSubtitle = 'سجل التحصيل والمدفوعات المرتبطة بالطلبات';
    } elseif (str_starts_with($routeName, 'customer.wholesale.products')) {
    $pageTitle = 'إدارة المنتجات';
    $pageSubtitle = 'كتالوج المنتجات المتاح لتاجر الجملة';
    } elseif (str_starts_with($routeName, 'customer.wholesale.orders')) {
    $pageTitle = 'إدارة الطلبات';
    $pageSubtitle = 'متابعة الطلبات الواردة إلى تاجر الجملة';
    } elseif (str_starts_with($routeName, 'customer.wholesale.customers')) {
    $pageTitle = 'إدارة العملاء';
    $pageSubtitle = 'متابعة عملاء تاجر الجملة وسجل طلباتهم';
    } elseif (str_starts_with($routeName, 'customer.payment-methods')) {
    $pageTitle = 'طرق الدفع';
    $pageSubtitle = 'إدارة طرق الدفع الخاصة بتاجر الجملة';
    } elseif (str_starts_with($routeName, 'customer.profile')) {
    $pageTitle = 'الملف الشخصي';
    $pageSubtitle = 'تحديث بيانات ' . $typeLabel . ' والاطلاع على كشف الحساب';
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