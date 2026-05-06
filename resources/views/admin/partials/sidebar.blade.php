<aside class="admin-sidebar" id="adminSidebar">

    <div class="sidebar-title">
        <a href="{{ url('/admin/dashboard') }}" class="sidebar-logo" aria-label="الصفحة الرئيسية">
            <img src="{{ asset('assets/images/logo.png') }}" alt="Masar Logo">
        </a>
        <div class="sidebar-caption">إدارة مركزية لجميع وحدات النظام</div>
    </div>

    <ul class="nav nav-pills flex-column">

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/dashboard') ? 'active' : '' }}"
                href="{{ url('/admin/dashboard') }}">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>الرئيسية</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/suppliers*') ? 'active' : '' }}"
                href="{{ route('admin.suppliers.index') }}">
                <i class="bi bi-people-fill"></i>
                <span>الوكلاء</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/branches*') ? 'active' : '' }}"
                href="{{ route('admin.branches.index') }}">
                <i class="bi bi-diagram-3-fill"></i>
                <span>الفروع</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/distributors*') ? 'active' : '' }}"
                href="{{ route('admin.distributors.index') }}">
                <i class="bi bi-truck"></i>
                <span>المندوبون</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/products*') ? 'active' : '' }}"
                href="{{ route('admin.products.index') }}">
                <i class="bi bi-box-seam"></i>
                <span>المنتجات</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/categories*') ? 'active' : '' }}"
                href="{{ route('admin.categories.index') }}">
                <i class="bi bi-tags-fill"></i>
                <span>إدارة التصنيفات</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/variant-types*') ? 'active' : '' }}"
                href="{{ route('admin.variant-types.index') }}">
                <i class="bi bi-sliders"></i>
                <span>إدارة المواصفات</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/variant-values*') ? 'active' : '' }}"
                href="{{ route('admin.variant-values.index') }}">
                <i class="bi bi-list-ul"></i>
                <span>إدارة قيم المواصفات</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/orders*') ? 'active' : '' }}"
                href="{{ route('admin.orders.index') }}">
                <i class="bi bi-receipt-cutoff"></i>
                <span>الطلبات</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/delivery*') ? 'active' : '' }}"
                href="{{ route('admin.delivery.index') }}">
                <i class="bi bi-truck-flatbed"></i>
                <span>إدارة التوصيل</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/inventory*') ? 'active' : '' }}"
                href="{{ route('admin.inventory.index') }}">
                <i class="bi bi-boxes"></i>
                <span>مراقبة المخزون</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/payments*') ? 'active' : '' }}"
                href="{{ route('admin.payments.index') }}">
                <i class="bi bi-credit-card-2-front-fill"></i>
                <span>المدفوعات</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/accounts*') ? 'active' : '' }}"
                href="{{ route('admin.accounts.index') }}">
                <i class="bi bi-wallet2"></i>
                <span>حسابات العملاء</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/commercial-stores*') ? 'active' : '' }}"
                href="{{ route('admin.commercial-stores.index') }}">
                <i class="bi bi-shop-window"></i>
                <span>المحلات التجارية</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/workshops*') ? 'active' : '' }}"
                href="{{ route('admin.workshops.index') }}">
                <i class="bi bi-wrench-adjustable-circle"></i>
                <span>ورش الصيانة</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/consumers*') ? 'active' : '' }}"
                href="{{ route('admin.consumers.index') }}">
                <i class="bi bi-person-badge-fill"></i>
                <span>إدارة العملاء التجزئة</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}"
                href="{{ route('admin.users.index') }}">
                <i class="bi bi-person-lines-fill"></i>
                <span>المستخدمون</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/account-opening-excel*') ? 'active' : '' }}"
                href="{{ route('admin.account-opening-excel.index') }}">
                <i class="bi bi-file-earmark-excel"></i>
                <span>استيراد وتصدير فتح الحسابات</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/auth-verification*') ? 'active' : '' }}"
                href="{{ route('admin.auth-verification.index') }}">
                <i class="bi bi-shield-check"></i>
                <span>إدارة التحقق والتوثيق</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/tasks*') ? 'active' : '' }}"
                href="{{ route('admin.tasks.index') }}">
                <i class="bi bi-list-check"></i>
                <span>المهام</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/reports*') ? 'active' : '' }}"
                href="{{ route('admin.reports.index') }}">
                <i class="bi bi-bar-chart-line-fill"></i>
                <span>التقارير والتحليلات</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/locations*') ? 'active' : '' }}"
                href="{{ route('admin.locations.index') }}">
                <i class="bi bi-geo-alt-fill"></i>
                <span>إدارة المدن والمناطق</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/content*') ? 'active' : '' }}"
                href="{{ route('admin.content.index') }}">
                <i class="bi bi-image-fill"></i>
                <span>إدارة المحتوى</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/pricing*') ? 'active' : '' }}"
                href="{{ route('admin.pricing.index') }}">
                <i class="bi bi-cash-stack"></i>
                <span>التسعير والعمولات والاشتراكات</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/settings*') ? 'active' : '' }}"
                href="{{ route('admin.settings.index') }}">
                <i class="bi bi-gear-fill"></i>
                <span>إعدادات النظام</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/payment-methods*') ? 'active' : '' }}"
                href="{{ route('admin.payment-methods.index') }}">
                <i class="bi bi-credit-card-2-front-fill"></i>
                <span>إدارة طرق الدفع</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/roles*') ? 'active' : '' }}"
                href="{{ route('admin.roles.index') }}">
                <i class="bi bi-diagram-2-fill"></i>
                <span>الأدوار والصلاحيات</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/admins*') ? 'active' : '' }}"
                href="{{ route('admin.admins.index') }}">
                <i class="bi bi-person-gear"></i>
                <span>إدارة الأدمن</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/audit-logs*') ? 'active' : '' }}"
                href="{{ route('admin.audit-logs.index') }}">
                <i class="bi bi-journal-text"></i>
                <span>سجل العمليات</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/notifications*') ? 'active' : '' }}"
                href="{{ route('admin.notifications.index') }}">
                <i class="bi bi-bell-fill"></i>
                <span>مركز الإشعارات</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/platform-release*') ? 'active' : '' }}"
                href="{{ route('admin.platform-release.index') }}">
                <i class="bi bi-rocket-takeoff-fill"></i>
                <span>إصدار المنصة</span>
            </a>
        </li>

    </ul>

</aside>