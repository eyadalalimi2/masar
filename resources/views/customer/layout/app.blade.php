<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    @include('customer.partials.header')
</head>

<body>
    @if (auth('customer')->check())
        <div class="customer-shell">
            @include('customer.partials.sidebar')

            <div class="customer-main">
                @include('customer.partials.navbar')

                <div class="customer-card">
                    @yield('content')
                </div>

                @include('customer.partials.footer')
            </div>
        </div>
    @else
        <main class="min-vh-100 d-flex align-items-center justify-content-center p-3">
            @yield('content')
        </main>
    @endif

    @stack('scripts')
</body>

</html>
