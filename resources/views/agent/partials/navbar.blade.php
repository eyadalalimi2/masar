<div class="agent-navbar d-flex flex-wrap justify-content-between align-items-center gap-2">
    @php
        $routeName = request()->route()?->getName() ?? '';

        $pageTitle = 'لوحة الوكيل';
        $pageSubtitle = 'إدارة عمليات الوكيل اليومية';

        if ($routeName === 'agent.dashboard') {
            $pageTitle = 'الرئيسية';
            $pageSubtitle = 'ملخص الأداء اليومي ومؤشرات العمل';
        } elseif ($routeName === 'agent.profile') {
            $pageTitle = 'ملفي الشخصي';
            $pageSubtitle = 'إدارة بيانات الحساب والإعدادات الشخصية';
        } elseif ($routeName === 'agent.platform-release.index') {
            $pageTitle = 'إصدار المنصة';
            $pageSubtitle = 'معلومات النسخة الحالية وبيئة التشغيل';
        } elseif ($routeName === 'agent.developer-profile.index') {
            $pageTitle = 'تفاصيل المطور';
            $pageSubtitle = 'بيانات المطور المسؤول عن بناء المنصة';
        } elseif (str_starts_with($routeName, 'agent.branches')) {
            $pageTitle = 'إدارة الفروع';
            $pageSubtitle = 'متابعة الفروع وتنظيم عملياتها';
        } elseif (str_starts_with($routeName, 'agent.distributors')) {
            $pageTitle = 'إدارة المندوبين';
            $pageSubtitle = 'متابعة أداء فرق التوزيع الميدانية';
        } elseif (str_starts_with($routeName, 'agent.products')) {
            $pageTitle = 'إدارة المنتجات';
            $pageSubtitle = 'تنظيم المنتجات والتسعير والمخزون';
        } elseif (str_starts_with($routeName, 'agent.inventory')) {
            $pageTitle = 'المخزون والتوزيع';
            $pageSubtitle = 'متابعة مستويات المخزون وخطط التوزيع';
        } elseif (str_starts_with($routeName, 'agent.orders')) {
            $pageTitle = 'إدارة الطلبات';
            $pageSubtitle = 'متابعة الطلبات من الإنشاء حتى الإغلاق';
        } elseif (str_starts_with($routeName, 'agent.payments.commercial-stores')) {
            $pageTitle = 'مدفوعات المحلات التجارية';
            $pageSubtitle = 'مراجعة عمليات الدفع والتحصيل للمحلات التجارية';
        } elseif (str_starts_with($routeName, 'agent.payments.workshops')) {
            $pageTitle = 'مدفوعات الورش';
            $pageSubtitle = 'مراجعة عمليات الدفع والتحصيل للورش';
        } elseif (str_starts_with($routeName, 'agent.payments')) {
            $pageTitle = 'المدفوعات';
            $pageSubtitle = 'مراجعة عمليات الدفع والحالة المالية';
        } elseif (str_starts_with($routeName, 'agent.accounts')) {
            $pageTitle = 'حسابات العملاء';
            $pageSubtitle = 'إدارة الأرصدة والحسابات المستحقة';
        } elseif (str_starts_with($routeName, 'agent.commercial-stores')) {
            $pageTitle = 'إدارة المحلات التجارية';
            $pageSubtitle = 'إدارة حسابات المحلات التجارية بشكل مستقل';
        } elseif (str_starts_with($routeName, 'agent.workshops')) {
            $pageTitle = 'إدارة الورش';
            $pageSubtitle = 'إدارة حسابات الورش بشكل مستقل';
        } elseif (str_starts_with($routeName, 'agent.customers')) {
            $pageTitle = 'العملاء التجاريون';
            $pageSubtitle = 'إدارة قاعدة العملاء والعلاقات التجارية';
        } elseif (str_starts_with($routeName, 'agent.reports')) {
            $pageTitle = 'التقارير والتحليلات';
            $pageSubtitle = 'قراءة المؤشرات واتخاذ قرارات مبنية على البيانات';
        } elseif (str_starts_with($routeName, 'agent.coverage')) {
            $pageTitle = 'إدارة المناطق';
            $pageSubtitle = 'تنظيم نطاقات التغطية الميدانية';
        } elseif (str_starts_with($routeName, 'agent.replenishment')) {
            $pageTitle = 'طلبات توريد الفروع';
            $pageSubtitle = 'متابعة الطلبات التشغيلية للفروع';
        } elseif (str_starts_with($routeName, 'agent.alerts')) {
            $pageTitle = 'التنبيهات';
            $pageSubtitle = 'مراقبة التنبيهات والإشعارات المهمة';
        }
    @endphp

    <div>
        <div class="fw-bold">{{ $pageTitle }}</div>
        <div class="small text-muted">{{ $pageSubtitle }}</div>
    </div>
    <div class="d-flex align-items-center gap-2 agent-top-actions">
        <form action="{{ route('agent.logout') }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-agent-logout">تسجيل الخروج</button>
        </form>
    </div>
</div>
