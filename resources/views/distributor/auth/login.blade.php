@extends('distributor.layout.app')

@section('title', 'دخول المندوب')

@section('content')
    <style>
        .login-card {
            max-width: 520px;
            width: 100%;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 22px 44px rgba(15, 23, 42, 0.08);
        }
    </style>

    <div class="card login-card">
        <div class="card-body p-5">
            <h1 class="h4 fw-bold mb-2 text-center">تسجيل دخول المندوب</h1>
            <p class="text-muted text-center mb-4">أدخل رقم الهاتف وكلمة المرور</p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('distributor.login.submit') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', '770450301') }}"
                        required>
                </div>

                <div class="mb-4">
                    <label class="form-label">كلمة المرور</label>
                    <input type="password" name="password" value="123456" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-dark w-100">دخول</button>
            </form>
        </div>
    </div>
@endsection
