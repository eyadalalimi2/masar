@extends('agent.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">توثيق الحساب</h1>
        <p class="text-muted mb-0">إدارة وثائق التوثيق وإرسال الطلب للإدارة.</p>
    </div>
    <a href="{{ route('agent.profile') }}" class="btn btn-outline-secondary btn-sm">رجوع للبروفايل</a>
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
    @if ($supplier->is_verified)
    <span class="badge text-bg-success">الحساب موثق</span>
    @elseif ($supplier->has_verification_request)
    <span class="badge text-bg-warning">طلب التوثيق قيد المراجعة</span>
    <span class="small text-muted">{{ $supplier->verification_requested_at?->format('Y-m-d H:i') }}</span>
    @else
    <form action="{{ route('agent.profile.request-verification') }}" method="POST" onsubmit="return confirm('إرسال طلب التوثيق للإدارة؟');">
        @csrf
        @method('PATCH')
        <button type="submit" class="btn btn-success btn-sm">إرسال طلب التوثيق</button>
    </form>
    @endif
</div>

<form action="{{ route('agent.profile.verification.update') }}" method="POST" enctype="multipart/form-data" class="card border-0 shadow-sm">
    @csrf
    @method('PUT')

    <div class="card-body row g-3">
        <div class="col-md-4">
            <label class="form-label">رقم البطاقة الشخصية</label>
            <input type="text" name="national_id_number" class="form-control" value="{{ old('national_id_number', $supplier->national_id_number) }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">رقم السجل التجاري</label>
            <input type="text" name="commercial_reg_number" class="form-control" value="{{ old('commercial_reg_number', $supplier->commercial_reg_number) }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">رقم الرخصة</label>
            <input type="text" name="license_number" class="form-control" value="{{ old('license_number', $supplier->license_number) }}" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">صورة البطاقة الشخصية</label>
            <input type="file" name="national_id_image" class="form-control" accept="image/*">
            @if ($supplier->national_id_image_url)
            <a href="{{ $supplier->national_id_image_url }}" target="_blank" rel="noopener noreferrer" class="d-inline-block mt-2">
                <img src="{{ $supplier->national_id_image_url }}" alt="صورة البطاقة" class="rounded border" style="width:78px;height:78px;object-fit:cover;">
            </a>
            @endif
        </div>
        <div class="col-md-4">
            <label class="form-label">صورة السجل التجاري</label>
            <input type="file" name="commercial_reg_image" class="form-control" accept="image/*">
            @if ($supplier->commercial_reg_image_url)
            <a href="{{ $supplier->commercial_reg_image_url }}" target="_blank" rel="noopener noreferrer" class="d-inline-block mt-2">
                <img src="{{ $supplier->commercial_reg_image_url }}" alt="صورة السجل" class="rounded border" style="width:78px;height:78px;object-fit:cover;">
            </a>
            @endif
        </div>
        <div class="col-md-4">
            <label class="form-label">صورة الرخصة</label>
            <input type="file" name="license_image" class="form-control" accept="image/*">
            @if ($supplier->license_image_url)
            <a href="{{ $supplier->license_image_url }}" target="_blank" rel="noopener noreferrer" class="d-inline-block mt-2">
                <img src="{{ $supplier->license_image_url }}" alt="صورة الرخصة" class="rounded border" style="width:78px;height:78px;object-fit:cover;">
            </a>
            @endif
        </div>
    </div>

    <div class="card-footer bg-white">
        <button type="submit" class="btn btn-dark">حفظ وثائق التوثيق</button>
    </div>
</form>
@endsection