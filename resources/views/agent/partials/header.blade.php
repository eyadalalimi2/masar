<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'لوحة تحكم الوكيل')</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --agent-primary: #0b3b8f;
        --agent-ink: #0f172a;
        --agent-line: #dbe2ea;
        --agent-surface: #ffffff;
        --agent-surface-alt: #f3f6fb;
        --agent-shell-gap: 20px;
        --agent-radius-lg: 18px;
        --agent-radius-md: 12px;
        --agent-sidebar-bg: linear-gradient(180deg, #081a3a 0%, #0e2f66 62%, #12458b 100%);
    }

    body {
        font-family: 'Cairo', sans-serif;
        background:
            radial-gradient(circle at 10% 14%, #e0ecff 0%, transparent 35%),
            radial-gradient(circle at 94% 0%, #cffafe 0%, transparent 30%),
            var(--agent-surface-alt);
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

    .agent-shell {
        min-height: 100vh;
        display: flex;
        gap: var(--agent-shell-gap);
        padding: var(--agent-shell-gap);
    }

    .agent-sidebar {
        width: 260px;
        background: var(--agent-sidebar-bg);
        color: #fff;
        padding: 20px 16px;
        border-radius: var(--agent-radius-lg);
        border: 1px solid rgba(125, 211, 252, 0.2);
        box-shadow: 0 22px 44px rgba(8, 26, 58, 0.35);
        position: sticky;
        top: var(--agent-shell-gap);
        align-self: flex-start;
    }

    .agent-sidebar .nav {
        gap: 6px;
    }

    .agent-sidebar .nav-link {
        color: #cbd5e1;
        border-radius: 10px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid transparent;
        transition: all .18s ease;
    }

    .agent-sidebar .nav-link i {
        font-size: 1rem;
        width: 18px;
        text-align: center;
        flex-shrink: 0;
        opacity: 0.95;
    }

    .agent-sidebar .nav-link.active,
    .agent-sidebar .nav-link:hover {
        color: #fff;
        background: rgba(219, 234, 254, 0.14);
        border-color: rgba(219, 234, 254, 0.35);
        transform: translateX(-1px);
    }

    .agent-sidebar .nav-link.active {
        color: #0b2a57;
        background: linear-gradient(90deg, #bfdbfe 0%, #a5f3fc 100%);
        border-color: rgba(255, 255, 255, 0.8);
        box-shadow: 0 10px 18px rgba(14, 165, 233, 0.26);
    }

    .agent-main {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .agent-navbar,
    .agent-footer,
    .agent-card {
        background: var(--agent-surface);
        border: 1px solid var(--agent-line);
        border-radius: var(--agent-radius-lg);
    }

    .agent-navbar,
    .agent-footer {
        padding: 12px 14px;
    }

    .agent-card {
        padding: 20px;
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.07);
        min-height: calc(100vh - 210px);
    }

    .agent-navbar {
        background: linear-gradient(90deg, #0a2550 0%, #0f3a79 60%, #12539a 100%) !important;
        border: 1px solid rgba(125, 211, 252, 0.28);
        box-shadow: 0 14px 28px rgba(8, 26, 58, 0.28);
        color: #f8fbff;
    }

    .agent-navbar .text-muted {
        color: #c9defc !important;
    }

    .agent-top-actions .btn {
        border-radius: 10px;
        padding: 0.38rem 0.85rem;
        font-weight: 700;
        letter-spacing: 0.1px;
        border-width: 1px;
        transition: all 0.18s ease;
    }

    .agent-top-actions .btn-agent-logout {
        color: #ffe9ee;
        background: rgba(220, 38, 38, 0.2);
        border-color: rgba(254, 202, 202, 0.55);
    }

    .agent-top-actions .btn-agent-logout:hover {
        color: #ffffff;
        background: rgba(220, 38, 38, 0.4);
        border-color: rgba(254, 202, 202, 0.9);
        transform: translateY(-1px);
    }

    .agent-logo {
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
    }

    .agent-sidebar-caption {
        margin: -4px 0 10px;
        color: rgba(219, 234, 254, 0.9);
        font-size: 12px;
        text-align: center;
        border-bottom: 1px solid rgba(191, 219, 254, 0.35);
        padding-bottom: 12px;
    }

    .agent-logo img {
        width: 100%;

        object-fit: contain;
    }

    .agent-footer {
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

    .agent-footer-line {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #dbeafe;
        font-size: 12px;
        white-space: nowrap;
    }

    .agent-footer-line strong {
        color: #ffffff;
    }

    .agent-footer-separator {
        color: #a8c8f0;
    }

    .agent-footer-chip {
        border: 1px solid rgba(191, 219, 254, 0.55);
        background: rgba(255, 255, 255, 0.1);
        color: #eff6ff;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 12px;
        text-decoration: none;
    }

    .agent-footer-chip:hover {
        color: #ffffff;
        border-color: rgba(219, 234, 254, 0.9);
        background: rgba(219, 234, 254, 0.2);
    }

    @media (max-width: 991.98px) {
        .agent-shell {
            display: block;
            padding: 14px;
        }

        .agent-sidebar {
            width: 100%;
            position: static;
            margin-bottom: 12px;
        }

        .agent-main {
            gap: 12px;
        }

        .agent-card {
            min-height: auto;
            padding: 16px;
        }

        .agent-top-actions {
            margin-top: 8px;
            width: 100%;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .agent-footer {
            flex-direction: column;
            text-align: center;
        }
    }
</style>
