@extends('agent.layout.app')

@section('content')
@php
$profileLocked = (bool) $supplier->is_verified;
$fieldLabels = [
'owner_name' => 'اسم المالك',
'branch_manager_name' => 'اسم مدير الفرع',
'email' => 'البريد الإلكتروني',
'phone' => 'رقم الهاتف',
'whatsapp' => 'واتساب',
'national_id_number' => 'رقم البطاقة الشخصية',
'national_id_image' => 'صورة البطاقة الشخصية',
'agent_image' => 'صورة الوكيل',
'business_name' => 'الاسم التجاري',
'logo' => 'الشعار',
'branch_manager_image' => 'صورة مدير الفرع',
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
'branch_manager_image',
'national_id_image',
'commercial_reg_image',
'license_image',
];
@endphp

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">الملف الشخصي للوكيل</h1>
        <p class="text-muted mb-0">إدارة بيانات الحساب والنشاط التجاري وطلبات التعديل</p>
    </div>
    <div>
        @if ($profileLocked)
        <span class="badge text-bg-success px-3 py-2">الحساب موثّق</span>
        @elseif ($supplier->has_verification_request)
        <span class="badge text-bg-warning px-3 py-2">طلب التوثيق قيد المراجعة</span>
        @else
        <span class="badge text-bg-secondary px-3 py-2">غير موثّق</span>
        @endif
    </div>
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

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted mb-1">الحالة</div>
                @if ($supplier->status === 'active')
                <span class="badge text-bg-success">مفعل</span>
                @else
                <span class="badge text-bg-secondary">معطل</span>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted mb-1">حالة التوثيق</div>
                @if ($profileLocked)
                <span class="badge text-bg-success">موثّق</span>
                @elseif ($supplier->has_verification_request)
                <span class="badge text-bg-warning">قيد المراجعة</span>
                @else
                <span class="badge text-bg-secondary">غير موثّق</span>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted mb-1">آخر تحديث</div>
                <div class="fw-semibold">{{ $supplier->updated_at?->format('Y-m-d H:i') }}</div>
            </div>
        </div>
    </div>
</div>

<form action="{{ route('agent.profile.update-security') }}" method="POST" enctype="multipart/form-data"
    class="card border-0 shadow-sm mb-4">
    @csrf
    @method('PATCH')
    <div class="card-header bg-white fw-bold">أمان حساب الوكيل</div>
    <div class="card-body row g-3">
        <div class="col-md-4">
            <label class="form-label">صورة الوكيل الحالية</label>
            <div>
                @if ($supplier->agent_image)
                <label for="securityAgentImageInput" class="d-inline-block" style="cursor: pointer;">
                    <img src="{{ asset('storage/' . $supplier->agent_image) }}" alt="صورة الوكيل"
                        style="width: 120px; height: 120px; object-fit: cover; border: 1px solid #e5e7eb; border-radius: 10px;">
                </label>
                <div class="text-muted small mt-1">اضغط على الصورة لاستبدالها.</div>
                @else
                <div class="text-muted small">لا توجد صورة</div>
                @endif
            </div>
        </div>

        <div class="col-md-8 row g-3">
            <div class="col-12">
                <label class="form-label">تغيير صورة الوكيل (اختياري)</label>
                <input type="file" id="securityAgentImageInput" name="agent_image" class="form-control" accept="image/*">
            </div>

            <div class="col-md-4">
                <label class="form-label">كلمة المرور الجديدة</label>
                <input type="password" name="new_password" class="form-control">
            </div>

            <div class="col-12">
                <small class="text-muted">يمكنك تغيير الصورة أو كلمة المرور الجديدة أو كلاهما معًا.</small>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-end">
        <button type="submit" class="btn btn-dark">حفظ إعدادات الأمان</button>
    </div>
</form>

@if (!$profileLocked)
<div class="d-flex align-items-center gap-2 mb-3">
    @if (! $supplier->has_verification_request)
    <a href="{{ route('agent.profile.verification') }}" class="btn btn-sm btn-outline-success">طلب التوثيق</a>
    @else
    <button type="button" class="btn btn-sm btn-outline-secondary" disabled>طلب التوثيق</button>
    <span class="badge text-bg-warning">طلب التوثيق قيد المراجعة</span>
    <span class="small text-muted">{{ $supplier->verification_requested_at?->format('Y-m-d H:i') }}</span>
    @endif
</div>
@endif

@if (!$profileLocked)
<form action="{{ route('agent.profile.update') }}" method="POST" enctype="multipart/form-data" class="row g-3">
    @csrf
    @method('PUT')

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">بيانات الوكيل</div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label">اسم المالك</label>
                    <input type="text" name="owner_name" class="form-control"
                        value="{{ old('owner_name', $supplier->owner_name) }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email" name="email" class="form-control"
                        value="{{ old('email', $supplier->email) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="text" name="phone" class="form-control"
                        value="{{ old('phone', $supplier->phone) }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">واتساب</label>
                    <input type="text" name="whatsapp" class="form-control"
                        value="{{ old('whatsapp', $supplier->whatsapp) }}" required>
                </div>

                <input type="hidden" name="national_id_number"
                    value="{{ old('national_id_number', $supplier->national_id_number) }}">
                <input type="hidden" name="commercial_reg_number"
                    value="{{ old('commercial_reg_number', $supplier->commercial_reg_number) }}">
                <input type="hidden" name="license_number"
                    value="{{ old('license_number', $supplier->license_number) }}">

                <div class="col-md-4">
                    <label class="form-label">صورة الوكيل</label>
                    <input type="file" id="agentImageInput" name="agent_image"
                        class="form-control {{ $supplier->agent_image ? 'd-none' : '' }}" accept="image/*">
                    @if ($supplier->agent_image)
                    <label for="agentImageInput" class="d-inline-block mt-1" style="cursor: pointer;">
                        <img src="{{ asset('storage/' . $supplier->agent_image) }}" alt="صورة الوكيل"
                            style="width: 120px; height: 120px; object-fit: cover; border: 1px solid #e5e7eb; border-radius: 10px;">
                    </label>
                    <div class="text-muted small">اضغط على الصورة لاستبدالها.</div>
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="form-label">اسم مدير الفرع</label>
                    <input type="text" name="branch_manager_name" class="form-control"
                        value="{{ old('branch_manager_name', $supplier->branch_manager_name) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">صورة مدير الفرع</label>
                    <input type="file" id="branchManagerImageInput" name="branch_manager_image"
                        class="form-control {{ $supplier->branch_manager_image ? 'd-none' : '' }}" accept="image/*">
                    @if ($supplier->branch_manager_image)
                    <label for="branchManagerImageInput" class="d-inline-block mt-1" style="cursor: pointer;">
                        <img src="{{ asset('storage/' . $supplier->branch_manager_image) }}" alt="صورة مدير الفرع"
                            style="width: 120px; height: 120px; object-fit: cover; border: 1px solid #e5e7eb; border-radius: 10px;">
                    </label>
                    <div class="text-muted small">اضغط على الصورة لاستبدالها.</div>
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="form-label">كلمة مرور مدير الفرع (اختياري)</label>
                    <input type="password" name="branch_manager_password" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label">المعاينة الحالية</label>
                    @if ($supplier->agent_image)
                    <img src="{{ asset('storage/' . $supplier->agent_image) }}" alt="صورة الوكيل"
                        style="width: 120px; height: 120px; object-fit: cover; border: 1px solid #e5e7eb; border-radius: 10px;">
                    @else
                    <div class="text-muted small">لا توجد صورة</div>
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="form-label">معاينة صورة مدير الفرع</label>
                    @if ($supplier->branch_manager_image)
                    <img src="{{ asset('storage/' . $supplier->branch_manager_image) }}"
                        alt="صورة مدير الفرع"
                        style="width: 120px; height: 120px; object-fit: cover; border: 1px solid #e5e7eb; border-radius: 10px;">
                    @else
                    <div class="text-muted small">لا توجد صورة</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">بيانات النشاط التجاري</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">الاسم التجاري</label>
                    <input type="text" name="business_name" class="form-control"
                        value="{{ old('business_name', $supplier->business_name) }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">الشعار</label>
                    <input type="file" id="supplierLogoInput" name="logo"
                        class="form-control {{ $supplier->logo ? 'd-none' : '' }}" accept="image/*">
                    @if ($supplier->logo)
                    <label for="supplierLogoInput" class="d-inline-block mt-1" style="cursor: pointer;">
                        <img src="{{ asset('storage/' . $supplier->logo) }}" alt="شعار الوكيل"
                            style="width: 140px; height: 90px; object-fit: cover; border: 1px solid #e5e7eb; border-radius: 10px;">
                    </label>
                    <div class="text-muted small">اضغط على الصورة لاستبدالها.</div>
                    @endif
                </div>

                <div class="col-md-6">
                    <label class="form-label">الموقع (GPS)</label>
                    <input type="text" name="gps_location" class="form-control"
                        value="{{ old('gps_location', $supplier->gps_location) }}" required>
                </div>

                <div class="col-12">
                    <label class="form-label">تحديد الموقع من الخريطة</label>
                    <div id="mapApiHint" class="alert alert-warning py-2 mb-2" style="display: none;">
                        لم يتم ضبط مفتاح Google Maps API. أضف GOOGLE_MAPS_API_KEY في ملف البيئة لاستخدام الخريطة.
                    </div>
                    <input type="text" id="addressSearch" class="form-control mb-2"
                        placeholder="ابحث عن عنوان أو اسم مكان">
                    <div class="d-flex justify-content-end mb-2">
                        <button type="button" id="useMyLocationBtn" class="btn btn-sm btn-outline-primary">تحديد
                            موقعي</button>
                    </div>
                    <div id="mapPicker" class="rounded border" style="height: 320px;"></div>
                    <small class="text-muted">يمكنك تحريك الدبوس أو الضغط على الخريطة لتحديث الإحداثيات
                        والعنوان.</small>
                </div>

                <div class="col-12">
                    <label class="form-label">العنوان</label>
                    <textarea name="address" class="form-control" rows="2" required>{{ old('address', $supplier->address) }}</textarea>
                </div>

                <div class="col-12">
                    @include('agent.partials.working-hours-table', [
                    'workingHours' => $supplier->working_hours_schedule,
                    ])
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end">
        <button type="submit" class="btn btn-dark px-4">حفظ تحديثات الملف</button>
    </div>
</form>
@else
<div class="alert alert-info mb-3">
    تم توثيق بياناتك من الإدارة. يمكنك تعديل أوقات الدوام مباشرة، ولأي حقل آخر استخدم زر طلب تعديل للإدارة.
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold">بيانات الحساب الموثّق</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">اسم المالك</dt>
                    <dd class="col-sm-8">{{ $supplier->owner_name }}</dd>

                    <dt class="col-sm-4 text-muted">البريد الإلكتروني</dt>
                    <dd class="col-sm-8">{{ $supplier->email ?: 'غير محدد' }}</dd>


                    <div class="col-md-6">
                        <label class="form-label">صورة السجل التجاري</label>
                        @if ($supplier->commercial_reg_image)
                        <div class="text-muted small">تم رفع الصورة مسبقا.</div>
                        @else
                        <input type="file" name="commercial_reg_image" class="form-control" accept="image/*">
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">صورة الرخصة</label>
                        @if ($supplier->license_image)
                        <div class="text-muted small">تم رفع الصورة مسبقا.</div>
                        @else
                        <input type="file" name="license_image" class="form-control" accept="image/*">
                        @endif
                    </div>
                    <dt class="col-sm-4 text-muted">رقم الهاتف</dt>
                    <dd class="col-sm-8">{{ $supplier->phone }}</dd>

                    <dt class="col-sm-4 text-muted">اسم مدير الفرع</dt>
                    <dd class="col-sm-8">{{ $supplier->branch_manager_name ?: 'غير محدد' }}</dd>

                    <dt class="col-sm-4 text-muted">واتساب</dt>
                    <dd class="col-sm-8">{{ $supplier->whatsapp }}</dd>

                    <dt class="col-sm-4 text-muted">الاسم التجاري</dt>
                    <dd class="col-sm-8">{{ $supplier->business_name }}</dd>

                    <dt class="col-sm-4 text-muted">العنوان</dt>
                    <dd class="col-sm-8">{{ $supplier->address }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold">طلب تعديل حقل إلى الإدارة</div>
            <div class="card-body">
                <form action="{{ route('agent.profile.request-field-change') }}" method="POST"
                    enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label class="form-label">الحقل</label>
                        <select name="field_key" class="form-select" required>
                            <option value="">اختر الحقل</option>
                            @foreach ($fieldLabels as $fieldKey => $fieldLabel)
                            <option value="{{ $fieldKey }}" @selected(old('field_key')===$fieldKey)>
                                {{ $fieldLabel }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">التعديلات الجديدة المطلوبة</label>
                        <textarea name="requested_value" class="form-control" rows="3" required>{{ old('requested_value') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">الصورة المطلوبة (عند اختيار حقل صورة)</label>
                        <input type="file" name="requested_image" class="form-control" accept="image/*">
                        <small class="text-muted">استخدم هذا الحقل عند طلب تعديل الشعار أو صور الوثائق.</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظة للإدارة (اختياري)</label>
                        <textarea name="note" class="form-control" rows="2">{{ old('note') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">مستند داعم (اختياري)</label>
                        <input type="file" name="document" class="form-control" accept=".pdf,image/*">
                        <small class="text-muted">يمكنك رفع PDF أو صورة بحد أقصى 4MB.</small>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-outline-primary">إرسال طلب تعديل</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<form action="{{ route('agent.profile.update-working-hours') }}" method="POST"
    class="card border-0 shadow-sm mb-3">
    @csrf
    @method('PATCH')
    <div class="card-header bg-white fw-bold">تعديل أوقات الدوام</div>
    <div class="card-body row g-3">
        <div class="col-12">
            @include('agent.partials.working-hours-table', [
            'workingHours' => $supplier->working_hours_schedule,
            ])
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-end">
        <button type="submit" class="btn btn-dark">حفظ أوقات الدوام</button>
    </div>
</form>
@endif

@if ($supplier->fieldChangeRequests->isNotEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold">سجل طلبات تعديل الحقول</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>الحقل</th>
                    <th>القيمة المطلوبة</th>
                    <th>المستند</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($supplier->fieldChangeRequests as $requestItem)
                <tr>
                    <td>{{ $fieldLabels[$requestItem->field_key] ?? $requestItem->field_key }}</td>
                    <td>
                        @if (in_array($requestItem->field_key, $imageFieldKeys, true))
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ asset('storage/' . $requestItem->requested_value) }}"
                                alt="الصورة المطلوبة" class="rounded border"
                                style="width: 70px; height: 50px; object-fit: cover;">
                            <a href="{{ asset('storage/' . $requestItem->requested_value) }}"
                                target="_blank" rel="noopener noreferrer"
                                class="btn btn-sm btn-outline-secondary">عرض</a>
                        </div>
                        @else
                        {{ $requestItem->requested_value }}
                        @endif
                    </td>
                    <td>
                        @if ($requestItem->document_path)
                        <a href="{{ asset('storage/' . $requestItem->document_path) }}" target="_blank"
                            rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary">عرض</a>
                        @else
                        -
                        @endif
                    </td>
                    <td>
                        @if ($requestItem->status === 'approved')
                        <span class="badge text-bg-success">مقبول</span>
                        @elseif ($requestItem->status === 'rejected')
                        <span class="badge text-bg-danger">مرفوض</span>
                        @else
                        <span class="badge text-bg-warning">قيد المراجعة</span>
                        @endif
                    </td>
                    <td>{{ $requestItem->created_at?->format('Y-m-d H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div id="agentProfileConfig" data-google-maps-key="{{ (string) config('services.google_maps.key') }}" hidden></div>

<script>
    (() => {
        const mapsKey = document.getElementById('agentProfileConfig')?.dataset.googleMapsKey || '';
        const gpsInput = document.querySelector('input[name="gps_location"]');
        const searchInput = document.getElementById('addressSearch');
        const useMyLocationBtn = document.getElementById('useMyLocationBtn');
        const mapContainer = document.getElementById('mapPicker');
        const mapApiHint = document.getElementById('mapApiHint');

        if (!gpsInput || !searchInput || !useMyLocationBtn || !mapContainer || !mapApiHint) {
            return;
        }

        function disableMapUi(message) {
            mapApiHint.textContent = message;
            mapApiHint.style.display = '';
            searchInput.disabled = true;
            useMyLocationBtn.disabled = true;
            mapContainer.style.display = 'none';
        }

        if (!mapsKey) {
            disableMapUi(
                'لم يتم ضبط مفتاح Google Maps API. أضف GOOGLE_MAPS_API_KEY في ملف البيئة لاستخدام الخريطة.');
            return;
        }

        window.gm_authFailure = () => {
            disableMapUi(
                'تعذر تحميل خرائط Google بسبب مشكلة في المفتاح أو القيود. يمكنك إدخال الإحداثيات يدويًا.');
        };

        function parseLatLng(value) {
            if (!value || !value.includes(',')) {
                return null;
            }

            const parts = value.split(',').map((item) => parseFloat(item.trim()));
            if (parts.length !== 2 || Number.isNaN(parts[0]) || Number.isNaN(parts[1])) {
                return null;
            }

            return {
                lat: parts[0],
                lng: parts[1],
            };
        }

        function formatLatLng(position) {
            return `${position.lat().toFixed(6)},${position.lng().toFixed(6)}`;
        }

        window.initAgentProfileMapPicker = function() {
            const initial = parseLatLng(gpsInput.value) || {
                lat: 15.369445,
                lng: 44.191006,
            };

            const map = new google.maps.Map(mapContainer, {
                center: initial,
                zoom: 13,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
            });

            const marker = new google.maps.Marker({
                map,
                position: initial,
                draggable: true,
            });

            const geocoder = new google.maps.Geocoder();

            function syncPosition(latLng, shouldGeocode = true) {
                marker.setPosition(latLng);
                map.panTo(latLng);
                gpsInput.value = formatLatLng(latLng);

                if (!shouldGeocode) {
                    return;
                }

                geocoder.geocode({
                    location: latLng
                }, () => {});
            }

            function setMyLocationButtonState(isLoading) {
                useMyLocationBtn.disabled = isLoading;
                useMyLocationBtn.textContent = isLoading ? 'جارٍ تحديد الموقع...' : 'تحديد موقعي';
            }

            function useCurrentLocation() {
                if (!navigator.geolocation) {
                    alert('المتصفح لا يدعم تحديد الموقع الجغرافي.');
                    return;
                }

                setMyLocationButtonState(true);

                navigator.geolocation.getCurrentPosition((position) => {
                    const latLng = new google.maps.LatLng(position.coords.latitude, position.coords
                        .longitude);
                    map.setZoom(16);
                    syncPosition(latLng);
                    setMyLocationButtonState(false);
                }, () => {
                    alert('تعذر تحديد موقعك. تأكد من السماح بالوصول إلى الموقع.');
                    setMyLocationButtonState(false);
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0,
                });
            }

            marker.addListener('dragend', () => {
                syncPosition(marker.getPosition());
            });

            map.addListener('click', (event) => {
                syncPosition(event.latLng);
            });

            function searchByText() {
                const query = searchInput.value.trim();
                if (!query) {
                    return;
                }

                geocoder.geocode({
                    address: query,
                    componentRestrictions: {
                        country: 'YE'
                    },
                    region: 'ye',
                }, (results, status) => {
                    if (status === 'OK' && results && results[0] && results[0].geometry && results[0]
                        .geometry.location) {
                        map.setCenter(results[0].geometry.location);
                        map.setZoom(16);
                        syncPosition(results[0].geometry.location, false);

                    }
                });
            }

            if (google.maps.places && google.maps.places.Autocomplete) {
                const autocomplete = new google.maps.places.Autocomplete(searchInput, {
                    fields: ['formatted_address', 'geometry', 'name'],
                    componentRestrictions: {
                        country: 'ye'
                    },
                });

                autocomplete.addListener('place_changed', () => {
                    const place = autocomplete.getPlace();
                    if (!place.geometry || !place.geometry.location) {
                        searchByText();
                        return;
                    }

                    map.setCenter(place.geometry.location);
                    map.setZoom(16);
                    syncPosition(place.geometry.location, false);

                });
            }

            searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    searchByText();
                }
            });

            searchInput.addEventListener('change', () => {
                searchByText();
            });

            document.addEventListener('mousedown', (event) => {
                if (event.target.closest('.pac-item')) {
                    setTimeout(searchByText, 0);
                }
            }, true);

            searchInput.addEventListener('blur', () => {
                setTimeout(searchByText, 150);
            });

            useMyLocationBtn.addEventListener('click', useCurrentLocation);

            gpsInput.addEventListener('change', () => {
                const latLng = parseLatLng(gpsInput.value);
                if (latLng) {
                    syncPosition(new google.maps.LatLng(latLng.lat, latLng.lng), false);
                }
            });
        };

        const script = document.createElement('script');
        script.src =
            `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(mapsKey)}&libraries=places&callback=initAgentProfileMapPicker&language=ar`;
        script.async = true;
        script.defer = true;
        script.onerror = () => {
            disableMapUi('تعذر تحميل مكتبة خرائط Google. تحقق من الاتصال بالإنترنت وإعدادات المفتاح.');
        };
        document.head.appendChild(script);
    })();
</script>
@endsection