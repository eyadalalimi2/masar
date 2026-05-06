<aside class="customer-sidebar">
    <a href="{{ route('customer.dashboard') }}" class="customer-logo" aria-label="الصفحة الرئيسية">
        <img src="{{ asset('assets/images/logo.png') }}" alt="الشعار">
    </a>
    <div class="customer-sidebar-caption">إدارة حسابات العملاء والورش والمحلات التجارية</div>

    <ul class="nav nav-pills flex-column gap-1">
        <li class="nav-item">
            <a href="{{ route('customer.dashboard') }}"
                class="nav-link {{ request()->routeIs('customer.dashboard') ? 'active' : '' }}">
                لوحة التحكم
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('customer.orders.index') }}"
                class="nav-link {{ request()->routeIs('customer.orders.*') ? 'active' : '' }}">
                الطلبات
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('customer.payments.index') }}"
                class="nav-link {{ request()->routeIs('customer.payments.*') ? 'active' : '' }}">
                المدفوعات
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('customer.profile.index') }}"
                class="nav-link {{ request()->routeIs('customer.profile.*') ? 'active' : '' }}">
                الملف الشخصي
            </a>
        </li>
    </ul>
</aside>

