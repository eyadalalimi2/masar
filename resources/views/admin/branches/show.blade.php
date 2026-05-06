@extends('admin.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">تفاصيل الفرع</h1>
            <p class="text-muted mb-0">عرض بيانات الفرع المرتبط بالوكيل</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.distributors.index', ['branch_id' => $branch->id]) }}" class="btn btn-outline-dark">عرض
                المندوبين المرتبطين</a>
            <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-outline-primary">تعديل</a>
            <a href="{{ route('admin.branches.index', ['supplier_id' => $branch->supplier_id]) }}"
                class="btn btn-outline-secondary">رجوع</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="fw-semibold text-muted small">اسم الوكيل</div>
                    <div>{{ $branch->supplier?->owner_name ?: '-' }}</div>
                </div>

                <div class="col-md-6">
                    <div class="fw-semibold text-muted small">الاسم التجاري</div>
                    <div>{{ $branch->supplier?->business_name ?: '-' }}</div>
                </div>

                <div class="col-md-6">
                    <div class="fw-semibold text-muted small">لوجو الوكيل</div>
                    @if ($branch->supplier?->logo_url)
                        <img src="{{ $branch->supplier->logo_url }}" alt="لوجو الوكيل"
                            style="width: 120px; height: 90px; object-fit: contain; border: 1px solid #e5e7eb; border-radius: 10px; background: #fff;">
                    @else
                        <div>-</div>
                    @endif
                </div>

                <div class="col-md-6">
                    <div class="fw-semibold text-muted small">اسم الفرع</div>
                    <div>{{ $branch->name }}</div>
                </div>

                <div class="col-md-6">
                    <div class="fw-semibold text-muted small">رقم الهاتف</div>
                    <div>{{ $branch->phone }}</div>
                </div>

                <div class="col-md-6">
                    <div class="fw-semibold text-muted small">اسم مدير الفرع</div>
                    <div>{{ $branch->branch_manager_name ?: '-' }}</div>
                </div>

                <div class="col-md-6">
                    <div class="fw-semibold text-muted small">صورة مدير الفرع</div>
                    @if ($branch->branch_manager_image)
                        <img src="{{ asset('storage/' . $branch->branch_manager_image) }}" alt="صورة مدير الفرع"
                            style="width: 120px; height: 120px; object-fit: cover; border: 1px solid #e5e7eb; border-radius: 999px;">
                    @else
                        <div>-</div>
                    @endif
                </div>

                <div class="col-md-6">
                    <div class="fw-semibold text-muted small">الحالة</div>
                    @if ($branch->status === 'active')
                        <span class="badge text-bg-success">مفعل</span>
                    @else
                        <span class="badge text-bg-secondary">معطل</span>
                    @endif
                </div>

                <div class="col-md-6">
                    <div class="fw-semibold text-muted small">عدد المندوبين المرتبطين</div>
                    <div>{{ $branch->distributors->count() }}</div>
                </div>

                <div class="col-12">
                    <div class="fw-semibold text-muted small">العنوان</div>
                    <div>{{ $branch->address }}</div>
                </div>

                <div class="col-12">
                    <div class="fw-semibold text-muted small">الموقع</div>
                    @if ($branch->gps_location)
                        <div class="d-flex align-items-center gap-2">
                            <span>{{ $branch->gps_location }}</span>
                            <a href="https://www.google.com/maps?q={{ rawurlencode($branch->gps_location) }}"
                                target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-dark">عرض الموقع
                                بالخريطة</a>
                        </div>
                    @else
                        <div>-</div>
                    @endif
                </div>

                <div class="col-12">
                    <div class="fw-semibold text-muted small">أوقات الدوام</div>
                    @include('admin.partials.working-hours-display', [
                        'schedule' => $branch->working_hours_schedule,
                    ])
                </div>
            </div>
        </div>
    </div>
@endsection
