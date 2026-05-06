@extends('branch.layout.app')

@section('title', 'تسجيل دخول الفرع')

@section('content')
<div class="card border-0 shadow-sm w-100" style="max-width: 520px; border-radius: 16px;">
    <div class="card-body p-5">
        <h1 class="h4 fw-bold mb-2 text-center">تسجيل دخول مدير الفرع</h1>
        <p class="text-muted text-center mb-4">الدخول إلى لوحة إدارة عمليات الفرع</p>

        @if ($errors->any())
        <div class="alert alert-danger text-center py-2">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('branch.login.submit') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">رقم الهاتف</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', '770027718') }}"
                    required autofocus>
            </div>

            <div class="mb-4">
                <label class="form-label">كلمة المرور</label>
                <input type="password" name="password" class="form-control" value="123456" required>
            </div>

            <button type="submit" class="btn btn-dark w-100">تسجيل الدخول</button>
        </form>
    </div>
</div>
@endsection