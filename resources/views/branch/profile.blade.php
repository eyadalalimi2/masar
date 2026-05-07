@extends('branch.layout.app')

@section('title', 'بروفايل الفرع')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-1">بروفايل مدير الفرع</h1>
            <p class="text-muted mb-0">تحديث بيانات الفرع ومدير الفرع</p>
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

    <form action="{{ route('branch.profile.update') }}" method="POST" enctype="multipart/form-data"
        class="card border-0 shadow-sm">
        @csrf
        @method('PUT')

        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">اسم الفرع</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $branch->name) }}"
                        required>
                </div>

                <div class="col-md-2">
                    <label class="form-label">رقم هاتف الفرع</label>
                    <input type="text" class="form-control" value="{{ $branch->phone }}" disabled>
                    <small class="text-muted">رقم الهاتف غير قابل للتعديل من هذه الصفحة.</small>
                </div>

                <div class="col-md-4">
                    <label class="form-label">اسم مدير الفرع</label>
                    <input type="text" name="branch_manager_name" class="form-control"
                        value="{{ old('branch_manager_name', $branch->branch_manager_name) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">صورة مدير الفرع</label>
                    <input type="file" name="branch_manager_image" class="form-control" accept="image/*">
                    @if ($branch->branch_manager_image)
                    <img src="{{ asset('storage/' . $branch->branch_manager_image) }}" alt="صورة مدير الفرع"
                        class="mt-2 rounded border"
                        style="width: 90px; height: 90px; object-fit: cover; border-radius: 999px !important;">
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="form-label">كلمة مرور مدير الفرع</label>
                    <input type="password" name="branch_manager_password" class="form-control"
                        placeholder="اتركها فارغة إذا لا تريد التغيير">
                </div>

                <div class="col-md-4">
                    <label class="form-label">العنوان</label>
                    <textarea name="address" class="form-control" rows="2" required>{{ old('address', $branch->address) }}</textarea>
                </div>

                <input type="hidden" name="gps_location" value="{{ old('gps_location', $branch->gps_location) }}">

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
                    <div class="alert alert-light border py-2 mb-2 small">
                        الإحداثيات الحالية: <span
                            id="gpsPreview">{{ old('gps_location', $branch->gps_location) ?: '-' }}</span>
                    </div>
                    <div id="mapPicker" class="rounded border" style="height: 320px;"></div>
                    <small class="text-muted">يتم تحديث الإحداثيات تلقائيًا من الخريطة وحفظها عند الضغط على حفظ
                        التعديلات.</small>
                </div>

            </div>
        </div>

        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-dark">حفظ التعديلات</button>
        </div>
    </form>

    <form action="{{ route('branch.profile.update-working-hours') }}" method="POST"
        class="card border-0 shadow-sm mt-3">
        @csrf
        @method('PUT')

        <div class="card-body p-4">
            <h2 class="h6 fw-bold mb-3">أوقات الدوام</h2>
            @include('agent.partials.working-hours-table', [
            'workingHours' => $branch->working_hours_schedule,
            ])
        </div>

        <div class="card-footer bg-white d-flex justify-content-end">
            <button type="submit" class="btn btn-dark">حفظ أوقات الدوام</button>
        </div>
    </form>
</div>

<script>
    (() => {
        const mapsKey = @json(config('services.google_maps.key'));
        const gpsInput = document.querySelector('input[name="gps_location"]');
        const gpsPreview = document.getElementById('gpsPreview');
        const addressInput = document.querySelector('textarea[name="address"]');
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

        window.initBranchProfileMapPicker = function() {
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
                    if (
                        status === 'OK' &&
                        results &&
                        results[0] &&
                        results[0].formatted_address &&
                        addressInput
                    ) {
                        addressInput.value = results[0].formatted_address;
                    }
                });
            }

            gpsInput.value = formatLatLng(new google.maps.LatLng(initial.lat, initial.lng));
            if (gpsPreview) {
                gpsPreview.textContent = gpsInput.value;
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
            `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(mapsKey)}&libraries=places&callback=initBranchProfileMapPicker&language=ar`;
        script.async = true;
        script.defer = true;
        script.onerror = () => {
            disableMapUi('تعذر تحميل مكتبة خرائط Google. تحقق من الاتصال بالإنترنت وإعدادات المفتاح.');
        };
        document.head.appendChild(script);
    })();
</script>
@endsection