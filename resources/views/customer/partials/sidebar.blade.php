<aside class="customer-sidebar">
    @php
    $customer = auth('customer')->user();
    $customerType = (string) ($customer->type ?? '');
    $typeLabel = match ($customerType) {
    'wholesale_trader' => 'تاجر الجملة',
    'retail_store' => 'المحل التجاري',
    'workshop' => 'ورشة الصيانة',
    default => 'العميل',
    };
    @endphp

    <a href="{{ route('customer.dashboard') }}" class="customer-logo" aria-label="الصفحة الرئيسية">
        <img src="{{ asset('assets/images/logo.png') }}" alt="الشعار">
    </a>
    <div class="customer-sidebar-caption">إدارة {{ $typeLabel }}: الطلبات، المدفوعات، والملف الشخصي</div>

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
        @if ($customerType === 'wholesale_trader')
        <li class="nav-item">
            <a href="{{ route('customer.wholesale.products.index') }}"
                class="nav-link {{ request()->routeIs('customer.wholesale.products.*') ? 'active' : '' }}">
                إدارة المنتجات
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('customer.wholesale.orders.index') }}"
                class="nav-link {{ request()->routeIs('customer.wholesale.orders.*') ? 'active' : '' }}">
                إدارة الطلبات
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('customer.wholesale.customers.index') }}"
                class="nav-link {{ request()->routeIs('customer.wholesale.customers.*') ? 'active' : '' }}">
                إدارة العملاء
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('customer.payment-methods.index') }}"
                class="nav-link {{ request()->routeIs('customer.payment-methods.*') ? 'active' : '' }}">
                طرق الدفع
            </a>
        </li>
        @endif
        <li class="nav-item">
            <a href="{{ route('customer.profile.index') }}"
                class="nav-link {{ request()->routeIs('customer.profile.*') ? 'active' : '' }}">
                الملف الشخصي
            </a>
        </li>
    </ul>
</aside>