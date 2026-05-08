@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">إدارة التحقق والتوثيق</h1>
        <p class="text-muted mb-0">لوحة مستقلة لإدارة حسابات الوكلاء والمحلات التجارية وتجار الجملة وورش الصيانة.</p>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">إجمالي الحسابات</div>
                <div class="h4 fw-bold mb-0">{{ number_format($stats['total_accounts']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">حسابات مفعلة</div>
                <div class="h4 fw-bold text-success mb-0">{{ number_format($stats['active_accounts']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">حسابات معطلة</div>
                <div class="h4 fw-bold text-secondary mb-0">{{ number_format($stats['inactive_accounts']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">طلبات توثيق قيد المراجعة</div>
                <div class="h4 fw-bold text-warning mb-0">{{ number_format($stats['pending_supplier_verifications']) }}
                </div>
            </div>
        </div>
    </div>
</div>

<form method="GET" action="{{ route('admin.auth-verification.index') }}" class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label mb-1">بحث</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                    placeholder="الاسم أو الهاتف أو المعرف">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">نوع الحساب</label>
                <select name="type" class="form-select">
                    <option value="">الكل</option>
                    <option value="agent" @selected(request('type')==='agent' )>وكيل</option>
                    <option value="commercial_store" @selected(request('type')==='commercial_store' )>المحلات التجارية</option>
                    <option value="wholesale_trader" @selected(request('type')==='wholesale_trader' )>تاجر جملة</option>
                    <option value="workshop" @selected(request('type')==='workshop' )>ورشة صيانة</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">الحالة</label>
                <select name="status" class="form-select">
                    <option value="">الكل</option>
                    <option value="active" @selected(request('status')==='active' )>مفعل</option>
                    <option value="inactive" @selected(request('status')==='inactive' )>معطل</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100">تطبيق</button>
                <a href="{{ route('admin.auth-verification.index') }}" class="btn btn-outline-secondary w-100">إعادة</a>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="fw-semibold">إدارة تفعيل الحسابات</span>
        <span class="badge text-bg-success">الحسابات الموثقة: {{ number_format($verifiedAccountsCount) }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white mb-0">
            <thead class="table-light">
                <tr>
                    <th>المعرف</th>
                    <th>الاسم</th>
                    <th>الهاتف</th>
                    <th>نوع الحساب</th>
                    <th>الحالة</th>
                    <th>التوثيق</th>
                    <th>تاريخ الإنشاء</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($accounts as $account)
                <tr>
                    <td>{{ $account->id }}</td>
                    <td>
                        <div class="fw-semibold">{{ $account->name }}</div>
                        @if ($account->type === 'agent' && !empty($account->business_name))
                        <div class="small text-muted">{{ $account->business_name }}</div>
                        @endif
                    </td>
                    <td dir="ltr">{{ $account->phone }}</td>
                    <td>{{ $account->type_label }}</td>
                    <td>
                        @if ($account->status === 'active')
                        <span class="badge text-bg-success">مفعل</span>
                        @else
                        <span class="badge text-bg-secondary">معطل</span>
                        @endif
                    </td>
                    <td>
                        @if ($account->verification_state === 'verified')
                        <span class="badge text-bg-success">موثق</span>
                        @elseif ($account->verification_state === 'pending')
                        <span class="badge text-bg-warning">قيد المراجعة</span>
                        @elseif ($account->verification_state === 'unverified')
                        <span class="badge text-bg-secondary">غير موثق</span>
                        @else
                        <span class="text-muted small">لا ينطبق</span>
                        @endif
                    </td>
                    <td>{{ optional($account->created_at)->format('Y-m-d H:i') }}</td>
                    <td>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('admin.auth-verification.documents.show', ['type' => $account->type, 'id' => $account->id]) }}"
                                class="btn btn-sm btn-outline-primary">
                                عرض
                            </a>
                            @if ($account->type === 'agent' && $account->verification_state === 'verified' && (int) ($account->supplier_id ?? 0) > 0)
                            <form method="POST"
                                action="{{ route('admin.auth-verification.suppliers.unverify', (int) $account->supplier_id) }}"
                                onsubmit="return confirm('تأكيد إلغاء توثيق هذا الوكيل؟');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-danger">إلغاء التوثيق</button>
                            </form>
                            @endif

                            @if (in_array($account->type, ['commercial_store', 'workshop', 'wholesale_trader'], true) && (int) ($account->customer_id ?? 0) > 0)
                            @if ($account->verification_state === 'verified')
                            <form method="POST"
                                action="{{ route('admin.auth-verification.customers.unverify', (int) $account->customer_id) }}"
                                onsubmit="return confirm('تأكيد إلغاء توثيق هذا الحساب؟');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-danger">إلغاء التوثيق</button>
                            </form>
                            @else
                            <form method="POST"
                                action="{{ route('admin.auth-verification.customers.verify', (int) $account->customer_id) }}"
                                onsubmit="return confirm('قبول توثيق هذا الحساب؟');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-success">قبول التوثيق</button>
                            </form>
                            @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">لا توجد حسابات مطابقة للفلاتر</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $accounts->links() }}

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white fw-semibold">طلبات توثيق الوكلاء</div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white mb-0">
            <thead class="table-light">
                <tr>
                    <th>المعرف</th>
                    <th>اسم المالك</th>
                    <th>الاسم التجاري</th>
                    <th>الهاتف</th>
                    <th>تاريخ الطلب</th>
                    <th>الإجراء</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pendingSuppliers as $supplier)
                <tr>
                    <td>{{ $supplier->id }}</td>
                    <td>{{ $supplier->owner_name }}</td>
                    <td>{{ $supplier->business_name }}</td>
                    <td dir="ltr">{{ $supplier->phone }}</td>
                    <td>{{ optional($supplier->verification_requested_at)->format('Y-m-d H:i') }}</td>
                    <td>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('admin.suppliers.show', $supplier) }}"
                                class="btn btn-sm btn-outline-dark">عرض</a>
                            <form action="{{ route('admin.auth-verification.suppliers.verify', $supplier) }}"
                                method="POST" onsubmit="return confirm('قبول طلب توثيق هذا الوكيل؟');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-success">قبول التوثيق</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">لا توجد طلبات توثيق معلقة حاليًا</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    @php
    $fieldLabels = [
    'owner_name' => 'اسم المالك',
    'email' => 'البريد الإلكتروني',
    'phone' => 'رقم الهاتف',
    'whatsapp' => 'واتساب',
    'national_id_number' => 'رقم البطاقة الشخصية',
    'national_id_image' => 'صورة البطاقة الشخصية',
    'agent_image' => 'صورة الوكيل',
    'business_name' => 'الاسم التجاري',
    'logo' => 'الشعار',
    'gps_location' => 'الموقع (GPS)',
    'address' => 'العنوان',
    'commercial_reg_number' => 'رقم السجل التجاري',
    'commercial_reg_image' => 'صورة السجل التجاري',
    'license_number' => 'رقم الرخصة',
    'license_image' => 'صورة الرخصة',
    ];
    $imageFieldKeys = [
    'logo',
    'agent_image',
    'national_id_image',
    'commercial_reg_image',
    'license_image',
    ];
    @endphp
    <div class="card-header bg-white fw-semibold">طلبات تعديل الحقول من الوكلاء</div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>الوكيل</th>
                    <th>الحقل</th>
                    <th>القيمة المطلوبة</th>
                    <th>ملاحظة</th>
                    <th>مستند</th>
                    <th>التاريخ</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pendingSupplierFieldChangeRequests as $requestItem)
                @php $supplier = $requestItem->supplier; @endphp
                <tr>
                    <td>{{ $requestItem->id }}</td>
                    <td>
                        @if ($supplier)
                        <div class="fw-semibold">{{ $supplier->business_name }}</div>
                        <div class="small text-muted">{{ $supplier->owner_name }} - {{ $supplier->phone }}</div>
                        @else
                        <span class="text-muted">وكيل غير موجود</span>
                        @endif
                    </td>
                    <td>{{ $fieldLabels[$requestItem->field_key] ?? $requestItem->field_key }}</td>
                    <td>
                        @if (in_array($requestItem->field_key, $imageFieldKeys, true) && !empty($requestItem->requested_value))
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ asset('storage/' . $requestItem->requested_value) }}" alt="الصورة المطلوبة" class="rounded border" style="width: 70px; height: 50px; object-fit: cover;">
                            <a href="{{ asset('storage/' . $requestItem->requested_value) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary">عرض</a>
                        </div>
                        @else
                        {{ $requestItem->requested_value }}
                        @endif
                    </td>
                    <td>{{ $requestItem->note ?: '-' }}</td>
                    <td>
                        @if ($requestItem->document_path)
                        <a href="{{ asset('storage/' . $requestItem->document_path) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary">عرض</a>
                        @else
                        -
                        @endif
                    </td>
                    <td>{{ $requestItem->created_at?->format('Y-m-d H:i') }}</td>
                    <td>
                        @if ($supplier)
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="{{ route('admin.suppliers.show', $supplier) }}" class="btn btn-sm btn-outline-dark">عرض الوكيل</a>
                            <form action="{{ route('admin.suppliers.field-change-requests.approve', [$supplier, $requestItem]) }}" method="POST" onsubmit="return confirm('تأكيد قبول طلب تعديل الحقل؟');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-success">قبول</button>
                            </form>
                            <form action="{{ route('admin.suppliers.field-change-requests.reject', [$supplier, $requestItem]) }}" method="POST" onsubmit="return confirm('تأكيد رفض طلب تعديل الحقل؟');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-danger">رفض</button>
                            </form>
                        </div>
                        @else
                        -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">لا توجد طلبات تعديل حقول معلقة حاليًا</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection