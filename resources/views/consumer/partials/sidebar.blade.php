<aside class="consumer-sidebar">
    <a href="{{ route('consumer.dashboard') }}" class="consumer-logo" aria-label="الصفحة الرئيسية">
        <img src="{{ asset('assets/images/logo.png') }}" alt="الشعار">
    </a>
    <div class="consumer-sidebar-caption">إدارة حساب المستهلك الفردي</div>

    <ul class="nav nav-pills flex-column gap-1">
        <li class="nav-item">
            <a href="{{ route('consumer.dashboard') }}"
                class="nav-link {{ request()->routeIs('consumer.dashboard') || request()->routeIs('consumer.home') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i><span>الرئيسية</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('consumer.browse') }}"
                class="nav-link {{ request()->routeIs('consumer.browse') || request()->routeIs('consumer.store.show') ? 'active' : '' }}">
                <i class="bi bi-shop-window"></i><span>التصفح والمتاجر</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('consumer.tracking') }}"
                class="nav-link {{ request()->routeIs('consumer.tracking') ? 'active' : '' }}">
                <i class="bi bi-geo-alt-fill"></i><span>تتبع الطلبات</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('consumer.history') }}"
                class="nav-link {{ request()->routeIs('consumer.history') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i><span>سجل الطلبات</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('consumer.addresses.index') }}"
                class="nav-link {{ request()->routeIs('consumer.addresses.*') ? 'active' : '' }}">
                <i class="bi bi-geo-fill"></i><span>العناوين</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('consumer.ratings.index') }}"
                class="nav-link {{ request()->routeIs('consumer.ratings.*') ? 'active' : '' }}">
                <i class="bi bi-star-fill"></i><span>التقييمات</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('consumer.profile.index') }}"
                class="nav-link {{ request()->routeIs('consumer.profile.*') ? 'active' : '' }}">
                <i class="bi bi-person-circle"></i><span>الحساب</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('consumer.settings.pdf-templates.index') }}"
                class="nav-link {{ request()->is('consumer/settings/pdf-templates*') ? 'active' : '' }}">
                <i class="bi bi-filetype-pdf"></i><span>قوالب التقارير</span>
            </a>
        </li>
    </ul>
</aside>