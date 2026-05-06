@extends('consumer.layout.app')

@section('title', 'الرئيسية | المستهلك')

@section('content')
<div class="container-fluid py-2">
    @if (session('status'))
    <div class="alert alert-success rounded-4">{{ session('status') }}</div>
    @endif

    @if (!$hasGeoLocation)
    <div class="alert alert-warning rounded-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            لم يتم تحديد موقعك الجغرافي بعد، لذلك قد لا يظهر ترتيب الأقرب بدقة.
            أضف إحداثيات GPS من صفحة الحساب أو العناوين.
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('consumer.profile.index') }}" class="btn btn-sm btn-outline-dark">تحديث الحساب</a>
            <a href="{{ route('consumer.addresses.index') }}" class="btn btn-sm btn-dark">إدارة العناوين</a>
        </div>
    </div>
    @endif

    <div class="p-4 rounded-4 text-white mb-3" style="background: linear-gradient(135deg, #0f172a 0%, #0f766e 100%);">
        <h1 class="h4 mb-1">مرحبًا {{ $consumer->name }}</h1>
        <p class="mb-0 text-white-50">منتجات، خدمات، ومتاجر قريبة في مكان واحد</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="border rounded-4 bg-white p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 mb-0">تنبيهاتك الذكية</h2>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge text-bg-primary">غير مقروء: {{ (int) $consumerUnreadAlertsCount }}</span>
                        @if ((int) $consumerUnreadAlertsCount > 0)
                        <form method="POST" action="{{ route('consumer.alerts.read-all') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary">تعليم الكل
                                مقروء</button>
                        </form>
                        @endif
                    </div>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($recentConsumerAlerts as $alert)
                    <div class="list-group-item px-0">
                        <div class="fw-semibold">{{ $alert->title }}</div>
                        <div class="small text-muted">{{ $alert->body }}</div>
                        <div class="small text-secondary mt-1">{{ $alert->created_at?->diffForHumans() }}</div>
                    </div>
                    @empty
                    <div class="text-muted small">لا توجد تنبيهات حالية.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="border rounded-4 bg-white p-3 h-100">
                <h2 class="h6 mb-2">توقعات إعادة الطلب</h2>

                <div class="small fw-semibold text-dark mb-2">منتجات متوقعة</div>
                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>آخر طلب (يوم)</th>
                                <th>التكرار</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reorderProductPredictions as $prediction)
                            <tr class="{{ $prediction['should_reorder_soon'] ? 'table-warning' : '' }}">
                                <td>{{ $prediction['product_name'] }}</td>
                                <td>{{ (int) $prediction['days_since_last'] }}</td>
                                <td>{{ (int) $prediction['ordered_count'] }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">لا توجد بيانات كافية لتوقع
                                    المنتجات.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="small fw-semibold text-dark mb-2">خدمات متوقعة</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>الخدمة</th>
                                <th>آخر طلب (يوم)</th>
                                <th>التكرار</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reorderServicePredictions as $prediction)
                            <tr class="{{ $prediction['should_reorder_soon'] ? 'table-warning' : '' }}">
                                <td>{{ $prediction['service_name'] }}</td>
                                <td>{{ (int) $prediction['days_since_last'] }}</td>
                                <td>{{ (int) $prediction['ordered_count'] }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">لا توجد بيانات كافية لتوقع
                                    الخدمات.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="border rounded-4 bg-white p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 mb-0">أقرب المحلات التجارية</h2>
                    <a href="{{ route('consumer.browse', ['type' => 'products']) }}"
                        class="btn btn-sm btn-outline-primary">تصفح الكل</a>
                </div>
                <div class="row g-2">
                    @forelse ($nearbyPos as $store)
                    <div class="col-md-6">
                        <a href="{{ route('consumer.store.show', ['storeType' => 'pos', 'storeId' => $store->id]) }}"
                            class="text-decoration-none">
                            <div class="border rounded-3 p-2 h-100">
                                <div class="fw-bold text-dark">{{ $store->name }}</div>
                                <div class="small text-muted">{{ $store->address ?: 'لا يوجد عنوان مفصل' }}</div>
                                <div class="small text-primary mt-1">
                                    @if (!is_null($store->distance_km ?? null))
                                    تبعد {{ number_format((float) $store->distance_km, 1) }} كم
                                    @else
                                    المسافة غير متاحة
                                    @endif
                                </div>
                            </div>
                        </a>
                    </div>
                    @empty
                    <div class="col-12 text-muted small">لا توجد نقاط بيع مفعلة حاليا.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="border rounded-4 bg-white p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 mb-0">أقرب متاجر POS</h2>
                </div>
                <div class="row g-2">
                    @forelse ($nearbyRetailPos as $store)
                    <div class="col-md-6">
                        <a href="{{ route('consumer.store.show', ['storeType' => 'retail', 'storeId' => $store->id]) }}"
                            class="text-decoration-none">
                            <div class="border rounded-3 p-2 h-100">
                                <div class="fw-bold text-dark">{{ $store->name }}</div>
                                <div class="small text-muted">{{ $store->address ?: 'لا يوجد عنوان مفصل' }}</div>
                                <div class="small text-primary mt-1">
                                    @if (!is_null($store->distance_km ?? null))
                                    تبعد {{ number_format((float) $store->distance_km, 1) }} كم
                                    @else
                                    المسافة غير متاحة
                                    @endif
                                </div>
                            </div>
                        </a>
                    </div>
                    @empty
                    <div class="col-12 text-muted small">لا توجد متاجر POS مفعلة حاليا.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="border rounded-4 bg-white p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 mb-0">ورش قريبة</h2>
                    <a href="{{ route('consumer.browse', ['type' => 'services']) }}"
                        class="btn btn-sm btn-outline-primary">تصفح الكل</a>
                </div>
                <div class="row g-2">
                    @forelse ($nearbyWorkshops as $store)
                    <div class="col-md-6">
                        <a href="{{ route('consumer.store.show', ['storeType' => 'workshop', 'storeId' => $store->id]) }}"
                            class="text-decoration-none">
                            <div class="border rounded-3 p-2 h-100">
                                <div class="fw-bold text-dark">{{ $store->name }}</div>
                                <div class="small text-muted">{{ $store->address ?: 'لا يوجد عنوان مفصل' }}</div>
                                <div class="small text-primary mt-1">
                                    @if (!is_null($store->distance_km ?? null))
                                    تبعد {{ number_format((float) $store->distance_km, 1) }} كم
                                    @else
                                    المسافة غير متاحة
                                    @endif
                                </div>
                            </div>
                        </a>
                    </div>
                    @empty
                    <div class="col-12 text-muted small">لا توجد ورش مفعلة حاليا.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="border rounded-4 bg-white p-3 h-100">
                <h2 class="h6 mb-2">خدمات شائعة</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>الخدمة</th>
                                <th>السعر</th>
                                <th>المدة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($popularServices as $service)
                            <tr>
                                <td>{{ $service->name }}</td>
                                <td>{{ number_format((float) $service->price, 2) }}</td>
                                <td>{{ (int) $service->duration_minutes }} دقيقة</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">لا توجد خدمات متاحة.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="border rounded-4 bg-white p-3 h-100">
                <h2 class="h6 mb-2">عروض وخصومات (أقل سعر)</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>المتجر</th>
                                <th>السعر</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($offers as $offer)
                            <tr>
                                <td>{{ $offer->product?->name }}</td>
                                <td>{{ $offer->branch?->name }}</td>
                                <td>{{ number_format((float) $offer->selling_price, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">لا توجد عروض حاليا.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="border rounded-4 bg-white p-3">
        <h2 class="h6 mb-2">منتجات مميزة</h2>
        <div class="row g-3">
            @forelse ($featuredProducts as $stock)
            <div class="col-md-6 col-xl-3">
                <div class="border rounded-3 p-3 h-100">
                    <div class="fw-bold">{{ $stock->product?->name }}</div>
                    <div class="small text-muted mb-2">{{ $stock->branch?->name }}</div>
                    <div class="small">السعر: {{ number_format((float) $stock->selling_price, 2) }}</div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center text-muted">لا توجد منتجات مميزة حاليا.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection