<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'لوحة المندوب')</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --panel-line: #dbe2ea;
        --panel-surface: #ffffff;
        --panel-surface-alt: #f3f6fb;
        --panel-shell-gap: 20px;
        --panel-radius-lg: 18px;
        --panel-sidebar-bg: linear-gradient(180deg, #081a3a 0%, #0e2f66 62%, #12458b 100%);
    }

    body {
        font-family: 'Cairo', sans-serif;
        background:
            radial-gradient(circle at 10% 14%, #e0ecff 0%, transparent 35%),
            radial-gradient(circle at 94% 0%, #cffafe 0%, transparent 30%),
            var(--panel-surface-alt);
        overflow-x: hidden;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    body::-webkit-scrollbar {
        width: 0;
        height: 0;
    }

    html {
        overflow-x: hidden;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    html::-webkit-scrollbar {
        width: 0;
        height: 0;
    }

    .distributor-shell {
        min-height: 100vh;
        display: flex;
        gap: var(--panel-shell-gap);
        padding: var(--panel-shell-gap);
    }

    .distributor-sidebar {
        width: 260px;
        background: var(--panel-sidebar-bg);
        color: #fff;
        padding: 20px 16px;
        border-radius: var(--panel-radius-lg);
        border: 1px solid rgba(125, 211, 252, 0.2);
        box-shadow: 0 22px 44px rgba(8, 26, 58, 0.35);
        position: sticky;
        top: var(--panel-shell-gap);
        align-self: flex-start;
    }

    .distributor-sidebar .nav {
        gap: 6px;
    }

    .distributor-sidebar .nav-link {
        color: #cbd5e1;
        border-radius: 10px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid transparent;
        transition: all .18s ease;
    }

    .distributor-sidebar .nav-link i {
        font-size: 1rem;
        width: 18px;
        text-align: center;
        flex-shrink: 0;
        opacity: 0.95;
    }

    .distributor-sidebar .nav-link.active,
    .distributor-sidebar .nav-link:hover {
        color: #fff;
        background: rgba(219, 234, 254, 0.14);
        border-color: rgba(219, 234, 254, 0.35);
        transform: translateX(-1px);
    }

    .distributor-sidebar .nav-link.active {
        color: #0b2a57;
        background: linear-gradient(90deg, #bfdbfe 0%, #a5f3fc 100%);
        border-color: rgba(255, 255, 255, 0.8);
        box-shadow: 0 10px 18px rgba(14, 165, 233, 0.26);
    }

    .distributor-main {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .distributor-navbar,
    .distributor-footer,
    .distributor-card {
        background: var(--panel-surface);
        border: 1px solid var(--panel-line);
        border-radius: var(--panel-radius-lg);
    }

    .distributor-navbar,
    .distributor-footer {
        padding: 12px 14px;
    }

    .distributor-card {
        padding: 20px;
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.07);
        min-height: calc(100vh - 210px);
    }

    .distributor-navbar {
        background: linear-gradient(90deg, #0a2550 0%, #0f3a79 60%, #12539a 100%) !important;
        border: 1px solid rgba(125, 211, 252, 0.28);
        box-shadow: 0 14px 28px rgba(8, 26, 58, 0.28);
        color: #f8fbff;
    }

    .distributor-navbar .text-muted {
        color: #c9defc !important;
    }

    .distributor-top-actions .btn {
        border-radius: 10px;
        padding: 0.38rem 0.85rem;
        font-weight: 700;
        letter-spacing: 0.1px;
        border-width: 1px;
        transition: all 0.18s ease;
    }

    .distributor-top-actions .btn-distributor-logout {
        color: #ffe9ee;
        background: rgba(220, 38, 38, 0.2);
        border-color: rgba(254, 202, 202, 0.55);
    }

    .distributor-top-actions .btn-distributor-logout:hover {
        color: #ffffff;
        background: rgba(220, 38, 38, 0.4);
        border-color: rgba(254, 202, 202, 0.9);
        transform: translateY(-1px);
    }

    .distributor-logo {
        width: 100%;
        height: 84px;
        border-radius: 12px;
        background: linear-gradient(145deg, #ffffff 0%, #f0f9ff 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 14px;
        padding: 10px;
        border: 1px solid rgba(255, 255, 255, 0.35);
        text-decoration: none;
    }

    .distributor-sidebar-caption {
        margin: -4px 0 10px;
        color: rgba(219, 234, 254, 0.9);
        font-size: 12px;
        text-align: center;
        border-bottom: 1px solid rgba(191, 219, 254, 0.35);
        padding-bottom: 12px;
    }

    .distributor-logo img {
        width: 100%;
        object-fit: contain;
    }

    .distributor-footer {
        border: 1px solid rgba(125, 211, 252, 0.26);
        border-radius: 14px;
        padding: 12px 16px;
        background: linear-gradient(90deg, #0a2550 0%, #0f3a79 60%, #12539a 100%);
        color: #dbeafe;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        box-shadow: 0 12px 22px rgba(8, 26, 58, 0.24);
    }

    .distributor-footer-line {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #dbeafe;
        font-size: 12px;
        white-space: nowrap;
    }

    .distributor-footer-line strong {
        color: #ffffff;
    }

    .distributor-footer-separator {
        color: #a8c8f0;
    }

    .distributor-footer-chip {
        border: 1px solid rgba(191, 219, 254, 0.55);
        background: rgba(255, 255, 255, 0.1);
        color: #eff6ff;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 12px;
        text-decoration: none;
    }

    .distributor-footer-chip:hover {
        color: #ffffff;
        border-color: rgba(219, 234, 254, 0.9);
        background: rgba(219, 234, 254, 0.2);
    }

    @media (max-width: 991.98px) {
        .distributor-shell {
            display: block;
            padding: 14px;
        }

        .distributor-sidebar {
            width: 100%;
            position: static;
            margin-bottom: 12px;
        }

        .distributor-main {
            gap: 12px;
        }

        .distributor-card {
            min-height: auto;
            padding: 16px;
        }

        .distributor-top-actions {
            margin-top: 8px;
            width: 100%;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .distributor-footer {
            flex-direction: column;
            text-align: center;
        }
    }
</style>
