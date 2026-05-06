@extends('admin.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">تفاصيل المندوب</h1>
        <a href="{{ route('admin.distributors.index') }}" class="btn btn-outline-secondary">رجوع</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-3">
                    @if ($distributor->image)
                        <img src="{{ asset('storage/' . $distributor->image) }}" alt="صورة المندوب"
                            class="img-fluid rounded-circle border" style="width:160px;height:160px;object-fit:cover;">
                    @else
                        <div class="border rounded p-4 text-center text-muted">لا توجد صورة</div>
                    @endif
                </div>
                <div class="col-md-9">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label text-muted mb-1">اسم المندوب</label>
                            <div class="fw-semibold">{{ $distributor->name }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted mb-1">رقم الهاتف</label>
                            <div>{{ $distributor->phone }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted mb-1">أماكن التوزيع</label>
                            <div class="fw-semibold" style="white-space: pre-line;">
                                {{ $distributor->distribution_points ?: '-' }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted mb-1">الوكيل</label>
                            <div>
                                {{ $distributor->supplier?->business_name ?? ($distributor->supplier?->owner_name ?? '-') }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted mb-1">لوجو الوكيل</label>
                            <div>
                                @if ($distributor->supplier?->logo_url)
                                    <img src="{{ $distributor->supplier->logo_url }}" alt="لوجو الوكيل"
                                        style="width: 90px; height: 70px; object-fit: contain; border: 1px solid #e5e7eb; border-radius: 10px; background: #fff;">
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted mb-1">الفرع</label>
                            <div>{{ $distributor->branch?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted mb-1">الحالة</label>
                            <div>{{ $distributor->status === 'active' ? 'مفعل' : 'معطل' }}</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted mb-1">نوع المركبة</label>
                            <div>{{ $distributor->vehicle_type ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
