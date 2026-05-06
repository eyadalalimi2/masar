<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    @include('branch.partials.header')
</head>

<body>
    @auth
        <div class="branch-shell">
            @include('branch.partials.sidebar')

            <div class="branch-main">
                @include('branch.partials.navbar')

                <div class="branch-card">
                    @yield('content')
                </div>

                @include('branch.partials.footer')
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
