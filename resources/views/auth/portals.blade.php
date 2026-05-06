<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول موحد</title>
    <style>
        :root {
            --ink: #0f172a;
            --muted: #5b6b84;
            --line: #d7e2f0;
            --card: rgba(255, 255, 255, 0.86);
            --card-strong: #ffffff;
            --primary: #1e3a8a;
            --primary-2: #2563eb;
            --accent: #0ea5e9;
            --radius-xl: 26px;
            --radius-md: 14px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Tajawal", "Segoe UI", Tahoma, sans-serif;
            color: var(--ink);
            background: url("{{ asset('assets/images/login.png') }}") center center / cover no-repeat fixed;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            padding: 28px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 82% 18%, rgba(14, 165, 233, 0.24) 0%, rgba(14, 165, 233, 0) 35%),
                linear-gradient(132deg, rgba(2, 6, 23, 0.54), rgba(30, 58, 138, 0.36));
            pointer-events: none;
        }

        .unified-login {
            width: min(740px, 100%);
            position: relative;
            z-index: 1;
            background: var(--card);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.45);
            border-radius: var(--radius-xl);
            box-shadow:
                0 24px 70px rgba(2, 6, 23, 0.32),
                0 3px 14px rgba(15, 23, 42, 0.16);
            overflow: hidden;
            animation: cardEnter 560ms ease both;
        }

        .hero {
            background:
                radial-gradient(circle at 0% 0%, rgba(125, 211, 252, 0.2) 0%, rgba(125, 211, 252, 0) 35%),
                linear-gradient(120deg, #020617 0%, var(--primary) 56%, var(--primary-2) 100%);
            color: #fff;
            padding: 24px 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.18);
        }

        .hero-kicker {
            margin: 0 0 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.12);
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .hero h1 {
            margin: 0 0 8px;
            font-size: clamp(1.4rem, 2.9vw, 1.95rem);
            font-weight: 800;
            line-height: 1.35;
        }

        .hero p {
            margin: 0;
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.86);
            max-width: 620px;
        }

        .body {
            padding: 22px;
        }

        #unifiedLoginForm {
            margin: 0 0 0 auto;
            text-align: right;
            background: var(--card-strong);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 16px;
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
        }

        .roles {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 9px;
            margin-bottom: 14px;
        }

        .role-btn {
            border: 1px solid var(--line);
            border-radius: 12px;
            background: linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
            color: #1e293b;
            font-family: inherit;
            font-size: 0.88rem;
            font-weight: 700;
            padding: 10px 8px;
            cursor: pointer;
            transition: all 160ms ease;
            opacity: 0;
            transform: translateY(8px);
            animation: roleFadeIn 360ms ease forwards;
        }

        .role-btn:nth-child(1) {
            animation-delay: 40ms;
        }

        .role-btn:nth-child(2) {
            animation-delay: 90ms;
        }

        .role-btn:nth-child(3) {
            animation-delay: 140ms;
        }

        .role-btn:nth-child(4) {
            animation-delay: 190ms;
        }

        .role-btn:nth-child(5) {
            animation-delay: 240ms;
        }

        .role-btn:nth-child(6) {
            animation-delay: 290ms;
        }

        .role-btn:nth-child(7) {
            animation-delay: 340ms;
        }

        .role-btn:hover {
            transform: translateY(-1px);
            border-color: #aac8ff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.1);
        }

        .role-btn.is-active {
            color: #ffffff;
            border-color: transparent;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-2) 100%);
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.34);
        }

        .panel-meta {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .panel-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
        }

        .panel-chip {
            font-size: 0.78rem;
            font-weight: 700;
            color: #1e40af;
            background: #dbeafe;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            padding: 4px 10px;
        }

        .panel-note {
            margin: 0 0 14px;
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.6;
        }

        .alert {
            margin-bottom: 14px;
            border-radius: 12px;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            padding: 11px 12px;
            font-size: 0.9rem;
        }

        .field {
            margin-bottom: 13px;
        }

        .field label {
            display: block;
            margin-bottom: 6px;
            font-weight: 700;
            font-size: 0.92rem;
            color: #1e293b;
            text-align: right;
        }

        .field input {
            width: 100%;
            border: 1px solid #c8d4e6;
            border-radius: 11px;
            padding: 12px;
            font-family: inherit;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 150ms ease, box-shadow 150ms ease, background 150ms ease;
            text-align: right;
            direction: rtl;
            background: #fbfdff;
        }

        .field input:focus {
            border-color: var(--primary-2);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.16);
            background: #ffffff;
        }

        .submit {
            width: 100%;
            border: none;
            border-radius: 12px;
            padding: 13px 16px;
            background: linear-gradient(126deg, var(--primary) 0%, var(--primary-2) 100%);
            color: #fff;
            font-family: inherit;
            font-size: 0.98rem;
            font-weight: 800;
            cursor: pointer;
            transition: transform 140ms ease, box-shadow 140ms ease;
        }

        .submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(37, 99, 235, 0.38);
        }

        .footer-note {
            margin: 10px 2px 0;
            color: #6b7280;
            font-size: 0.8rem;
            text-align: center;
        }

        @keyframes cardEnter {
            0% {
                opacity: 0;
                transform: translateY(16px) scale(0.985);
            }

            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes roleFadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 760px) {
            body {
                justify-content: center;
                padding: 16px;
            }

            .roles {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            #unifiedLoginForm {
                max-width: 100%;
                margin: 0;
            }

            .hero,
            .body {
                padding: 18px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .unified-login,
            .role-btn {
                animation: none;
            }

            .role-btn,
            .submit {
                transition: none;
            }
        }
    </style>
</head>

<body>
    @php
    $roles = [
    'admin' => [
    'label' => 'أدمن',
    'title' => 'لوحة الإدارة',
    'description' => 'سجّل دخولك كأدمن لإدارة كامل النظام.',
    'action' => route('admin.login.submit'),
    'phone' => '770450001',
    'password' => '123456',
    ],
    'agent' => [
    'label' => 'وكيل',
    'title' => 'لوحة الوكيل',
    'description' => 'سجّل دخولك كوكيل لإدارة العمليات اليومية.',
    'action' => route('agent.login.submit'),
    'phone' => '770027719',
    'password' => '123456',
    ],
    'branch' => [
    'label' => 'فرع',
    'title' => 'لوحة الفرع',
    'description' => 'سجّل دخولك كفرع لمتابعة الطلبات والمبيعات.',
    'action' => route('branch.login.submit'),
    'phone' => '770027718',
    'password' => '123456',
    ],
    'distributor' => [
    'label' => 'مندوب',
    'title' => 'لوحة المندوب',
    'description' => 'سجّل دخولك كمندوب لمتابعة التسليم والتحصيل.',
    'action' => route('distributor.login.submit'),
    'phone' => '770450301',
    'password' => '123456',
    ],
    'pos' => [
    'label' => 'محل تجاري',
    'title' => 'لوحة المحلات التجارية',
    'description' => 'سجّل دخولك كصاحب محل لإدارة البيع والطلبات.',
    'action' => route('pos.login.submit'),
    'phone' => '770450601',
    'password' => '123456',
    ],
    'workshop' => [
    'label' => 'ورشة',
    'title' => 'لوحة ورشة الصيانة',
    'description' => 'سجّل دخولك كورشة لإدارة الخدمات والمواعيد.',
    'action' => route('workshop.login.submit'),
    'phone' => '770450401',
    'password' => '123456',
    ],
    'consumer' => [
    'label' => 'مستهلك فردي',
    'title' => 'لوحة المستهلك الفردي',
    'description' => 'سجّل دخولك كمستهلك لمتابعة طلباتك.',
    'action' => route('consumer.login.submit'),
    'phone' => '770450501',
    'password' => '123456',
    ],
    ];
    @endphp

    <section class="unified-login" aria-label="تسجيل دخول موحد">
        <header class="hero">
            <p class="hero-kicker">بوابة المصادقة الموحدة</p>
            <h1>تسجيل دخول موحّد لجميع أنواع المستخدمين</h1>
            <p>اختر نوع الحساب، وستتغير بيانات الحقول تلقائيًا مع التحويل إلى اللوحة المناسبة بعد الدخول.</p>
        </header>

        <div class="body">
            <div class="roles" id="roleButtons" role="tablist" aria-label="أنواع الحسابات">
                @foreach ($roles as $key => $role)
                <button type="button" class="role-btn" data-role="{{ $key }}" role="tab"
                    aria-selected="false">
                    {{ $role['label'] }}
                </button>
                @endforeach
            </div>

            <form id="unifiedLoginForm" method="POST" action="{{ route('admin.login.submit') }}"
                data-roles='@json($roles)'
                data-selected-role="{{ (string) old('_portal', '') }}"
                data-phone-old="{{ (string) old('phone', '') }}"
                data-has-errors="{{ $errors->any() ? '1' : '0' }}">
                @csrf
                <input type="hidden" name="_portal" id="portalField" value="{{ old('_portal', 'admin') }}">

                <div class="panel-meta">
                    <h2 class="panel-title" id="panelTitle"></h2>
                    <span class="panel-chip" id="panelChip">دخول آمن</span>
                </div>

                <p class="panel-note" id="panelDescription"></p>

                @if ($errors->any())
                <div class="alert" role="alert">{{ $errors->first() }}</div>
                @endif

                <div class="field">
                    <label for="phoneInput">رقم الهاتف</label>
                    <input id="phoneInput" type="text" name="phone" value="{{ old('phone') }}" required autofocus>
                </div>

                <div class="field">
                    <label for="passwordInput">كلمة المرور</label>
                    <input id="passwordInput" type="password" name="password" required>
                </div>

                <button type="submit" class="submit">تسجيل الدخول</button>
                <p class="footer-note">سيتم تحويلك تلقائيًا إلى لوحة التحكم المناسبة بعد التحقق.</p>
            </form>
        </div>
    </section>

    <script>
        (function() {
            const roleButtons = Array.from(document.querySelectorAll('.role-btn'));
            const form = document.getElementById('unifiedLoginForm');
            const roles = JSON.parse(form?.dataset?.roles || '{}');
            const phoneInput = document.getElementById('phoneInput');
            const passwordInput = document.getElementById('passwordInput');
            const portalField = document.getElementById('portalField');
            const panelTitle = document.getElementById('panelTitle');
            const panelDescription = document.getElementById('panelDescription');
            const panelChip = document.getElementById('panelChip');
            const submitButton = form.querySelector('.submit');
            const selectedRoleFromOld = form?.dataset?.selectedRole || '';
            const phoneFromOld = form?.dataset?.phoneOld || '';
            const hasServerErrors = (form?.dataset?.hasErrors || '0') === '1';

            function applyRole(roleKey, keepTypedPhone = false) {
                const role = roles[roleKey] || roles.admin;

                portalField.value = roleKey;
                form.action = role.action;
                panelTitle.textContent = role.title;
                panelDescription.textContent = role.description;
                panelChip.textContent = `دخول ${role.label}`;
                submitButton.textContent = `تسجيل الدخول كـ ${role.label}`;
                passwordInput.value = role.password;

                if (!keepTypedPhone) {
                    phoneInput.value = role.phone;
                }

                roleButtons.forEach((button) => {
                    const isActive = button.dataset.role === roleKey;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });

                localStorage.setItem('unified-portal-role', roleKey);
            }

            roleButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    applyRole(button.dataset.role);
                });
            });

            const storedRole = localStorage.getItem('unified-portal-role');
            const initialRole = selectedRoleFromOld || storedRole || 'admin';
            const keepTypedPhone = hasServerErrors && Boolean(phoneFromOld);
            applyRole(initialRole, keepTypedPhone);

            if (keepTypedPhone) {
                phoneInput.value = phoneFromOld;
            }
        })();
    </script>
</body>

</html>