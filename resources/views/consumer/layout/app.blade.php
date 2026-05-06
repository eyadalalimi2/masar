<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    @include('consumer.partials.header')
</head>

<body>
    @if (auth('consumer')->check())
        <div class="consumer-shell">
            @include('consumer.partials.sidebar')

            <div class="consumer-main">
                @include('consumer.partials.navbar')

                <div class="consumer-card">
                    @yield('content')
                </div>

                @include('consumer.partials.footer')
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
