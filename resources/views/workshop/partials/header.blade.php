<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'لوحة ورشة الصيانة')</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
    :root {
        --workshop-bg: radial-gradient(circle at 8% 0%, #ecfeff 0%, #ffffff 40%, #f8fafc 100%);
        --workshop-surface: #ffffff;
        --workshop-line: #e2e8f0;
        --workshop-text: #0f172a;
        --workshop-muted: #64748b;
        --workshop-radius-lg: 18px;
        --workshop-radius-md: 14px;
    }

    body {
        margin: 0;
        font-family: 'Cairo', 'Tahoma', sans-serif;
        background: #f8fafc;
        color: var(--workshop-text);
    }

    .workshop-shell {
        display: flex;
        min-height: 100vh;
        gap: 20px;
        padding: 20px;
        background: var(--workshop-bg);
    }

    .workshop-sidebar {
        width: 240px;
        flex: 0 0 240px;
        background: linear-gradient(180deg, #081a3a 0%, #0e2f66 62%, #12458b 100%);
        color: #fff;
        padding: 20px 16px;
        border-radius: 18px;
        border: 1px solid rgba(125, 211, 252, 0.2);
        box-shadow: 0 22px 44px rgba(8, 26, 58, 0.35);
        position: sticky;
        top: 20px;
        align-self: flex-start;
    }

    .workshop-main {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .workshop-navbar,
    .workshop-footer,
    .workshop-card {
        background: var(--workshop-surface);
        border: 1px solid var(--workshop-line);
        border-radius: var(--workshop-radius-lg);
    }

    .workshop-navbar,
    .workshop-footer {
        padding: 12px 14px;
    }

    .workshop-card {
        padding: 20px;
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.07);
        min-height: calc(100vh - 210px);
    }

    .workshop-navbar {
        background: linear-gradient(90deg, #0a2550 0%, #0f3a79 60%, #12539a 100%) !important;
        border: 1px solid rgba(125, 211, 252, 0.28);
        box-shadow: 0 14px 28px rgba(8, 26, 58, 0.28);
        color: #f8fbff;
    }

    .workshop-navbar .text-muted {
        color: #c9defc !important;
    }

    .workshop-top-actions .btn {
        border-radius: 10px;
        padding: 0.38rem 0.85rem;
        font-weight: 700;
        border-width: 1px;
        transition: all 0.18s ease;
    }

    .workshop-top-actions .btn-workshop-logout {
        color: #ffe9ee;
        background: rgba(220, 38, 38, 0.2);
        border-color: rgba(254, 202, 202, 0.55);
    }

    .workshop-top-actions .btn-workshop-logout:hover {
        color: #ffffff;
        background: rgba(220, 38, 38, 0.4);
        border-color: rgba(254, 202, 202, 0.9);
        transform: translateY(-1px);
    }

    .workshop-logo {
        width: 100%;
        height: 84px;
        border-radius: 12px;
        background: linear-gradient(145deg, #ffffff 0%, #f0f9ff 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        padding: 10px;
        border: 1px solid rgba(255, 255, 255, 0.35);
        text-decoration: none;
    }

    .workshop-logo img {
        width: 100%;
        object-fit: contain;
    }

    .workshop-sidebar-caption {
        color: rgba(219, 234, 254, 0.9);
        font-size: 12px;
        text-align: center;
        border-bottom: 1px solid rgba(191, 219, 254, 0.35);
        padding-bottom: 12px;
        margin-bottom: 8px;
    }

    .workshop-sidebar .nav-link {
        color: #cbd5e1;
        border-radius: 10px;
        padding: 10px 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid transparent;
        font-weight: 600;
        transition: all .18s ease;
    }

    .workshop-sidebar .nav-link i {
        font-size: 1rem;
        width: 18px;
        text-align: center;
        flex-shrink: 0;
        opacity: 0.95;
    }

    .workshop-sidebar .nav-link.active,
    .workshop-sidebar .nav-link:hover {
        background: rgba(219, 234, 254, 0.14);
        border-color: rgba(219, 234, 254, 0.35);
        color: #fff;
        transform: translateX(-1px);
    }

    .workshop-sidebar .nav-link.active {
        color: #0b2a57;
        background: linear-gradient(90deg, #bfdbfe 0%, #a5f3fc 100%);
        border-color: rgba(255, 255, 255, 0.8);
        box-shadow: 0 10px 18px rgba(14, 165, 233, 0.26);
    }

    .workshop-footer {
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

    .workshop-footer-line {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #dbeafe;
        font-size: 12px;
        white-space: nowrap;
    }

    .workshop-footer-line strong {
        color: #ffffff;
    }

    .workshop-footer-separator {
        color: #a8c8f0;
    }

    .workshop-footer-chip {
        border: 1px solid rgba(191, 219, 254, 0.55);
        background: rgba(255, 255, 255, 0.1);
        color: #eff6ff;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 12px;
        text-decoration: none;
    }

    .workshop-footer-chip:hover {
        color: #ffffff;
        border-color: rgba(219, 234, 254, 0.9);
        background: rgba(219, 234, 254, 0.2);
    }

    .workshop-section-title {
        font-weight: 800;
        margin-bottom: 8px;
    }

    .workshop-section-subtitle {
        color: var(--workshop-muted);
        margin-bottom: 16px;
    }

    .workshop-stat {
        border: 1px solid rgba(148, 163, 184, 0.28);
        border-radius: var(--workshop-radius-md);
        padding: 14px;
        background: linear-gradient(140deg, #334155 0%, #1e293b 100%);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        height: 100%;
        position: relative;
        overflow: hidden;
    }

    .workshop-stat::before {
        content: '';
        position: absolute;
        top: -20px;
        left: -24px;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.14);
    }

    .workshop-stat-label {
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.85rem;
        margin-bottom: 6px;
        position: relative;
        z-index: 1;
    }

    .workshop-stat-value {
        font-size: 1.3rem;
        font-weight: 800;
        color: #ffffff;
        position: relative;
        z-index: 1;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    .workshop-card .row.g-3.mb-3>div:nth-child(1) .workshop-stat {
        background: linear-gradient(140deg, #2563eb 0%, #1d4ed8 55%, #1e40af 100%);
    }

    .workshop-card .row.g-3.mb-3>div:nth-child(2) .workshop-stat {
        background: linear-gradient(140deg, #059669 0%, #047857 55%, #065f46 100%);
    }

    .workshop-card .row.g-3.mb-3>div:nth-child(3) .workshop-stat {
        background: linear-gradient(140deg, #d97706 0%, #b45309 55%, #92400e 100%);
    }

    .workshop-card .row.g-3.mb-3>div:nth-child(4) .workshop-stat {
        background: linear-gradient(140deg, #7c3aed 0%, #6d28d9 55%, #5b21b6 100%);
    }

    .workshop-panel {
        border: 1px solid #dbe7f7;
        border-radius: var(--workshop-radius-md);
        background: linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
        padding: 14px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
    }

    .workshop-badge {
        display: inline-block;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .workshop-list {
        margin: 0;
        padding-right: 18px;
    }

    .workshop-list li {
        margin-bottom: 8px;
        color: #334155;
    }

    @media (max-width: 991.98px) {
        .workshop-shell {
            display: block;
            padding: 14px;
        }

        .workshop-sidebar {
            width: 100%;
            position: static;
            margin-bottom: 12px;
        }

        .workshop-main {
            gap: 12px;
        }

        .workshop-card {
            min-height: auto;
            padding: 16px;
        }

        .workshop-top-actions {
            margin-top: 8px;
            width: 100%;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .workshop-footer {
            flex-direction: column;
            text-align: center;
        }
    }
</style>
