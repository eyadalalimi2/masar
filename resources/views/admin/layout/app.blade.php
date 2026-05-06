<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    @include('admin.partials.header')
    <style>
        .pac-container {
            z-index: 2147483647 !important;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.15);
            overflow: hidden;
            direction: rtl;
            text-align: right;
            font-family: inherit;
        }

        .pac-item {
            padding: 10px 12px;
            font-size: 14px;
            border-top: 1px solid #f1f5f9;
            cursor: pointer;
        }

        .pac-item:hover {
            background: #f8fafc;
        }

        .pac-item-query {
            font-weight: 600;
            color: #0f172a;
        }
    </style>
</head>

<body>

    @auth
        <div class="admin-shell" id="adminShell">

            {{-- Sidebar --}}
            @include('admin.partials.sidebar')

            {{-- Content Area --}}
            <div class="admin-main">

                @include('admin.partials.navbar')

                <main class="admin-content">
                    <div class="admin-content-card">
                        @yield('content')
                    </div>
                </main>

                {{-- Footer --}}
                @include('admin.partials.footer')

            </div>
        </div>
    @else
        {{-- بدون تسجيل دخول (مثل صفحة تسجيل الدخول) --}}
        <main class="guest-main">
            @yield('content')
        </main>

    @endauth

    @stack('scripts')

</body>

</html>
