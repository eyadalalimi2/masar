<input type="hidden" name="type" value="workshop">

<div class="col-md-4">
    <label class="form-label">اسم الورشة</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name ?? '') }}" required>
</div>
<div class="col-md-4">
    <label class="form-label">الهاتف</label>
    <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone ?? '') }}"
        required>
</div>
<div class="col-md-4">
    <label class="form-label">كلمة المرور {{ isset($customer) ? '(اتركها فارغة بدون تغيير)' : '' }}</label>
    <input type="password" name="password" class="form-control" autocomplete="new-password"
        {{ isset($customer) ? '' : 'required' }}>
</div>
<div class="col-md-4">
    <label class="form-label">تأكيد كلمة المرور</label>
    <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password"
        {{ isset($customer) ? '' : 'required' }}>
</div>
<div class="col-md-4">
    <label class="form-label">واتساب</label>
    <input type="text" name="whatsapp" class="form-control" value="{{ old('whatsapp', $customer->whatsapp ?? '') }}">
</div>
<div class="col-md-4">
    <label class="form-label">اسم المالك</label>
    <input type="text" name="owner_name" class="form-control"
        value="{{ old('owner_name', $customer->owner_name ?? '') }}">
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
    <label class="form-label">لوجو الورشة</label>
    <input type="file" name="logo" class="form-control" accept="image/*">
    @if (!empty($customer?->logo_url))
    <a href="{{ $customer->logo_url }}" target="_blank" rel="noopener noreferrer" class="d-inline-block mt-2">
        <img src="{{ $customer->logo_url }}" alt="لوجو الورشة" class="rounded border"
            style="width: 78px; height: 78px; object-fit: cover;">
    </a>
    @endif
</div>
<div class="col-md-4">
    <label class="form-label">رقم البطاقة الشخصية</label>
    <input type="text" name="national_id_number" class="form-control"
        value="{{ old('national_id_number', $customer->national_id_number ?? '') }}">
</div>
<div class="col-md-4">
    <label class="form-label">رقم السجل التجاري</label>
    <input type="text" name="commercial_reg_number" class="form-control"
        value="{{ old('commercial_reg_number', $customer->commercial_reg_number ?? '') }}">
</div>
<div class="col-md-4">
    <label class="form-label">رقم الرخصة</label>
    <input type="text" name="license_number" class="form-control"
        value="{{ old('license_number', $customer->license_number ?? '') }}">
</div>
<div class="col-md-4">
    <label class="form-label">الحالة</label>
    <select name="status" class="form-select" required>
        <option value="active" @selected(old('status', $customer->status ?? 'active') === 'active')>نشط</option>
        <option value="inactive" @selected(old('status', $customer->status ?? 'active') === 'inactive')>غير نشط</option>
    </select>
</div>
<input type="hidden" name="gps_location" value="{{ old('gps_location', $customer->gps_location ?? '') }}">
<div class="col-12">
    <label class="form-label">تحديد الموقع من الخريطة</label>
    <button type="button" id="workshopToggleMapBtn" class="btn btn-sm btn-outline-primary mb-2">تحديد الموقع عبر
        الخرائط</button>

    <div id="workshopMapPanel" style="display: none;">
        <div id="workshopMapApiHint" class="alert alert-warning py-2 mb-2" style="display: none;"></div>
        <input type="text" id="workshopAddressSearch" class="form-control mb-2"
            placeholder="ابحث عن عنوان أو اسم مكان">
        <div class="d-flex justify-content-end mb-2">
            <button type="button" id="workshopUseMyLocationBtn" class="btn btn-sm btn-outline-primary">تحديد
                موقعي</button>
        </div>
        <div class="alert alert-light border py-2 mb-2 small">
            الإحداثيات الحالية: <span
                id="workshopGpsPreview">{{ old('gps_location', $customer->gps_location ?? '') ?: '-' }}</span>
        </div>
        <div id="workshopMapPicker" class="rounded border" style="height: 320px;"></div>
        <small class="text-muted">يتم حفظ الإحداثيات تلقائياً من الخريطة.</small>
    </div>
</div>
<div class="col-12">
    <label class="form-label">العنوان</label>
    <textarea name="address" rows="3" class="form-control" required>{{ old('address', $customer->address ?? '') }}</textarea>
</div>
<div class="col-md-4">
    <label class="form-label">صورة البطاقة الشخصية</label>
    <input type="file" name="national_id_image" class="form-control" accept="image/*">
    @if (!empty($customer?->national_id_image_url))
    <a href="{{ $customer->national_id_image_url }}" target="_blank" rel="noopener noreferrer"
        class="d-inline-block mt-2">
        <img src="{{ $customer->national_id_image_url }}" alt="صورة البطاقة" class="rounded border"
            style="width: 78px; height: 78px; object-fit: cover;">
    </a>
    @endif
</div>
<div class="col-md-4">
    <label class="form-label">صورة السجل التجاري</label>
    <input type="file" name="commercial_reg_image" class="form-control" accept="image/*">
    @if (!empty($customer?->commercial_reg_image_url))
    <a href="{{ $customer->commercial_reg_image_url }}" target="_blank" rel="noopener noreferrer"
        class="d-inline-block mt-2">
        <img src="{{ $customer->commercial_reg_image_url }}" alt="صورة السجل" class="rounded border"
            style="width: 78px; height: 78px; object-fit: cover;">
    </a>
    @endif
</div>
<div class="col-md-4">
    <label class="form-label">صورة الرخصة</label>
    <input type="file" name="license_image" class="form-control" accept="image/*">
    @if (!empty($customer?->license_image_url))
    <a href="{{ $customer->license_image_url }}" target="_blank" rel="noopener noreferrer"
        class="d-inline-block mt-2">
        <img src="{{ $customer->license_image_url }}" alt="صورة الرخصة" class="rounded border"
            style="width: 78px; height: 78px; object-fit: cover;">
    </a>
    @endif
</div>
<div class="col-12">
    <label class="form-label">صور الورشة (متعددة)</label>
    <input type="file" name="store_images[]" class="form-control" accept="image/*" multiple>
    <small class="text-muted">عند رفع صور جديدة سيتم استبدال الصور الحالية.</small>

    @if (!empty($customer?->store_image_urls) && count($customer->store_image_urls) > 0)
    <div class="d-flex flex-wrap gap-2 mt-2">
        @foreach ($customer->store_image_urls as $storeImageUrl)
        <a href="{{ $storeImageUrl }}" target="_blank" rel="noopener noreferrer">
            <img src="{{ $storeImageUrl }}" alt="صورة للورشة" class="rounded border"
                style="width: 78px; height: 78px; object-fit: cover;">
        </a>
        @endforeach
    </div>
    @endif
</div>

@once
@push('scripts')
<script>
    (() => {
        const mapsKey = @json(config('services.google_maps.key'));
        const gpsInput = document.querySelector('input[name="gps_location"]');
        const gpsPreview = document.getElementById('workshopGpsPreview');
        const addressInput = document.querySelector('textarea[name="address"]');
        const toggleMapBtn = document.getElementById('workshopToggleMapBtn');
        const mapPanel = document.getElementById('workshopMapPanel');
        const searchInput = document.getElementById('workshopAddressSearch');
        const useMyLocationBtn = document.getElementById('workshopUseMyLocationBtn');
        const mapContainer = document.getElementById('workshopMapPicker');
        const mapApiHint = document.getElementById('workshopMapApiHint');
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

        window.initWorkshopMapPicker = function() {
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
                `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(mapsKey)}&libraries=places&callback=initWorkshopMapPicker&language=ar`;
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
    })
    ();
</script>
@endpush
@endonce