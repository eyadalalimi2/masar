@extends('workshop.layout.app')

@section('title', 'تسجيل دخول ورش الصيانة')

@section('content')
    <style>
        .workshop-login-wrap {
            width: 100%;
            max-width: 540px;
            margin: 12px auto;
        }

        .workshop-login-card {
            border: 1px solid #dbe7f7;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 20px 38px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .workshop-login-head {
            padding: 18px;
            color: #f8fbff;
            background: linear-gradient(135deg, #0a2550 0%, #0f3a79 62%, #1462aa 100%);
        }

        .workshop-login-body {
            padding: 22px;
        }

        .workshop-login-help {
            color: #64748b;
            font-size: 0.92rem;
        }

        .btn-workshop-login {
            border-radius: 10px;
            font-weight: 700;
            border-color: #0f3a79;
            background: linear-gradient(90deg, #0a2550 0%, #0f3a79 60%, #12539a 100%);
        }

        .btn-workshop-login:hover {
            border-color: #0c2f61;
            background: linear-gradient(90deg, #0c2b5b 0%, #124488 60%, #1563b0 100%);
        }
    </style>

    <div class="workshop-login-wrap">
        <div class="workshop-login-card">
            <div class="workshop-login-head">
                <h1 class="h5 fw-bold mb-1">تسجيل دخول ورش الصيانة</h1>
                <p class="mb-0 text-white-50">الدخول إلى لوحة الورشة الخاصة بك</p>
            </div>

            <div class="workshop-login-body">
                @if ($errors->any())
                    <div class="alert alert-danger text-center py-2">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('workshop.login.submit') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">رقم الهاتف</label>
                        <input type="text" name="phone" class="form-control"
                            value="{{ old('phone', $defaultPhone ?? '770450401') }}" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">كلمة المرور</label>
                        <input type="password" name="password" class="form-control"
                            value="{{ $defaultPassword ?? '123456' }}" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-workshop-login w-100">تسجيل الدخول</button>
                </form>

                <p class="workshop-login-help mt-3 mb-0">
                    هذه الصفحة مخصصة للورش فقط. حسابات المحلات التجارية تستخدم صفحة دخول منفصلة عبر مسار المحلات التجارية.
                </p>
            </div>
        </div>
    </div>
@endsection

