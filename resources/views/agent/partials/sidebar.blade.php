<aside class="agent-sidebar">
    <a href="{{ route('agent.dashboard') }}" class="agent-logo" aria-label="الصفحة الرئيسية">
        <img src="{{ asset('assets/images/logo.png') }}" alt="الشعار">
    </a>
    <div class="agent-sidebar-caption">إدارة عمليات الوكيل والفرق الميدانية</div>

    <ul class="nav nav-pills flex-column gap-1">
        <li class="nav-item">
            <a href="{{ route('agent.dashboard') }}"
                class="nav-link {{ request()->routeIs('agent.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i><span>لوحة التحكم</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('agent.profile') }}"
                class="nav-link {{ request()->routeIs('agent.profile') ? 'active' : '' }}">
                <i class="bi bi-person-badge-fill"></i><span>ملفي الشخصي</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('agent.users.index') }}"
                class="nav-link {{ request()->is('agent/users*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i><span>مستخدمو الوكيل</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('agent.branches.index') }}"
                class="nav-link {{ request()->is('agent/branches*') ? 'active' : '' }}">
                <i class="bi bi-diagram-3-fill"></i><span>الفروع</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('agent.distributors.index') }}"
                class="nav-link {{ request()->is('agent/distributors*') ? 'active' : '' }}">
                <i class="bi bi-truck"></i><span>المندوبون</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('agent.products.index') }}"
                class="nav-link {{ request()->is('agent/products*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i><span>المنتجات</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('agent.inventory.index') }}"
                class="nav-link {{ request()->is('agent/inventory*') ? 'active' : '' }}">
                <i class="bi bi-boxes"></i><span>المخزون والتوزيع</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('agent.spread.index') }}"
                class="nav-link {{ request()->is('agent/spread*') ? 'active' : '' }}">
                <i class="bi bi-geo-fill"></i><span>الانتشار</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('agent.orders.index') }}"
                class="nav-link {{ request()->is('agent/orders*') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i><span>الطلبات</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('agent.payments.commercial-stores.index') }}"
                class="nav-link {{ request()->is('agent/payments*') ? 'active' : '' }}">
                <i class="bi bi-credit-card-2-front-fill"></i><span>المدفوعات</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('agent.payment-methods.index') }}"
                class="nav-link {{ request()->is('agent/payment-methods*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i><span>إدارة طرق الدفع</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('agent.accounts.commercial-stores.index') }}"
                class="nav-link {{ request()->is('agent/accounts*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i><span>حسابات العملاء</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('agent.wholesale-traders.index') }}"
                class="nav-link {{ request()->is('agent/wholesale-traders*') ? 'active' : '' }}">
                <i class="bi bi-shop"></i><span>تجار الجملة</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('agent.reports.commercial-stores.index') }}"
                class="nav-link {{ request()->is('agent/reports*') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line-fill"></i><span>التقارير والتحليلات</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('agent.coverage.index') }}"
                class="nav-link {{ request()->is('agent/coverage*') ? 'active' : '' }}">
                <i class="bi bi-geo-alt-fill"></i><span>إدارة المناطق</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('agent.replenishment.index') }}"
                class="nav-link {{ request()->is('agent/replenishment-requests*') ? 'active' : '' }}">
                <i class="bi bi-box-arrow-in-down"></i><span>طلبات توريد الفروع</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('agent.alerts.index') }}"
                class="nav-link {{ request()->is('agent/alerts*') ? 'active' : '' }}">
                <i class="bi bi-bell-fill"></i><span>التنبيهات</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('agent.platform-release.index') }}"
                class="nav-link {{ request()->is('agent/platform-release*') ? 'active' : '' }}">
                <i class="bi bi-rocket-takeoff-fill"></i><span>إصدار المنصة</span>
            </a>
        </li>
    </ul>
</aside>