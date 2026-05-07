<aside class="distributor-sidebar">
    <a href="{{ route('distributor.dashboard') }}" class="distributor-logo" aria-label="الصفحة الرئيسية">
        <img src="{{ asset('assets/images/logo.png') }}" alt="الشعار">
    </a>
    <div class="distributor-sidebar-caption">إدارة مسارات المندوب والعمليات الميدانية</div>

    <ul class="nav nav-pills flex-column gap-1">
        <li class="nav-item">
            <a href="{{ route('distributor.dashboard') }}"
                class="nav-link {{ request()->routeIs('distributor.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i><span>لوحة التحكم</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('distributor.orders.index') }}"
                class="nav-link {{ request()->is('distributor/orders*') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i><span>الطلبات</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('distributor.products.index') }}"
                class="nav-link {{ request()->is('distributor/products*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i><span>المنتجات</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('distributor.profile') }}"
                class="nav-link {{ request()->is('distributor/profile*') ? 'active' : '' }}">
                <i class="bi bi-person-circle"></i><span>الملف الشخصي</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('distributor.payments.index') }}"
                class="nav-link {{ request()->is('distributor/payments*') ? 'active' : '' }}">
                <i class="bi bi-credit-card-2-front-fill"></i><span>التحصيلات</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('distributor.alerts.index') }}"
                class="nav-link {{ request()->is('distributor/alerts*') ? 'active' : '' }}">
                <i class="bi bi-bell-fill"></i><span>التنبيهات</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('distributor.settings.pdf-templates.index') }}"
                class="nav-link {{ request()->is('distributor/settings/pdf-templates*') ? 'active' : '' }}">
                <i class="bi bi-filetype-pdf"></i><span>قوالب التقارير</span>
            </a>
        </li>
    </ul>
</aside>