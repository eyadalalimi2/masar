@extends('customer.layout.app')

@php
    $portalTitle = $portalTitle ?? 'تسجيل دخول ورش الصيانه والمحلات التجارية';
    $portalSubtitle = $portalSubtitle ?? 'الدخول إلى لوحة متابعة طلباتك وحسابك';
    $submitRoute = $submitRoute ?? 'customer.login.submit';
    $defaultPhone = $defaultPhone ?? '770450401';
    $defaultPassword = $defaultPassword ?? 'Customer@123';
@endphp

@section('title', $portalTitle)

@section('content')
    <div class="card border-0 shadow-sm w-100" style="max-width: 520px; border-radius: 16px;">
        <div class="card-body p-5">
            <h1 class="h4 fw-bold mb-2 text-center">{{ $portalTitle }}</h1>
            <p class="text-muted text-center mb-4">{{ $portalSubtitle }}</p>

            @if ($errors->any())
                <div class="alert alert-danger text-center py-2">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route($submitRoute) }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $defaultPhone) }}"
                        required autofocus>
                </div>

                <div class="mb-4">
                    <label class="form-label">كلمة المرور</label>
                    <input type="password" name="password" class="form-control" value="{{ $defaultPassword }}" required>
                </div>

                <button type="submit" class="btn btn-dark w-100">تسجيل الدخول</button>
            </form>
        </div>
    </div>
@endsection

