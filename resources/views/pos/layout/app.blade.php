<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    @include('pos.partials.header')
</head>

<body>
    @auth
        <div class="pos-shell">
            @include('pos.partials.sidebar')
            <div class="pos-main">@include('pos.partials.navbar')<div class="pos-card">@yield('content')</div>
                @include('pos.partials.footer')</div>
        </div>
    @else
        <main class="min-vh-100 d-flex align-items-center justify-content-center p-3">
            @yield('content')
        </main>
    @endauth

    @stack('scripts')
</body>

</html>
