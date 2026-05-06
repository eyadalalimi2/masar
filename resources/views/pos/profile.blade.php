@extends('pos.layout.app')

@section('content')
    <div class="container-fluid py-2">
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

        <form method="POST" action="{{ route('pos.profile.update') }}" enctype="multipart/form-data"
            class="card border-0 shadow-sm">
            @csrf
            @method('PUT')

            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label">اسم المحل التجاري</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $pos->name) }}"
                        required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $pos->phone) }}"
                        required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">كلمة المرور الجديدة (اختياري)</label>
                    <input type="password" name="password" class="form-control" autocomplete="new-password"
                        placeholder="اتركها فارغة إذا لا تريد التغيير">
                </div>

                <div class="col-md-4">
                    <label class="form-label">واتساب</label>
                    <input type="text" name="whatsapp" class="form-control"
                        value="{{ old('whatsapp', $pos->whatsapp) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">اسم المالك</label>
                    <input type="text" name="owner_name" class="form-control"
                        value="{{ old('owner_name', $pos->owner_name) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">صورة المالك</label>
                    <input type="file" name="owner_image" class="form-control" accept="image/*">
                    @if (!empty($customer?->owner_image_url))
                        <a href="{{ $customer->owner_image_url }}" target="_blank" rel="noopener noreferrer"
                            class="d-inline-block mt-2">
                            <img src="{{ $customer->owner_image_url }}" alt="صورة المالك" class="rounded border"
                                style="width: 78px; height: 78px; object-fit: cover;">
                        </a>
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="form-label">لوجو المحل التجاري</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    @if (!empty($customer?->logo_url))
                        <a href="{{ $customer->logo_url }}" target="_blank" rel="noopener noreferrer"
                            class="d-inline-block mt-2">
                            <img src="{{ $customer->logo_url }}" alt="لوجو المحل" class="rounded border"
                                style="width: 78px; height: 78px; object-fit: cover;">
                        </a>
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="form-label">الحالة</label>
                    <input type="text" class="form-control" value="{{ $pos->status === 'active' ? 'نشط' : 'غير نشط' }}"
                        disabled>
                </div>

                <div class="col-md-4">
                    <label class="form-label">رقم البطاقة الشخصية</label>
                    <input type="text" name="national_id_number" class="form-control"
                        value="{{ old('national_id_number', $pos->national_id_number) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">رقم السجل التجاري</label>
                    <input type="text" name="commercial_reg_number" class="form-control"
                        value="{{ old('commercial_reg_number', $pos->commercial_reg_number) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">رقم الرخصة</label>
                    <input type="text" name="license_number" class="form-control"
                        value="{{ old('license_number', $pos->license_number) }}">
                </div>

                <input type="hidden" name="gps_location" value="{{ old('gps_location', $pos->gps_location) }}">
                <div class="col-12">
                    <label class="form-label">تحديد الموقع من الخريطة</label>
                    <button type="button" id="posToggleMapBtn" class="btn btn-sm btn-outline-primary mb-2">تحديد الموقع من
                        الخريطة</button>

                    <div id="posMapPanel" style="display: none;">
                        <div id="posMapApiHint" class="alert alert-warning py-2 mb-2" style="display: none;"></div>
                        <input type="text" id="posAddressSearch" class="form-control mb-2"
                            placeholder="ابحث عن عنوان أو اسم مكان">
                        <div class="d-flex justify-content-end mb-2">
                            <button type="button" id="posUseMyLocationBtn" class="btn btn-sm btn-outline-primary">تحديد
                                موقعي</button>
                        </div>
                        <div class="alert alert-light border py-2 mb-2 small">
                            الإحداثيات الحالية: <span
                                id="posGpsPreview">{{ old('gps_location', $pos->gps_location) ?: '-' }}</span>
                        </div>
                        <div id="posMapPicker" class="rounded border" style="height: 320px;"></div>
                        <small class="text-muted">يتم حفظ الإحداثيات تلقائياً من الخريطة.</small>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">العنوان</label>
                    <textarea name="address" rows="3" class="form-control" required>{{ old('address', $pos->address) }}</textarea>
                </div>

                <div class="col-md-4">
                    <label class="form-label">صورة البطاقة الشخصية</label>
                    <input type="file" name="national_id_image" class="form-control" accept="image/*">
                    @if (!empty($pos->national_id_image_url))
                        <a href="{{ $pos->national_id_image_url }}" target="_blank" rel="noopener noreferrer"
                            class="d-inline-block mt-2">
                            <img src="{{ $pos->national_id_image_url }}" alt="صورة البطاقة" class="rounded border"
                                style="width: 78px; height: 78px; object-fit: cover;">
                        </a>
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="form-label">صورة السجل التجاري</label>
                    <input type="file" name="commercial_reg_image" class="form-control" accept="image/*">
                    @if (!empty($pos->commercial_reg_image_url))
                        <a href="{{ $pos->commercial_reg_image_url }}" target="_blank" rel="noopener noreferrer"
                            class="d-inline-block mt-2">
                            <img src="{{ $pos->commercial_reg_image_url }}" alt="صورة السجل" class="rounded border"
                                style="width: 78px; height: 78px; object-fit: cover;">
                        </a>
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="form-label">صورة الرخصة</label>
                    <input type="file" name="license_image" class="form-control" accept="image/*">
                    @if (!empty($pos->license_image_url))
                        <a href="{{ $pos->license_image_url }}" target="_blank" rel="noopener noreferrer"
                            class="d-inline-block mt-2">
                            <img src="{{ $pos->license_image_url }}" alt="صورة الرخصة" class="rounded border"
                                style="width: 78px; height: 78px; object-fit: cover;">
                        </a>
                    @endif
                </div>

                <div class="col-12">
                    <label class="form-label">صور المحل التجاري (متعددة)</label>
                    <input type="file" name="store_images[]" class="form-control" accept="image/*" multiple>
                    <small class="text-muted">عند رفع صور جديدة سيتم استبدال الصور الحالية.</small>

                    @if (!empty($customer?->store_image_urls) && count($customer->store_image_urls) > 0)
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @foreach ($customer->store_image_urls as $storeImageUrl)
                                <a href="{{ $storeImageUrl }}" target="_blank" rel="noopener noreferrer">
                                    <img src="{{ $storeImageUrl }}" alt="صورة للمحل" class="rounded border"
                                        style="width: 78px; height: 78px; object-fit: cover;">
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="card-footer bg-white d-flex gap-2">
                <button type="submit" class="btn btn-dark">حفظ التعديلات</button>
                <a href="{{ route('pos.dashboard') }}" class="btn btn-outline-secondary">إلغاء</a>
            </div>
        </form>

        <form method="POST" action="{{ route('pos.profile.update-working-hours') }}"
            class="card border-0 shadow-sm mt-3">
            @csrf
            @method('PUT')
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">أوقات الدوام</h2>
                @include('agent.partials.working-hours-table', [
                    'workingHours' => old('working_hours', $customer->working_hours_schedule),
                ])
            </div>
            <div class="card-footer bg-white">
                <button class="btn btn-dark" type="submit">حفظ أوقات الدوام</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const mapsKey = @json(config('services.google_maps.key'));
            const gpsInput = document.querySelector('input[name="gps_location"]');
            const gpsPreview = document.getElementById('posGpsPreview');
            const addressInput = document.querySelector('textarea[name="address"]');
            const toggleMapBtn = document.getElementById('posToggleMapBtn');
            const mapPanel = document.getElementById('posMapPanel');
            const searchInput = document.getElementById('posAddressSearch');
            const useMyLocationBtn = document.getElementById('posUseMyLocationBtn');
            const mapContainer = document.getElementById('posMapPicker');
            const mapApiHint = document.getElementById('posMapApiHint');
            let mapScriptRequested = false;

            if (!gpsInput || !toggleMapBtn || !mapPanel || !searchInput || !useMyLocationBtn || !mapContainer || !
                mapApiHint) {
                return;
            }

            function showMapPanel() {
                mapPanel.style.display = '';
            }

            function disableMapUi(message) {
                showMapPanel();
                mapApiHint.textContent = message;
                mapApiHint.style.display = '';
                searchInput.disabled = true;
                useMyLocationBtn.disabled = true;
                mapContainer.style.display = 'none';
            }

            if (!mapsKey) {
                toggleMapBtn.addEventListener('click', () => {
                    showMapPanel();
                    disableMapUi(
                        'لم يتم ضبط مفتاح Google Maps API. أضف GOOGLE_MAPS_API_KEY في ملف البيئة لاستخدام الخريطة.'
                    );
                });
                return;
            }

            window.gm_authFailure = () => {
                disableMapUi('تعذر تحميل خرائط Google بسبب مشكلة في المفتاح أو القيود.');
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

            window.initPosProfileMapPicker = function() {
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

                    if (gpsPreview) {
                        gpsPreview.textContent = gpsInput.value;
                    }

                    if (!shouldGeocode) {
                        return;
                    }

                    geocoder.geocode({
                        location: latLng
                    }, (results, status) => {
                        if (status === 'OK' && results && results[0] && results[0].formatted_address &&
                            addressInput) {
                            addressInput.value = results[0].formatted_address;
                        }
                    });
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

                useMyLocationBtn.addEventListener('click', useCurrentLocation);

                gpsInput.value = formatLatLng(new google.maps.LatLng(initial.lat, initial.lng));
                if (gpsPreview) {
                    gpsPreview.textContent = gpsInput.value;
                }
            };

            function loadMapScript() {
                if (mapScriptRequested) {
                    return;
                }

                mapScriptRequested = true;

                const script = document.createElement('script');
                script.src =
                    `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(mapsKey)}&libraries=places&callback=initPosProfileMapPicker&language=ar`;
                script.async = true;
                script.defer = true;
                script.onerror = () => {
                    disableMapUi('تعذر تحميل مكتبة خرائط Google. تحقق من الاتصال بالإنترنت وإعدادات المفتاح.');
                };
                document.head.appendChild(script);
            }

            toggleMapBtn.addEventListener('click', () => {
                showMapPanel();
                toggleMapBtn.style.display = 'none';
                loadMapScript();
            });
        })();
    </script>
@endpush
