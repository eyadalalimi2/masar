@extends('admin.layout.app')

@section('content')
    <h1 class="h4 fw-bold mb-4">تعديل بيانات الوكيل</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="d-flex align-items-center gap-2 mb-3">
        @if ($supplier->is_verified)
            <span class="badge text-bg-success">موثّق</span>
            <span class="text-muted small">{{ $supplier->verified_at?->format('Y-m-d H:i') }}</span>
        @elseif ($supplier->has_verification_request)
            <form action="{{ route('admin.suppliers.verify', $supplier) }}" method="POST"
                onsubmit="return confirm('قبول طلب توثيق الوكيل؟ بعد القبول لن يستطيع الوكيل تعديل بياناته.');">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-sm btn-outline-success">قبول التوثيق</button>
            </form>
            <span class="badge text-bg-warning">طلب قيد المراجعة</span>
            <span class="text-muted small">{{ $supplier->verification_requested_at?->format('Y-m-d H:i') }}</span>
        @else
            <span class="badge text-bg-secondary">لا يوجد طلب توثيق</span>
        @endif
    </div>

    <form action="{{ route('admin.suppliers.update', $supplier) }}" method="POST" enctype="multipart/form-data"
        class="card border-0 shadow-sm">
        @csrf
        @method('PUT')

        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-12">
                    <h2 class="h6 fw-bold mb-2">بيانات الوكيل</h2>
                    <hr class="mt-0 mb-2">
                </div>

                <div class="col-md-4">
                    <label class="form-label">اسم المالك</label>
                    <input type="text" name="owner_name" class="form-control"
                        value="{{ old('owner_name', $supplier->owner_name) }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $supplier->email) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $supplier->phone) }}"
                        required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">واتساب</label>
                    <input type="text" name="whatsapp" class="form-control"
                        value="{{ old('whatsapp', $supplier->whatsapp) }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">رقم البطاقة الشخصية</label>
                    <input type="text" name="national_id_number" class="form-control"
                        value="{{ old('national_id_number', $supplier->national_id_number) }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">صورة البطاقة الشخصية</label>
                    <input type="file" name="national_id_image" class="form-control" accept="image/*">
                    @if ($supplier->national_id_image)
                        <img src="{{ asset('storage/' . $supplier->national_id_image) }}" alt="صورة البطاقة الشخصية"
                            class="mt-2 rounded border" style="width: 120px; height: 80px; object-fit: cover;">
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="form-label">صورة الوكيل</label>
                    <input type="file" name="agent_image" class="form-control" accept="image/*">
                    @if ($supplier->agent_image)
                        <img src="{{ asset('storage/' . $supplier->agent_image) }}" alt="صورة الوكيل"
                            class="mt-2 rounded border"
                            style="width: 90px; height: 90px; object-fit: cover; border-radius: 999px !important;">
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="form-label">كلمة المرور (اختياري)</label>
                    <input type="password" name="password" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="active" @selected(old('status', $supplier->status) === 'active')>مفعل</option>
                        <option value="inactive" @selected(old('status', $supplier->status) === 'inactive')>معطل</option>
                    </select>
                </div>

                <div class="col-12 mt-4">
                    <h2 class="h6 fw-bold mb-2">بيانات النشاط التجاري</h2>
                    <hr class="mt-0 mb-2">
                </div>

                <div class="col-md-6">
                    <label class="form-label">الاسم التجاري</label>
                    <input type="text" name="business_name" class="form-control"
                        value="{{ old('business_name', $supplier->business_name) }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">الشعار</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    @if ($supplier->logo)
                        <img src="{{ asset('storage/' . $supplier->logo) }}" alt="شعار الوكيل"
                            class="mt-2 rounded border" style="width: 120px; height: 80px; object-fit: cover;">
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
                    <small class="text-muted">يمكنك تحريك الدبوس أو الضغط على الخريطة لتحديث الإحداثيات والعنوان.</small>
                </div>

                <div class="col-12">
                    <label class="form-label">العنوان</label>
                    <textarea name="address" class="form-control" rows="2" required>{{ old('address', $supplier->address) }}</textarea>
                </div>

                <div class="col-12">
                    @include('agent.partials.working-hours-table', [
                        'workingHours' => $supplier->working_hours,
                    ])
                </div>

                <div class="col-12 mt-4">
                    <h2 class="h6 fw-bold mb-2">بيانات السجل والتراخيص</h2>
                    <hr class="mt-0 mb-2">
                </div>

                <div class="col-md-6">
                    <label class="form-label">رقم السجل التجاري</label>
                    <input type="text" name="commercial_reg_number" class="form-control"
                        value="{{ old('commercial_reg_number', $supplier->commercial_reg_number) }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">صورة السجل التجاري</label>
                    <input type="file" name="commercial_reg_image" class="form-control" accept="image/*">
                    @if ($supplier->commercial_reg_image)
                        <img src="{{ asset('storage/' . $supplier->commercial_reg_image) }}" alt="صورة السجل التجاري"
                            class="mt-2 rounded border" style="width: 120px; height: 80px; object-fit: cover;">
                    @endif
                </div>

                <div class="col-md-6">
                    <label class="form-label">رقم الرخصة</label>
                    <input type="text" name="license_number" class="form-control"
                        value="{{ old('license_number', $supplier->license_number) }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">صورة الرخصة</label>
                    <input type="file" name="license_image" class="form-control" accept="image/*">
                    @if ($supplier->license_image)
                        <img src="{{ asset('storage/' . $supplier->license_image) }}" alt="صورة الرخصة"
                            class="mt-2 rounded border" style="width: 120px; height: 80px; object-fit: cover;">
                    @endif
                </div>
            </div>
        </div>

        <div class="card-footer bg-white d-flex gap-2">
            <button type="submit" class="btn btn-dark">تحديث</button>
            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        </div>
    </form>

    <script>
        (() => {
            const mapsKey = @json(config('services.google_maps.key'));

            const gpsInput = document.querySelector('input[name="gps_location"]');
            const searchInput = document.getElementById('addressSearch');
            const useMyLocationBtn = document.getElementById('useMyLocationBtn');
            const mapContainer = document.getElementById('mapPicker');
            const mapApiHint = document.getElementById('mapApiHint');

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

            window.initSupplierMapPicker = function() {
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
                `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(mapsKey)}&libraries=places&callback=initSupplierMapPicker&language=ar`;
            script.async = true;
            script.defer = true;
            script.onerror = () => {
                disableMapUi('تعذر تحميل مكتبة خرائط Google. تحقق من الاتصال بالإنترنت وإعدادات المفتاح.');
            };
            document.head.appendChild(script);
        })();
    </script>
@endsection
