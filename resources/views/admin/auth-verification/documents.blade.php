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

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">الحقول المستخدمة في التوثيق</div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label text-muted small mb-1">رقم البطاقة الشخصية</label>
                    <div class="form-control bg-light">{{ $documents['national_id_number'] ?: '-' }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted small mb-1">صورة البطاقة الشخصية</label>
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
                    <div class="form-control bg-light">{{ $documents['commercial_reg_number'] ?: '-' }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted small mb-1">صورة السجل التجاري</label>
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
                    <div class="form-control bg-light">{{ $documents['license_number'] ?: '-' }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted small mb-1">صورة الرخصة</label>
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
    </div>
@endsection
