<aside class="branch-sidebar">
    <a href="{{ route('branch.dashboard') }}" class="branch-logo" aria-label="الصفحة الرئيسية">
        <img src="{{ asset('assets/images/logo.png') }}" alt="الشعار">
    </a>
    <div class="branch-sidebar-caption">إدارة عمليات الفرع اليومية</div>

    <ul class="nav nav-pills flex-column gap-1">
        <li class="nav-item">
            <a href="{{ route('branch.dashboard') }}"
                class="nav-link {{ request()->routeIs('branch.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i><span>لوحة التحكم</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('branch.profile') }}"
                class="nav-link {{ request()->routeIs('branch.profile') ? 'active' : '' }}">
                <i class="bi bi-person-badge-fill"></i><span>البروفايل</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('branch.orders.index') }}"
                class="nav-link {{ request()->is('branch/orders*') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i><span>الطلبات</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('branch.inventory.index') }}"
                class="nav-link {{ request()->is('branch/inventory*') ? 'active' : '' }}">
                <i class="bi bi-boxes"></i><span>المخزون والتسعير</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('branch.distributors.index') }}"
                class="nav-link {{ request()->is('branch/distributors*') ? 'active' : '' }}">
                <i class="bi bi-truck"></i><span>المندوبون</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('branch.clients.index') }}"
                class="nav-link {{ request()->is('branch/clients*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i><span>العملاء</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('branch.reports.index') }}"
                class="nav-link {{ request()->is('branch/reports*') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line-fill"></i><span>التقارير</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('branch.settings.pdf-templates.index') }}"
                class="nav-link {{ request()->is('branch/settings/pdf-templates*') ? 'active' : '' }}">
                <i class="bi bi-filetype-pdf"></i><span>قوالب التقارير</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('branch.replenishment.index') }}"
                class="nav-link {{ request()->is('branch/replenishment*') ? 'active' : '' }}">
                <i class="bi bi-box-arrow-in-down"></i><span>طلب توريد</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('branch.users.index') }}"
                class="nav-link {{ request()->is('branch/users*') ? 'active' : '' }}">
                <i class="bi bi-person-fill"></i><span>مستخدمو الفرع</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('branch.alerts.index') }}"
                class="nav-link {{ request()->is('branch/alerts*') ? 'active' : '' }}">
                <i class="bi bi-bell-fill"></i><span>التنبيهات</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('branch.products.index') }}"
                class="nav-link {{ request()->is('branch/products*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i><span>المنتجات</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('branch.payments.index') }}"
                class="nav-link {{ request()->is('branch/payments*') ? 'active' : '' }}">
                <i class="bi bi-credit-card-2-front-fill"></i><span>المدفوعات والتحصيل</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('branch.payment-methods.index') }}"
                class="nav-link {{ request()->is('branch/payment-methods*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i><span>إدارة طرق الدفع</span>
            </a>
        </li>
    </ul>
</aside>