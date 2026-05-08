@extends('agent.layout.app')

@section('title', 'الانتشار | لوحة الوكيل')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">الانتشار</h1>
        <p class="text-muted mb-0">خريطة أماكن توفر منتجاتك لدى الفروع والمحلات التجارية والورش وتجار الجملة.</p>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">إجمالي النقاط</div>
                <div class="fs-4 fw-bold">{{ $summary['all_points'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">الفروع</div>
                <div class="fs-4 fw-bold">{{ $summary['branches'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">المحلات التجارية</div>
                <div class="fs-4 fw-bold">{{ $summary['commercial_stores'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">الورش</div>
                <div class="fs-4 fw-bold">{{ $summary['workshops'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">تجار الجملة</div>
                <div class="fs-4 fw-bold">{{ $summary['wholesale_traders'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('agent.spread.index') }}" class="row g-2 align-items-end mb-2">
            <div class="col-md-5">
                <label for="productQuery" class="form-label mb-1">بحث باسم الصنف أو رقم الصنف</label>
                <input id="productQuery" type="text" name="product_query" class="form-control"
                    value="{{ $productQuery ?? '' }}" placeholder="مثال: فلتر زيت أو 125">
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-dark">بحث الانتشار</button>
            </div>
            <div class="col-md-2 d-grid">
                <a href="{{ route('agent.spread.index') }}" class="btn btn-outline-secondary">إعادة ضبط</a>
            </div>
            <div class="col-md-3">
                @if (($productQuery ?? '') !== '')
                <div class="small text-muted">نتائج الصنف: {{ $matchedProductsCount ?? 0 }} مطابق</div>
                @else
                <div class="small text-muted">بدون بحث: يتم عرض كامل النقاط</div>
                @endif
            </div>
        </form>

        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="pointTypeFilter" class="form-label mb-1">نوع النقطة</label>
                <select id="pointTypeFilter" class="form-select">
                    <option value="all">الكل</option>
                    <option value="branch">الفروع</option>
                    <option value="commercial_store">المحلات التجارية</option>
                    <option value="workshop">الورش</option>
                    <option value="wholesale_trader">تجار الجملة</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="nameFilter" class="form-label mb-1">بحث بالاسم</label>
                <input id="nameFilter" type="text" class="form-control" placeholder="اسم الفرع أو المحل أو الورشة">
            </div>
            <div class="col-md-4 d-grid">
                <button id="resetFiltersBtn" class="btn btn-outline-secondary">إعادة ضبط الفلاتر</button>
            </div>
        </div>
    </div>
</div>

@if ($mapsApiKey === '')
<div class="alert alert-warning border-0 shadow-sm">
    لم يتم ضبط مفتاح Google Maps API. أضف GOOGLE_MAPS_API_KEY في ملف البيئة لاستخدام الخريطة.
</div>
@endif

@if (($productQuery ?? '') !== '' && (int) ($matchedProductsCount ?? 0) === 0)
<div class="alert alert-danger border-0 shadow-sm">
    لم يتم العثور على صنف مطابق للاسم أو الرقم المدخل.
</div>
@elseif (($productQuery ?? '') !== '' && (int) ($summary['all_points'] ?? 0) === 0)
<div class="alert alert-info border-0 shadow-sm">
    تم العثور على الصنف، لكن لا توجد نقاط انتشار حالية له في الفروع أو المحلات أو الورش أو تجار الجملة.
</div>
@endif

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-0">
        <div id="spreadMapData" data-points='@json($markers)' data-center='@json($mapCenter)'
            data-maps-key="{{ $mapsApiKey }}" hidden></div>
        <div id="spreadMapWrap" style="position: relative;">
            <button id="spreadMapFullscreenBtn" type="button" class="btn btn-light btn-sm"
                style="position: absolute; top: 10px; left: 10px; z-index: 10; border: 1px solid #cbd5e1;">
                <i class="bi bi-fullscreen"></i>
                <span>ملء الشاشة</span>
            </button>
            <div id="spreadMap" style="height: 520px; border-radius: 12px; overflow: hidden;"></div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0" id="spreadTable">
            <thead class="table-light">
                <tr>
                    <th>النوع</th>
                    <th>الاسم</th>
                    <th>العنوان</th>
                    <th>عدد المنتجات</th>
                    <th>مخزون الورشة (صافي)</th>
                    <th>الموقع</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($markers as $point)
                <tr data-type="{{ $point['type'] }}" data-id="{{ $point['id'] }}" data-name="{{ $point['name'] }}">
                    <td>{{ $point['type_label'] }}</td>
                    <td>{{ $point['name'] }}</td>
                    <td>{{ $point['address'] !== '' ? $point['address'] : '-' }}</td>
                    <td>{{ $point['products_count'] }}</td>
                    <td>
                        @if ($point['type'] === 'workshop')
                        {{ number_format((float) ($point['stock_quantity'] ?? 0), 3) }}
                        @else
                        -
                        @endif
                    </td>
                    <td>{{ $point['lat'] }}, {{ $point['lng'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">لا توجد نقاط انتشار مطابقة للشروط.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const mapDataNode = document.getElementById('spreadMapData');
        if (!mapDataNode) {
            return;
        }

        const points = JSON.parse(mapDataNode.dataset.points || '[]');
        const mapsKey = mapDataNode.dataset.mapsKey || '';
        const initialCenter = JSON.parse(mapDataNode.dataset.center || '{"lat":33.3152,"lng":44.3661}');
        const mapWrap = document.getElementById('spreadMapWrap');
        const fullscreenBtn = document.getElementById('spreadMapFullscreenBtn');
        const typeFilter = document.getElementById('pointTypeFilter');
        const nameFilter = document.getElementById('nameFilter');
        const resetFiltersBtn = document.getElementById('resetFiltersBtn');
        const table = document.getElementById('spreadTable');

        let map = null;
        let mapMarkers = [];

        function normalizeText(value) {
            return String(value || '').trim().toLowerCase();
        }

        function getIconByType(type) {
            if (type === 'branch') {
                return 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png';
            }
            if (type === 'commercial_store') {
                return 'https://maps.google.com/mapfiles/ms/icons/green-dot.png';
            }

            if (type === 'wholesale_trader') {
                return 'https://maps.google.com/mapfiles/ms/icons/purple-dot.png';
            }

            return 'https://maps.google.com/mapfiles/ms/icons/orange-dot.png';
        }

        function filteredPoints() {
            const selectedType = typeFilter ? typeFilter.value : 'all';
            const query = normalizeText(nameFilter ? nameFilter.value : '');

            return points.filter((point) => {
                const typeMatches = selectedType === 'all' || point.type === selectedType;
                const nameMatches = query === '' || normalizeText(point.name).includes(query);
                return typeMatches && nameMatches;
            });
        }

        function updateTable(visiblePoints) {
            if (!table) {
                return;
            }

            const rows = Array.from(table.querySelectorAll('tbody tr'));
            rows.forEach((row) => {
                const type = row.getAttribute('data-type') || '';
                const id = Number(row.getAttribute('data-id') || 0);
                const visible = visiblePoints.some((point) => point.type === type && Number(point.id) === id);
                row.style.display = visible ? '' : 'none';
            });
        }

        function clearMarkers() {
            mapMarkers.forEach((marker) => marker.setMap(null));
            mapMarkers = [];
        }

        function drawMarkers(visiblePoints) {
            if (!map) {
                return;
            }

            clearMarkers();

            const bounds = new google.maps.LatLngBounds();

            visiblePoints.forEach((point) => {
                const marker = new google.maps.Marker({
                    map,
                    position: {
                        lat: Number(point.lat),
                        lng: Number(point.lng),
                    },
                    icon: {
                        url: getIconByType(point.type),
                    },
                    title: point.name,
                });

                const info = new google.maps.InfoWindow({
                    content: `
                            <div style="min-width: 200px; direction: rtl; text-align: right;">
                                <div style="font-weight: 700; margin-bottom: 6px;">${point.name}</div>
                                <div style="font-size: 12px; color: #475569; margin-bottom: 4px;">${point.type_label}</div>
                                <div style="font-size: 12px; color: #0f172a;">عدد المنتجات: ${point.products_count}</div>
                                ${point.type === 'workshop' ? `<div style="font-size: 12px; color: #0f172a;">المخزون الصافي: ${Number(point.stock_quantity || 0).toFixed(3)}</div>` : ''}
                                ${point.type === 'workshop' ? `<div style="font-size: 12px; color: #334155;">المستلم: ${Number(point.received_quantity || 0).toFixed(3)}</div>` : ''}
                                ${point.type === 'workshop' ? `<div style="font-size: 12px; color: #334155;">المستخدم: ${Number(point.consumed_quantity || 0).toFixed(3)}</div>` : ''}
                            </div>
                        `,
                });

                marker.addListener('click', () => info.open({
                    anchor: marker,
                    map,
                }));

                mapMarkers.push(marker);
                bounds.extend(marker.getPosition());
            });

            if (visiblePoints.length === 1) {
                map.setCenter(bounds.getCenter());
                map.setZoom(13);
            } else if (visiblePoints.length > 1) {
                map.fitBounds(bounds, 60);
            } else {
                map.setCenter(initialCenter);
                map.setZoom(11);
            }
        }

        function applyFilters() {
            const visiblePoints = filteredPoints();
            updateTable(visiblePoints);
            drawMarkers(visiblePoints);
        }

        function setupInteractions() {
            if (typeFilter) {
                typeFilter.addEventListener('change', applyFilters);
            }

            if (nameFilter) {
                nameFilter.addEventListener('input', applyFilters);
            }

            if (resetFiltersBtn) {
                resetFiltersBtn.addEventListener('click', () => {
                    if (typeFilter) {
                        typeFilter.value = 'all';
                    }

                    if (nameFilter) {
                        nameFilter.value = '';
                    }

                    applyFilters();
                });
            }

            if (fullscreenBtn && mapWrap) {
                fullscreenBtn.addEventListener('click', async () => {
                    try {
                        if (document.fullscreenElement) {
                            await document.exitFullscreen();
                        } else {
                            await mapWrap.requestFullscreen();
                        }
                    } catch (error) {
                        console.error('Fullscreen toggle failed', error);
                    }
                });
            }

            document.addEventListener('fullscreenchange', () => {
                if (fullscreenBtn) {
                    const inFullscreen = document.fullscreenElement === mapWrap;
                    fullscreenBtn.innerHTML = inFullscreen ?
                        '<i class="bi bi-fullscreen-exit"></i><span>خروج</span>' :
                        '<i class="bi bi-fullscreen"></i><span>ملء الشاشة</span>';
                }

                if (map) {
                    setTimeout(() => {
                        google.maps.event.trigger(map, 'resize');
                        applyFilters();
                    }, 120);
                }
            });
        }

        function initSpreadMap() {
            const mapContainer = document.getElementById('spreadMap');
            if (!mapContainer) {
                return;
            }

            map = new google.maps.Map(mapContainer, {
                center: initialCenter,
                zoom: 11,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
            });

            applyFilters();
        }

        setupInteractions();

        if (!mapsKey) {
            return;
        }

        window.initAgentSpreadMap = initSpreadMap;

        const script = document.createElement('script');
        script.src =
            `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(mapsKey)}&callback=initAgentSpreadMap&language=ar`;
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    })();
</script>
@endpush