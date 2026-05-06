<aside class="pos-sidebar">
    <a href="{{ route('pos.dashboard') }}" class="pos-logo" aria-label="الصفحة الرئيسية">
        <img src="{{ asset('assets/images/logo.png') }}" alt="الشعار">
    </a>
    <div class="pos-sidebar-caption">إدارة العمليات اليومية للمحل التجاري</div>

    <ul class="nav nav-pills flex-column gap-1">
        <li class="nav-item"><a href="{{ route('pos.dashboard') }}"
                class="nav-link {{ request()->routeIs('pos.dashboard') ? 'active' : '' }}"><i
                    class="bi bi-grid-1x2-fill"></i><span>لوحة التحكم</span></a></li>
        <li class="nav-item"><a href="{{ route('pos.marketplace.index') }}"
                class="nav-link {{ request()->is('pos/marketplace*') ? 'active' : '' }}"><i
                    class="bi bi-shop-window"></i><span>السوق</span></a></li>
        <li class="nav-item"><a href="{{ route('pos.orders.index') }}"
                class="nav-link {{ request()->is('pos/orders*') ? 'active' : '' }}"><i
                    class="bi bi-receipt-cutoff"></i><span>الطلبات</span></a></li>
        <li class="nav-item"><a href="{{ route('pos.catalog.index') }}"
                class="nav-link {{ request()->is('pos/catalog*') ? 'active' : '' }}"><i
                    class="bi bi-box-seam"></i><span>المنتجات المحلية</span></a></li>
        <li class="nav-item"><a href="{{ route('pos.products.index') }}"
                class="nav-link {{ request()->is('pos/products*') ? 'active' : '' }}"><i
                    class="bi bi-boxes"></i><span>إدارة المنتجات</span></a></li>
        <li class="nav-item"><a href="{{ route('pos.sales.index') }}"
                class="nav-link {{ request()->is('pos/sales*') ? 'active' : '' }}"><i
                    class="bi bi-cash-coin"></i><span>المبيعات</span></a></li>
        <li class="nav-item"><a href="{{ route('pos.customers.index') }}"
                class="nav-link {{ request()->is('pos/customers*') ? 'active' : '' }}"><i
                    class="bi bi-people-fill"></i><span>العملاء</span></a></li>
        <li class="nav-item"><a href="{{ route('pos.reports.index') }}"
                class="nav-link {{ request()->is('pos/reports*') ? 'active' : '' }}"><i
                    class="bi bi-bar-chart-line-fill"></i><span>التقارير</span></a></li>
        <li class="nav-item"><a href="{{ route('pos.payment-methods.index') }}"
                class="nav-link {{ request()->is('pos/payment-methods*') ? 'active' : '' }}"><i
                    class="bi bi-wallet2"></i><span>إدارة طرق الدفع</span></a></li>
        <li class="nav-item"><a href="{{ route('pos.alerts.index') }}"
                class="nav-link {{ request()->is('pos/alerts*') ? 'active' : '' }}"><i
                    class="bi bi-bell-fill"></i><span>التنبيهات</span></a></li>
        <li class="nav-item"><a href="{{ route('pos.profile.index') }}"
                class="nav-link {{ request()->routeIs('pos.profile.*') ? 'active' : '' }}"><i
                    class="bi bi-person-circle"></i><span>الملف الشخصي</span></a></li>
    </ul>

</aside>