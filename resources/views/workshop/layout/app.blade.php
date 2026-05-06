<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    @include('workshop.partials.header')
</head>

<body>
    @if (auth('workshop')->check())
        <div class="workshop-shell">
            @include('workshop.partials.sidebar')

            <div class="workshop-main">
                @include('workshop.partials.navbar')

                <div class="workshop-card">
                    @yield('content')
                </div>

                @include('workshop.partials.footer')
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
