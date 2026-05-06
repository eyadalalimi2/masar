<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'لوحة المحلات التجارية')</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --pos-primary: #0b3b8f;
        --pos-ink: #0f172a;
        --line: #dbe2ea;
        --surface: #ffffff;
        --surface-alt: #f3f6fb;
        --sidebar-width: 260px;
        --shell-gap: 15px;
        --radius-lg: 18px;
        --radius-md: 12px;
        --sidebar-bg: linear-gradient(180deg, #081a3a 0%, #0e2f66 62%, #12458b 100%);
    }

    body {
        font-family: 'Cairo', sans-serif;
        background:
            radial-gradient(circle at 10% 14%, #e0ecff 0%, transparent 35%),
            radial-gradient(circle at 94% 0%, #cffafe 0%, transparent 30%),
            var(--surface-alt);
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

    .pos-shell {
        min-height: 100vh;
        display: flex;
        gap: var(--shell-gap);
        padding: 0 var(--shell-gap) var(--shell-gap);
        align-items: flex-start;
    }

    .pos-main {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        line-height: 0;
    }

    .pos-main>* {
        line-height: normal;
    }

    .pos-sidebar {
        width: var(--sidebar-width);
        background: var(--sidebar-bg);
        color: #fff;
        padding: 20px 16px;
        border-radius: var(--radius-lg);
        border: 1px solid rgba(125, 211, 252, 0.2);
        box-shadow: 0 22px 44px rgba(8, 26, 58, 0.35);
        position: sticky;
        top: 0;
        margin-top: 0;
        align-self: flex-start;
    }

    .pos-sidebar-title {
        color: #eff6ff;
        margin-bottom: 18px;
        padding-bottom: 14px;
        border-bottom: 1px solid rgba(191, 219, 254, 0.35);
        text-align: center;
    }

    .pos-logo {
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

    .pos-logo img {
        width: 100%;
        object-fit: contain;
    }

    .pos-sidebar-caption {
        margin: -4px 0 10px;
        color: rgba(219, 234, 254, 0.9);
        font-size: 12px;
        text-align: center;
        border-bottom: 1px solid rgba(191, 219, 254, 0.35);
        padding-bottom: 12px;
    }

    .pos-sidebar .nav {
        gap: 6px;
    }

    .pos-sidebar .nav-item {
        position: relative;
    }

    .pos-sidebar .nav-link {
        color: #cbd5e1;
        border-radius: 10px;
        padding: 10px 13px;
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid transparent;
        font-weight: 600;
        transition: all .18s ease;
    }

    .pos-sidebar .nav-link i {
        font-size: 1rem;
        width: 18px;
        text-align: center;
        flex-shrink: 0;
        opacity: 0.95;
    }

    .pos-sidebar .nav-link.active,
    .pos-sidebar .nav-link:hover {
        color: #fff;
        background: rgba(219, 234, 254, 0.14);
        border-color: rgba(219, 234, 254, 0.35);
        transform: translateX(-1px);
    }

    .pos-sidebar .nav-link.active {
        color: #0b2a57;
        background: linear-gradient(90deg, #bfdbfe 0%, #a5f3fc 100%);
        border-color: rgba(255, 255, 255, 0.8);
        box-shadow: 0 10px 18px rgba(14, 165, 233, 0.26);
    }

    .pos-navbar,
    .pos-footer,
    .pos-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius-lg);
    }

    .pos-navbar,
    .pos-footer {
        padding: 12px 14px;
    }

    .pos-card {
        padding: 20px;
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.07);
        min-height: calc(100vh - 210px);
    }

    .pos-navbar {
        background: linear-gradient(90deg, #0a2550 0%, #0f3a79 60%, #12539a 100%) !important;
        border: 1px solid rgba(125, 211, 252, 0.28);
        box-shadow: 0 14px 28px rgba(8, 26, 58, 0.28);
        color: #f8fbff;
        margin-top: 0;
    }

    .pos-navbar .text-muted {
        color: #c9defc !important;
    }

    .pos-top-actions {
        gap: 8px;
    }

    .pos-top-actions .btn {
        border-radius: 10px;
        padding: 0.38rem 0.85rem;
        font-weight: 700;
        letter-spacing: 0.1px;
        border-width: 1px;
        transition: all 0.18s ease;
    }

    .pos-top-actions .btn:focus {
        box-shadow: 0 0 0 0.2rem rgba(125, 211, 252, 0.28);
    }

    .pos-top-actions .btn-pos-logout {
        color: #ffe9ee;
        background: rgba(220, 38, 38, 0.2);
        border-color: rgba(254, 202, 202, 0.55);
    }

    .pos-top-actions .btn-pos-logout:hover {
        color: #ffffff;
        background: rgba(220, 38, 38, 0.4);
        border-color: rgba(254, 202, 202, 0.9);
        transform: translateY(-1px);
    }

    .pos-footer {
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

    .pos-footer-line {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #dbeafe;
        font-size: 12px;
        white-space: nowrap;
    }

    .pos-footer-line strong {
        color: #ffffff;
    }

    .pos-footer-separator {
        color: #a8c8f0;
    }

    .pos-footer-chip {
        border: 1px solid rgba(191, 219, 254, 0.55);
        background: rgba(255, 255, 255, 0.1);
        color: #eff6ff;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 12px;
        text-decoration: none;
    }

    .pos-footer-chip:hover {
        color: #ffffff;
        border-color: rgba(219, 234, 254, 0.9);
        background: rgba(219, 234, 254, 0.2);
    }

    .hero-box {
        border-radius: 16px;
        background: linear-gradient(135deg, #111827 0%, #2563eb 100%);
        color: #fff;
        padding: 16px;
        margin-bottom: 14px;
    }

    .stat-card {
        border: 1px solid rgba(148, 163, 184, 0.28);
        border-radius: 14px;
        background: linear-gradient(140deg, #334155 0%, #1e293b 100%);
        padding: 16px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        height: 100%;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: -20px;
        left: -24px;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.14);
    }

    .stat-card .subtle-text {
        color: rgba(255, 255, 255, 0.9);
        position: relative;
        z-index: 1;
    }

    .stat-card .fs-4 {
        color: #ffffff;
        position: relative;
        z-index: 1;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    .pos-card .row.g-3.mb-4>div:nth-child(1) .stat-card {
        background: linear-gradient(140deg, #2563eb 0%, #1d4ed8 55%, #1e40af 100%);
    }

    .pos-card .row.g-3.mb-4>div:nth-child(2) .stat-card {
        background: linear-gradient(140deg, #059669 0%, #047857 55%, #065f46 100%);
    }

    .pos-card .row.g-3.mb-4>div:nth-child(3) .stat-card {
        background: linear-gradient(140deg, #d97706 0%, #b45309 55%, #92400e 100%);
    }

    .pos-card .row.g-3.mb-4>div:nth-child(4) .stat-card {
        background: linear-gradient(140deg, #7c3aed 0%, #6d28d9 55%, #5b21b6 100%);
    }

    .pos-card .row.g-3.mb-4>div:nth-child(5) .stat-card {
        background: linear-gradient(140deg, #0d9488 0%, #0f766e 55%, #115e59 100%);
    }

    .table-wrap {
        border: 1px solid var(--line);
        border-radius: 14px;
        overflow: hidden;
        background: var(--surface);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
    }

    .subtle-text {
        color: #64748b;
    }

    .reveal {
        opacity: 0;
        transform: translateY(10px);
        animation: reveal .45s ease-out forwards;
    }

    .rv1 {
        animation-delay: .05s;
    }

    .rv2 {
        animation-delay: .12s;
    }

    .rv3 {
        animation-delay: .18s;
    }

    @keyframes reveal {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 991.98px) {
        .pos-shell {
            display: block;
            padding: 0 14px 14px;
        }

        .pos-sidebar {
            width: 100%;
            position: static;
            top: auto;
            margin-top: 0;
            margin-bottom: 12px;
        }

        .pos-main {
            gap: 12px;
        }

        .pos-card {
            min-height: auto;
            padding: 16px;
        }

        .pos-top-actions {
            margin-top: 8px;
            width: 100%;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .pos-footer {
            flex-direction: column;
            text-align: center;
        }
    }
</style>
