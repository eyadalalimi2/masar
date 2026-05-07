@extends('pos.layout.app')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h5 fw-bold mb-1">توثيق الحساب</h1>
            <p class="text-muted mb-0">إدارة وثائق التوثيق للمحل التجاري.</p>
        </div>
        <a href="{{ route('pos.profile.index') }}" class="btn btn-outline-secondary btn-sm">رجوع للبروفايل</a>
    </div>

    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="mb-3 d-flex gap-2 align-items-center">
        @if ($customer->is_verified)
        <span class="badge text-bg-success">الحساب موثق</span>
        @elseif ($customer->verification_requested_at)
        <span class="badge text-bg-warning">طلب التوثيق قيد المراجعة</span>
        <span class="small text-muted">{{ $customer->verification_requested_at?->format('Y-m-d H:i') }}</span>
        @else
        <form action="{{ route('pos.profile.request-verification') }}" method="POST" onsubmit="return confirm('إرسال طلب التوثيق للإدارة؟');">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-success btn-sm">إرسال طلب التوثيق</button>
        </form>
        @endif
    </div>

    <form method="POST" action="{{ route('pos.profile.verification.update') }}" enctype="multipart/form-data" class="card border-0 shadow-sm">
        @csrf
        @method('PUT')
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">رقم البطاقة الشخصية</label>
                <input type="text" name="national_id_number" class="form-control" value="{{ old('national_id_number', $customer->national_id_number) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">رقم السجل التجاري</label>
                <input type="text" name="commercial_reg_number" class="form-control" value="{{ old('commercial_reg_number', $customer->commercial_reg_number) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">رقم الرخصة</label>
                <input type="text" name="license_number" class="form-control" value="{{ old('license_number', $customer->license_number) }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">صورة البطاقة الشخصية</label>
                <input type="file" name="national_id_image" class="form-control" accept="image/*">
                @if ($customer->national_id_image_url)
                <a href="{{ $customer->national_id_image_url }}" target="_blank" rel="noopener noreferrer" class="d-inline-block mt-2">
                    <img src="{{ $customer->national_id_image_url }}" alt="صورة البطاقة" class="rounded border" style="width:78px;height:78px;object-fit:cover;">
                </a>
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label">صورة السجل التجاري</label>
                <input type="file" name="commercial_reg_image" class="form-control" accept="image/*">
                @if ($customer->commercial_reg_image_url)
                <a href="{{ $customer->commercial_reg_image_url }}" target="_blank" rel="noopener noreferrer" class="d-inline-block mt-2">
                    <img src="{{ $customer->commercial_reg_image_url }}" alt="صورة السجل" class="rounded border" style="width:78px;height:78px;object-fit:cover;">
                </a>
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label">صورة الرخصة</label>
                <input type="file" name="license_image" class="form-control" accept="image/*">
                @if ($customer->license_image_url)
                <a href="{{ $customer->license_image_url }}" target="_blank" rel="noopener noreferrer" class="d-inline-block mt-2">
                    <img src="{{ $customer->license_image_url }}" alt="صورة الرخصة" class="rounded border" style="width:78px;height:78px;object-fit:cover;">
                </a>
                @endif
            </div>
        </div>
        <div class="card-footer bg-white">
            <button type="submit" class="btn btn-dark">حفظ وثائق التوثيق</button>
        </div>
    </form>
</div>
@endsection