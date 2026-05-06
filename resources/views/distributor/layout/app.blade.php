<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    @include('distributor.partials.header')
</head>

<body>
    @auth
        <div class="distributor-shell">
            @include('distributor.partials.sidebar')

            <div class="distributor-main">
                @include('distributor.partials.navbar')

                <div class="distributor-card">
                    @yield('content')
                </div>

                @include('distributor.partials.footer')
            </div>
        </div>
    @else
        <main class="min-vh-100 d-flex align-items-center justify-content-center p-3">
            @yield('content')
        </main>
    @endauth

    @stack('scripts')
</body>

</html>
