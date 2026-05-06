<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>لوحة التحكم </title>

{{-- Bootstrap RTL --}}
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

{{-- خطوط عربية احترافية --}}
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --primary: #0b3b8f;
        --primary-soft: #dbeafe;
        --accent: #06b6d4;
        --ink: #0f172a;
        --muted: #64748b;
        --line: #dbe2ea;
        --surface: #ffffff;
        --surface-alt: #f3f6fb;
        --sidebar-bg: linear-gradient(180deg, #081a3a 0%, #0e2f66 62%, #12458b 100%);
        --sidebar-link: #dbeafe;
        --sidebar-link-hover: #ffffff;
        --sidebar-width: 280px;
        --shell-gap: 24px;
        --radius-lg: 20px;
        --radius-md: 12px;
    }

    body {
        font-family: 'Cairo', sans-serif;
        background:
            radial-gradient(circle at 10% 15%, #e0ecff 0%, transparent 36%),
            radial-gradient(circle at 95% 0%, #cffafe 0%, transparent 32%),
            var(--surface-alt);
        color: var(--ink);
        min-height: 100vh;
        overflow-x: hidden;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    body::-webkit-scrollbar {
        width: 0;
        height: 0;
    }

    body.admin-lock {
        overflow: hidden;
        height: 100vh;
        touch-action: none;
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

    .admin-shell {
        min-height: 100vh;
        display: flex;
        gap: var(--shell-gap);
        padding: var(--shell-gap);
    }

    .admin-main {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .admin-content {
        flex: 1;
        padding: 0;
    }

    .admin-content-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius-lg);
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
        min-height: calc(100vh - 210px);
        padding: 24px;
    }

    .admin-navbar {
        background: linear-gradient(90deg, #0a2550 0%, #0f3a79 60%, #12539a 100%) !important;
        border: 1px solid rgba(125, 211, 252, 0.28);
        border-radius: var(--radius-lg);
        backdrop-filter: blur(8px);
        box-shadow: 0 14px 28px rgba(8, 26, 58, 0.28);
        padding: 11px 14px;
    }

    .admin-navbar .text-muted {
        color: #c9defc !important;
    }

    .admin-navbar .navbar-brand {
        color: #f8fbff !important;
        font-weight: 700;
        letter-spacing: 0.2px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .admin-top-actions {
        gap: 8px;
    }

    .admin-top-actions .btn {
        border-radius: 10px;
        padding: 0.38rem 0.85rem;
        font-weight: 700;
        letter-spacing: 0.1px;
        border-width: 1px;
        transition: all 0.18s ease;
    }

    .admin-top-actions .btn:focus {
        box-shadow: 0 0 0 0.2rem rgba(125, 211, 252, 0.28);
    }

    .admin-top-actions .btn-admin-task {
        color: #0b2a57;
        background: linear-gradient(90deg, #c7dbff 0%, #a8d9ff 100%);
        border-color: rgba(255, 255, 255, 0.72);
    }

    .admin-top-actions .btn-admin-task:hover {
        color: #082247;
        background: linear-gradient(90deg, #b7d2ff 0%, #95ceff 100%);
        transform: translateY(-1px);
    }

    .admin-top-actions .btn-admin-agent {
        color: #edf5ff;
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(191, 219, 254, 0.55);
    }

    .admin-top-actions .btn-admin-agent:hover {
        color: #ffffff;
        background: rgba(219, 234, 254, 0.22);
        border-color: rgba(219, 234, 254, 0.85);
        transform: translateY(-1px);
    }

    .admin-top-actions .btn-admin-logout {
        color: #ffe9ee;
        background: rgba(220, 38, 38, 0.2);
        border-color: rgba(254, 202, 202, 0.55);
    }

    .admin-top-actions .btn-admin-logout:hover {
        color: #ffffff;
        background: rgba(220, 38, 38, 0.4);
        border-color: rgba(254, 202, 202, 0.9);
        transform: translateY(-1px);
    }

    .admin-sidebar {
        width: var(--sidebar-width);
        background: var(--sidebar-bg);
        border-radius: var(--radius-lg);
        border: 1px solid rgba(125, 211, 252, 0.2);
        box-shadow: 0 22px 44px rgba(8, 26, 58, 0.35);
        padding: 20px 16px;
        position: sticky;
        top: var(--shell-gap);
        align-self: flex-start;
        height: auto;
        overflow: visible;
    }

    .sidebar-title {
        color: #eff6ff;
        margin-bottom: 18px;
        padding-bottom: 14px;
        border-bottom: 1px solid rgba(191, 219, 254, 0.35);
        text-align: center;
    }

    .sidebar-caption {
        margin-top: 8px;
        color: rgba(219, 234, 254, 0.9);
        font-size: 12px;
        letter-spacing: .2px;
    }

    .sidebar-logo {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 92px;
        border-radius: 14px;
        background: linear-gradient(145deg, #ffffff 0%, #f0f9ff 100%);
        border: 1px solid rgba(255, 255, 255, 0.4);
        padding: 10px 14px;
        margin-bottom: 10px;
        text-decoration: none;
    }

    .sidebar-logo img {
        width: 100%;

        object-fit: contain;
    }

    .admin-sidebar .nav {
        gap: 6px;
    }

    .admin-sidebar .nav-item {
        position: relative;
    }

    .admin-sidebar .nav-link {
        color: var(--sidebar-link);
        border-radius: 10px;
        padding: 10px 13px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }

    .admin-sidebar .nav-link i {
        font-size: 1rem;
        width: 18px;
        text-align: center;
        flex-shrink: 0;
        opacity: 0.95;
    }

    .admin-sidebar .nav-link:hover {
        color: var(--sidebar-link-hover);
        background: rgba(219, 234, 254, 0.14);
        border-color: rgba(219, 234, 254, 0.35);
        transform: translateX(-2px);
    }

    .admin-sidebar .nav-link.active {
        color: #0b2a57;
        background: linear-gradient(90deg, #bfdbfe 0%, #a5f3fc 100%);
        border-color: rgba(255, 255, 255, 0.8);
        box-shadow: 0 10px 18px rgba(14, 165, 233, 0.26);
    }

    .admin-footer {
        border: 1px solid rgba(125, 211, 252, 0.26);
        border-radius: 14px;
        padding: 14px 16px;
        background: linear-gradient(90deg, #0a2550 0%, #0f3a79 60%, #12539a 100%);
        color: #dbeafe;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        box-shadow: 0 12px 22px rgba(8, 26, 58, 0.24);
        flex-wrap: wrap;
    }

    .admin-footer-line {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #dbeafe;
        font-size: 12px;
        white-space: nowrap;
    }

    .admin-footer-line strong {
        color: #ffffff;
    }

    .admin-footer-separator {
        color: #a8c8f0;
    }

    .admin-footer-dev-link {
        color: #ffffff;
        font-weight: 700;
        text-decoration: none;
        border-bottom: 1px dashed rgba(219, 234, 254, 0.75);
        padding-bottom: 1px;
    }

    .admin-footer-dev-link:hover {
        color: #dbeafe;
        border-bottom-color: rgba(255, 255, 255, 0.95);
    }

    .admin-footer-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .admin-footer-chip {
        border: 1px solid rgba(191, 219, 254, 0.55);
        background: rgba(255, 255, 255, 0.1);
        color: #eff6ff;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 12px;
        text-decoration: none;
    }

    .admin-footer-chip:hover {
        color: #ffffff;
        border-color: rgba(219, 234, 254, 0.9);
        background: rgba(219, 234, 254, 0.2);
    }

    .guest-main {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }

    @media (max-width: 991.98px) {
        .admin-shell {
            padding: 16px;
            gap: 16px;
        }

        .admin-sidebar {
            position: fixed;
            top: 16px;
            right: 16px;
            left: 16px;
            width: auto;
            z-index: 1060;
            height: auto;
            max-height: calc(100vh - 32px);
            overflow-y: auto;
            overscroll-behavior: contain;
            transform: translateY(-110%);
            opacity: 0;
            pointer-events: none;
            transition: all 0.25s ease;
        }

        .sidebar-open .admin-sidebar {
            transform: translateY(0);
            opacity: 1;
            pointer-events: auto;
        }

        .admin-content-card {
            min-height: auto;
            padding: 18px;
        }

        .admin-top-actions {
            margin-top: 8px;
            width: 100%;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .admin-footer {
            justify-content: center;
            text-align: center;
        }

        .admin-footer-meta {
            justify-content: center;
        }
    }
</style>
