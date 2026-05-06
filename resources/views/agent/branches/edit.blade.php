@extends('agent.layout.app')

@section('content')
    <h1 class="h4 fw-bold mb-4">تعديل الفرع</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('agent.branches.update', $branch->id) }}" method="POST" class="row g-3">
        @csrf
        @method('PUT')

        <input type="hidden" name="supplier_id" value="{{ auth()->user()->supplier->id }}">

        <div class="col-12">
            <label class="form-label">بيانات نشاطك التجاري</label>
            <div class="border rounded p-2 d-flex align-items-center gap-2">
                @if (auth()->user()->supplier?->logo_url)
                    <img src="{{ auth()->user()->supplier->logo_url }}" alt="لوجو الوكيل"
                        style="width:52px;height:52px;object-fit:cover;border-radius:10px;border:1px solid #e5e7eb;">
                @endif
                <div>
                    <div class="small text-muted">الاسم التجاري</div>
                    <div class="fw-semibold">{{ auth()->user()->supplier?->business_name ?: '-' }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <label class="form-label">اسم الفرع</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $branch->name) }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">رقم الهاتف</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $branch->phone) }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">كلمة مرور مدير الفرع</label>
            <input type="password" name="branch_manager_password" class="form-control"
                placeholder="اتركه فارغًا إذا لا تريد التغيير">
        </div>

        <div class="col-md-6">
            <label class="form-label">الموقع</label>
            <input type="text" name="gps_location" class="form-control"
                value="{{ old('gps_location', $branch->gps_location) }}">
        </div>

        <div class="col-12">
            <label class="form-label">تحديد الموقع من الخريطة</label>
            <div id="mapApiHint" class="alert alert-warning py-2 mb-2" style="display: none;">
                لم يتم ضبط مفتاح Google Maps API. أضف GOOGLE_MAPS_API_KEY في ملف البيئة لاستخدام الخريطة.
            </div>
            <input type="text" id="addressSearch" class="form-control mb-2" placeholder="ابحث عن عنوان أو اسم مكان">
            <div class="d-flex justify-content-end mb-2">
                <button type="button" id="useMyLocationBtn" class="btn btn-sm btn-outline-primary">تحديد موقعي</button>
            </div>
            <div id="mapPicker" class="rounded border" style="height: 320px;"></div>
            <small class="text-muted">يمكنك تحريك الدبوس أو الضغط على الخريطة لتحديث الإحداثيات والعنوان.</small>
        </div>

        <div class="col-md-6">
            <label class="form-label">الحالة</label>
            <select name="status" class="form-select" required>
                <option value="active" @selected(old('status', $branch->status) === 'active')>مفعل</option>
                <option value="inactive" @selected(old('status', $branch->status) === 'inactive')>معطل</option>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">العنوان</label>
            <textarea name="address" class="form-control" rows="3" required>{{ old('address', $branch->address) }}</textarea>
        </div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-dark">تحديث</button>
            <a href="{{ route('agent.branches.index') }}" class="btn btn-outline-secondary">إلغاء</a>
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

            window.initAgentBranchEditMapPicker = function() {
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
                `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(mapsKey)}&libraries=places&callback=initAgentBranchEditMapPicker&language=ar`;
            script.async = true;
            script.defer = true;
            script.onerror = () => {
                disableMapUi('تعذر تحميل مكتبة خرائط Google. تحقق من الاتصال بالإنترنت وإعدادات المفتاح.');
            };
            document.head.appendChild(script);
        })();
    </script>
@endsection
