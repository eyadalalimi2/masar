@extends('admin.layout.app')

@section('content')
    <div class="d-flex align-items-center justify-content-center vh-100">

        <div class="card border-0 shadow-sm w-100" style="max-width: 560px; border-radius: 16px;">
            <div class="card-body p-5">

                {{-- عنوان --}}
                <div class="text-center mb-4">
                    <div class="mb-3 d-flex align-items-center justify-content-center"
                        style="height: 98px; border: 1px dashed #d1d5db; border-radius: 12px; background: #f8fafc;">
                        <img src="{{ asset('assets/images/logo.png') }}" alt="Masar Logo"
                            style="max-width: 260px; width: 100%; height: 72px; object-fit: contain;">
                    </div>
                    <h1 class="h4 fw-bold mb-2">تسجيل دخول الأدمن</h1>
                    <p class="text-muted mb-0">أدخل بياناتك للوصول إلى لوحة التحكم</p>
                </div>

                {{-- الأخطاء --}}
                @if ($errors->any())
                    <div class="alert alert-danger text-center py-2">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-warning text-center py-2">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- النموذج --}}
                <form method="POST" action="{{ route('admin.login.submit') }}">
                    @csrf

                    {{-- رقم الهاتف --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">رقم الهاتف</label>
                        <input type="text" name="phone" value="{{ old('phone', '770450001') }}" class="form-control"
                            placeholder="مثال: 780000000" required autofocus>
                    </div>

                    {{-- كلمة المرور --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">كلمة المرور</label>
                        <input type="password" name="password" value="123456" class="form-control" placeholder="********"
                            required>
                    </div>

                    {{-- زر الدخول --}}
                    <button type="submit" class="btn btn-dark w-100 fw-semibold">
                        تسجيل الدخول
                    </button>

                </form>

            </div>
        </div>

    </div>
@endsection
