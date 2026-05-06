<aside class="workshop-sidebar">
    <a href="{{ route('workshop.dashboard') }}" class="workshop-logo" aria-label="الصفحة الرئيسية">
        <img src="{{ asset('assets/images/logo.png') }}" alt="الشعار">
    </a>
    <div class="workshop-sidebar-caption">Workshop = خدمة + منتج + تجربة عميل</div>

    <ul class="nav nav-pills flex-column gap-1">
        <li class="nav-item">
            <a href="{{ route('workshop.dashboard') }}"
                class="nav-link {{ request()->routeIs('workshop.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i><span>لوحة التحكم</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('workshop.services.index') }}"
                class="nav-link {{ request()->routeIs('workshop.services.*') ? 'active' : '' }}">
                <i class="bi bi-gear-fill"></i><span>إدارة الخدمات</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('workshop.products.index') }}"
                class="nav-link {{ request()->routeIs('workshop.products.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i><span>إدارة المنتجات</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('workshop.marketplace.index') }}"
                class="nav-link {{ request()->routeIs('workshop.marketplace.*') ? 'active' : '' }}">
                <i class="bi bi-shop-window"></i><span>السوق</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('workshop.orders.purchase.index') }}"
                class="nav-link {{ request()->routeIs('workshop.orders.purchase.*') ? 'active' : '' }}">
                <i class="bi bi-bag-check-fill"></i><span>طلبات الشراء</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('workshop.orders.service.index') }}"
                class="nav-link {{ request()->routeIs('workshop.orders.service.*') ? 'active' : '' }}">
                <i class="bi bi-tools"></i><span>طلبات الخدمة</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('workshop.appointments.index') }}"
                class="nav-link {{ request()->routeIs('workshop.appointments.*') ? 'active' : '' }}">
                <i class="bi bi-calendar-check-fill"></i><span>المواعيد</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('workshop.execution.index') }}"
                class="nav-link {{ request()->routeIs('workshop.execution.*') ? 'active' : '' }}">
                <i class="bi bi-play-circle-fill"></i><span>تنفيذ الخدمة</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('workshop.sales.index') }}"
                class="nav-link {{ request()->routeIs('workshop.sales.*') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i><span>المبيعات</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('workshop.pricing.index') }}"
                class="nav-link {{ request()->routeIs('workshop.pricing.*') ? 'active' : '' }}">
                <i class="bi bi-tag-fill"></i><span>إدارة الأسعار</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('workshop.customers.index') }}"
                class="nav-link {{ request()->routeIs('workshop.customers.*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i><span>العملاء</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('workshop.maintenance.history') }}"
                class="nav-link {{ request()->routeIs('workshop.maintenance.*') ? 'active' : '' }}">
                <i class="bi bi-car-front-fill"></i><span>سجل الصيانة</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('workshop.reports.index') }}"
                class="nav-link {{ request()->routeIs('workshop.reports.*') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line-fill"></i><span>التقارير</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('workshop.payment-methods.index') }}"
                class="nav-link {{ request()->routeIs('workshop.payment-methods.*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i><span>إدارة طرق الدفع</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('workshop.profile.index') }}"
                class="nav-link {{ request()->routeIs('workshop.profile.*') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i><span>الملف الشخصي والدوام</span>
            </a>
        </li>
    </ul>
</aside>