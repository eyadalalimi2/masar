@extends('agent.layout.app')

@section('content')
    <div class="card border-0 shadow-sm w-100" style="max-width: 520px; border-radius: 16px;">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <div class="mb-3 d-flex align-items-center justify-content-center"
                    style="height: 96px; border: 1px dashed #d1d5db; border-radius: 12px; background: #f8fafc;">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="الشعار"
                        style="max-width: 260px; width: 100%; height: 72px; object-fit: contain;">
                </div>
                <h1 class="h4 fw-bold mb-2">تسجيل دخول الوكيل</h1>
                <p class="text-muted mb-0">أدخل رقم الهاتف وكلمة المرور</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger text-center py-2">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('agent.login.submit') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">رقم الهاتف</label>
                    <input type="text" name="phone" value="{{ old('phone', '770027719') }}" class="form-control"
                        required autofocus>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">كلمة المرور</label>
                    <input type="password" name="password" value="123456" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-dark w-100 fw-semibold">تسجيل الدخول</button>
            </form>
        </div>
    </div>
@endsection
