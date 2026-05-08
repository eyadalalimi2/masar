@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 fw-bold mb-0">تفاصيل الوكيل</h1>
    <div class="d-flex gap-2">
        @if (!$supplier->is_verified && $supplier->has_verification_request)
        <form action="{{ route('admin.suppliers.verify', $supplier) }}" method="POST"
            onsubmit="return confirm('قبول طلب توثيق الوكيل؟ بعد القبول لن يستطيع الوكيل تعديل بياناته.');">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-outline-success">قبول التوثيق</button>
        </form>
        @else
        @if ($supplier->is_verified)
        <span class="btn btn-success disabled">موثّق</span>
        @else
        <span class="btn btn-secondary disabled">لا يوجد طلب</span>
        @endif
        @endif
        <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="btn btn-outline-primary">تعديل</a>
        <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary">رجوع</a>
    </div>
</div>

@if (session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="accordion" id="supplierDetailsAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="supplierAgentInfoHeading">
                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse"
                        data-bs-target="#supplierAgentInfoCollapse" aria-expanded="false"
                        aria-controls="supplierAgentInfoCollapse">
                        بيانات الوكيل
                    </button>
                </h2>
                <div id="supplierAgentInfoCollapse" class="accordion-collapse collapse"
                    aria-labelledby="supplierAgentInfoHeading" data-bs-parent="#supplierDetailsAccordion">
                    <div class="accordion-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="fw-semibold text-muted small">اسم المالك</div>
                                <div>{{ $supplier->owner_name }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="fw-semibold text-muted small">البريد الإلكتروني</div>
                                <div>{{ $supplier->email ?: 'غير محدد' }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="fw-semibold text-muted small">رقم الهاتف</div>
                                <div>{{ $supplier->phone }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="fw-semibold text-muted small">واتساب</div>
                                <div>{{ $supplier->whatsapp }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="fw-semibold text-muted small">رقم البطاقة الشخصية</div>
                                <div>{{ $supplier->national_id_number }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="fw-semibold text-muted small">صورة الوكيل</div>
                                @if ($supplier->agent_image)
                                <img src="{{ asset('storage/' . $supplier->agent_image) }}" alt="صورة الوكيل"
                                    style="width: 120px; height: 120px; object-fit: cover; border: 1px solid #e5e7eb; border-radius: 999px;">
                                @else
                                <div class="text-muted">لا توجد صورة</div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <div class="fw-semibold text-muted small">صورة البطاقة الشخصية</div>
                                @if ($supplier->national_id_image)
                                <img src="{{ asset('storage/' . $supplier->national_id_image) }}"
                                    alt="صورة البطاقة الشخصية"
                                    style="width: 220px; height: 130px; object-fit: cover; border: 1px solid #e5e7eb; border-radius: 10px;">
                                @else
                                <div class="text-muted">لا توجد صورة</div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <div class="fw-semibold text-muted small">الحالة</div>
                                @if ($supplier->status === 'active')
                                <span class="badge text-bg-success">مفعل</span>
                                @else
                                <span class="badge text-bg-secondary">معطل</span>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <div class="fw-semibold text-muted small">التوثيق</div>
                                @if ($supplier->is_verified)
                                <span class="badge text-bg-success">موثّق</span>
                                <div class="small text-muted mt-1">
                                    {{ $supplier->verified_at?->format('Y-m-d H:i') }}
                                </div>
                                @elseif ($supplier->has_verification_request)
                                <span class="badge text-bg-warning">طلب قيد المراجعة</span>
                                <div class="small text-muted mt-1">
                                    {{ $supplier->verification_requested_at?->format('Y-m-d H:i') }}
                                </div>
                                @else
                                <span class="badge text-bg-secondary">لا يوجد طلب توثيق</span>
                                @endif
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="supplierBusinessInfoHeading">
                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse"
                        data-bs-target="#supplierBusinessInfoCollapse" aria-expanded="false"
                        aria-controls="supplierBusinessInfoCollapse">
                        بيانات النشاط التجاري
                    </button>
                </h2>
                <div id="supplierBusinessInfoCollapse" class="accordion-collapse collapse"
                    aria-labelledby="supplierBusinessInfoHeading" data-bs-parent="#supplierDetailsAccordion">
                    <div class="accordion-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="fw-semibold text-muted small">الاسم التجاري</div>
                                <div>
                                    <span>{{ $supplier->business_name }}</span>
                                    @if ($supplier->is_verified)
                                    <img src="{{ asset('assets/images/viv.png') }}" alt="موثق" class="ms-1 align-middle"
                                        style="width: 18px; height: 18px; object-fit: contain;">
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="fw-semibold mb-2">الشعار</div>
                                @if ($supplier->logo)
                                <img src="{{ asset('storage/' . $supplier->logo) }}" alt="الشعار"
                                    style="width: 180px; height: 110px; object-fit: contain; border: 1px solid #e5e7eb; border-radius: 10px; padding: 8px; background: #fff;">
                                @else
                                <div class="text-muted">لا يوجد شعار</div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <div class="fw-semibold text-muted small">الموقع</div>
                                <div class="d-flex gap-2 align-items-center">
                                    <span>{{ $supplier->gps_location }}</span>
                                    <a href="https://www.google.com/maps?q={{ rawurlencode($supplier->gps_location ?: $supplier->address) }}"
                                        target="_blank" rel="noopener noreferrer"
                                        class="btn btn-sm btn-outline-dark">فتح الخرائط</a>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="fw-semibold text-muted small">العنوان</div>
                                <div>{{ $supplier->address }}</div>
                            </div>

                            <div class="col-12">
                                <div class="fw-semibold text-muted small">أوقات الدوام</div>
                                @php
                                $weekDays = [
                                'saturday' => 'السبت',
                                'sunday' => 'الأحد',
                                'monday' => 'الاثنين',
                                'tuesday' => 'الثلاثاء',
                                'wednesday' => 'الأربعاء',
                                'thursday' => 'الخميس',
                                'friday' => 'الجمعة',
                                ];
                                $schedule = $supplier->working_hours_schedule;
                                @endphp
                                <div class="table-responsive border rounded mt-2">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>اليوم</th>
                                                <th>الحالة</th>
                                                <th>بداية الدوام</th>
                                                <th>نهاية الدوام</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($weekDays as $dayKey => $dayLabel)
                                            @php
                                            $dayData = $schedule[$dayKey] ?? [
                                            'enabled' => false,
                                            'start' => null,
                                            'end' => null,
                                            ];
                                            $enabled = (bool) data_get($dayData, 'enabled', false);
                                            @endphp
                                            <tr>
                                                <td class="fw-semibold">{{ $dayLabel }}</td>
                                                <td>
                                                    @if ($enabled)
                                                    <span class="badge text-bg-success">مفعّل</span>
                                                    @else
                                                    <span class="badge text-bg-secondary">معطّل</span>
                                                    @endif
                                                </td>
                                                <td>{{ data_get($dayData, 'start') ?: '-' }}</td>
                                                <td>{{ data_get($dayData, 'end') ?: '-' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="supplierLicensesInfoHeading">
                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse"
                        data-bs-target="#supplierLicensesInfoCollapse" aria-expanded="false"
                        aria-controls="supplierLicensesInfoCollapse">
                        بيانات السجل والتراخيص
                    </button>
                </h2>
                <div id="supplierLicensesInfoCollapse" class="accordion-collapse collapse"
                    aria-labelledby="supplierLicensesInfoHeading" data-bs-parent="#supplierDetailsAccordion">
                    <div class="accordion-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="fw-semibold text-muted small">رقم السجل التجاري</div>
                                <div>{{ $supplier->commercial_reg_number }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="fw-semibold text-muted small">صورة السجل التجاري</div>
                                @if ($supplier->commercial_reg_image)
                                <div class="mt-2 d-flex align-items-center gap-2">
                                    <img src="{{ asset('storage/' . $supplier->commercial_reg_image) }}"
                                        alt="صورة السجل التجاري"
                                        style="width: 220px; height: 130px; object-fit: cover; border: 1px solid #e5e7eb; border-radius: 10px;">
                                    <a href="{{ asset('storage/' . $supplier->commercial_reg_image) }}"
                                        target="_blank" rel="noopener noreferrer"
                                        class="btn btn-sm btn-outline-dark">عرض</a>
                                </div>
                                @else
                                <div class="text-muted">لا توجد صورة</div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <div class="fw-semibold text-muted small">رقم الرخصة</div>
                                <div>{{ $supplier->license_number }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="fw-semibold text-muted small">صورة الرخصة</div>
                                @if ($supplier->license_image)
                                <div class="mt-2 d-flex align-items-center gap-2">
                                    <img src="{{ asset('storage/' . $supplier->license_image) }}"
                                        alt="صورة الرخصة"
                                        style="width: 220px; height: 130px; object-fit: cover; border: 1px solid #e5e7eb; border-radius: 10px;">
                                    <a href="{{ asset('storage/' . $supplier->license_image) }}" target="_blank"
                                        rel="noopener noreferrer" class="btn btn-sm btn-outline-dark">عرض</a>
                                </div>
                                @else
                                <div class="text-muted">لا توجد صورة</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-6">
        <a href="{{ route('admin.branches.index', ['supplier_id' => $supplier->id]) }}"
            class="text-decoration-none text-reset">
            <div class="card border-0 shadow-sm h-100 position-relative">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold">الفروع المرتبطة بالوكيل</div>
                        <div class="text-muted small">انقر لعرض وإدارة الفروع الخاصة بهذا الوكيل</div>
                    </div>
                    <span class="badge text-bg-dark fs-6">{{ $supplier->branches->count() }}</span>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-6">
        <a href="{{ route('admin.distributors.index', ['supplier_id' => $supplier->id]) }}"
            class="text-decoration-none text-reset">
            <div class="card border-0 shadow-sm h-100 position-relative">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold">المندوبون المرتبطون بالوكيل</div>
                        <div class="text-muted small">انقر لعرض وإدارة المندوبين الخاصين بهذا الوكيل</div>
                    </div>
                    <span class="badge text-bg-dark fs-6">{{ $supplier->distributors->count() }}</span>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection