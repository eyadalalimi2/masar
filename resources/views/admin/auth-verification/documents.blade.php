@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">وثائق ومستندات التوثيق</h1>
        <p class="text-muted mb-0">عرض حقول ومستندات التوثيق المرتبطة بالحساب المحدد.</p>
    </div>
    <a href="{{ route('admin.auth-verification.index') }}" class="btn btn-outline-secondary">رجوع</a>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="text-muted small">نوع الحساب</div>
                <div class="fw-semibold">{{ $accountTypeLabel }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">اسم الحساب</div>
                <div class="fw-semibold">{{ $account->name ?? '-' }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">الهاتف</div>
                <div class="fw-semibold" dir="ltr">{{ $account->phone ?? '-' }}</div>
            </div>
        </div>
        <div class="mt-3 text-muted small">{{ $sourceLabel }}</div>
    </div>
</div>

<form method="POST" action="{{ route('admin.auth-verification.documents.update', ['type' => $accountType, 'id' => $account->id]) }}" enctype="multipart/form-data" class="card border-0 shadow-sm">
    @csrf
    @method('PATCH')
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>الحقول المستخدمة في التوثيق</span>
        <button type="submit" class="btn btn-sm btn-dark">حفظ التعديلات</button>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label text-muted small mb-1">رقم البطاقة الشخصية</label>
                <input type="text" name="national_id_number" class="form-control" value="{{ old('national_id_number', $documents['national_id_number']) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted small mb-1">صورة البطاقة الشخصية</label>
                <input type="file" name="national_id_image" class="form-control mb-2" accept="image/*">
                @if ($documents['national_id_image_url'])
                <a href="{{ $documents['national_id_image_url'] }}" target="_blank" rel="noopener noreferrer">
                    <img src="{{ $documents['national_id_image_url'] }}" alt="صورة البطاقة الشخصية"
                        class="img-fluid rounded border" style="max-height: 220px; object-fit: contain;">
                </a>
                @else
                <div class="form-control bg-light">-</div>
                @endif
            </div>

            <div class="col-md-6">
                <label class="form-label text-muted small mb-1">رقم السجل التجاري</label>
                <input type="text" name="commercial_reg_number" class="form-control" value="{{ old('commercial_reg_number', $documents['commercial_reg_number']) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted small mb-1">صورة السجل التجاري</label>
                <input type="file" name="commercial_reg_image" class="form-control mb-2" accept="image/*">
                @if ($documents['commercial_reg_image_url'])
                <a href="{{ $documents['commercial_reg_image_url'] }}" target="_blank" rel="noopener noreferrer">
                    <img src="{{ $documents['commercial_reg_image_url'] }}" alt="صورة السجل التجاري"
                        class="img-fluid rounded border" style="max-height: 220px; object-fit: contain;">
                </a>
                @else
                <div class="form-control bg-light">-</div>
                @endif
            </div>

            <div class="col-md-6">
                <label class="form-label text-muted small mb-1">رقم الرخصة</label>
                <input type="text" name="license_number" class="form-control" value="{{ old('license_number', $documents['license_number']) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted small mb-1">صورة الرخصة</label>
                <input type="file" name="license_image" class="form-control mb-2" accept="image/*">
                @if ($documents['license_image_url'])
                <a href="{{ $documents['license_image_url'] }}" target="_blank" rel="noopener noreferrer">
                    <img src="{{ $documents['license_image_url'] }}" alt="صورة الرخصة"
                        class="img-fluid rounded border" style="max-height: 220px; object-fit: contain;">
                </a>
                @else
                <div class="form-control bg-light">-</div>
                @endif
            </div>
        </div>
    </div>
</form>
@endsection