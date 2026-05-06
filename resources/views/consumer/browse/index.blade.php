@extends('consumer.layout.app')

@section('title', 'التصفح | المستهلك')

@section('content')
<div class="container-fluid py-2">
    <div class="border rounded-4 bg-white p-3 mb-3">
        <form method="GET" action="{{ route('consumer.browse') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">نوع العرض</label>
                <select name="type" class="form-select">
                    <option value="all" {{ $type === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="products" {{ $type === 'products' ? 'selected' : '' }}>منتجات</option>
                    <option value="services" {{ $type === 'services' ? 'selected' : '' }}>خدمات</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">بحث عن منتج</label>
                <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control"
                    placeholder="باسم المنتج أو رقم المنتج">
            </div>
            <div class="col-md-3">
                <label class="form-label">أقصى سعر</label>
                <input type="number" name="max_price" value="{{ $maxPrice > 0 ? $maxPrice : '' }}" min="0"
                    step="0.01" class="form-control" placeholder="بدون حد">
            </div>
            <div class="col-md-3">
                <label class="form-label">الترتيب</label>
                <select name="sort" class="form-select">
                    <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>السعر (الأقل أولًا)</option>
                    <option value="price_desc" {{ $sort === 'price_desc' ? 'selected' : '' }}>السعر (الأعلى أولًا)
                    </option>
                    <option value="distance" {{ $sort === 'distance' ? 'selected' : '' }}>الأقرب (GPS)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">أقل تقييم</label>
                <select name="min_rating" class="form-select">
                    <option value="0" {{ (float) ($minRating ?? 0) === 0.0 ? 'selected' : '' }}>الكل</option>
                    <option value="3" {{ (float) ($minRating ?? 0) === 3.0 ? 'selected' : '' }}>3+</option>
                    <option value="4" {{ (float) ($minRating ?? 0) === 4.0 ? 'selected' : '' }}>4+</option>
                    <option value="4.5" {{ (float) ($minRating ?? 0) === 4.5 ? 'selected' : '' }}>4.5+</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">نطاق القرب (كم)</label>
                <input type="number" name="radius_km" value="{{ (float) ($radiusKm ?? 0) > 0 ? $radiusKm : '' }}"
                    min="1" step="1" class="form-control" placeholder="بدون حد">
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-primary">تطبيق الفلترة</button>
            </div>
        </form>
    </div>

    @if ($type !== 'services')
    <div class="border rounded-4 bg-white p-3 mb-3">
        <h2 class="h6 mb-3">المنتجات</h2>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>المتجر</th>
                        <th>السعر</th>
                        <th>المسافة</th>
                        <th>التقييم</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $item)
                    @php $rate = $ratings->get('pos:' . $item->branch_id); @endphp
                    <tr>
                        <td>{{ $item->product?->name }}</td>
                        <td>
                            <a href="{{ route('consumer.store.show', ['storeType' => 'pos', 'storeId' => $item->branch_id]) }}"
                                class="text-decoration-none">
                                {{ $item->branch?->name }}
                            </a>
                        </td>
                        <td>{{ number_format((float) $item->selling_price, 2) }}</td>
                        <td>
                            @if (!is_null($item->distance_km ?? null))
                            {{ number_format((float) $item->distance_km, 1) }} كم
                            @else
                            غير متاحة
                            @endif
                        </td>
                        <td>{{ $rate ? number_format((float) $rate->avg_rating, 1) . ' / 5' : 'بدون' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">لا توجد منتجات مطابقة.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $products->links() }}</div>
    </div>
    @endif

    @if ($type !== 'products')
    <div class="border rounded-4 bg-white p-3">
        <h2 class="h6 mb-3">الخدمات</h2>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>الخدمة</th>
                        <th>الورشة</th>
                        <th>السعر</th>
                        <th>المدة</th>
                        <th>المسافة</th>
                        <th>التقييم</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                    @php $rate = $ratings->get('workshop:' . $service->workshop_id); @endphp
                    <tr>
                        <td>{{ $service->name }}</td>
                        <td>
                            <a href="{{ route('consumer.store.show', ['storeType' => 'workshop', 'storeId' => $service->workshop_id]) }}"
                                class="text-decoration-none">
                                {{ $service->workshop?->name }}
                            </a>
                        </td>
                        <td>{{ number_format((float) $service->price, 2) }}</td>
                        <td>{{ (int) $service->duration_minutes }} دقيقة</td>
                        <td>
                            @if (!is_null($service->distance_km ?? null))
                            {{ number_format((float) $service->distance_km, 1) }} كم
                            @else
                            غير متاحة
                            @endif
                        </td>
                        <td>{{ $rate ? number_format((float) $rate->avg_rating, 1) . ' / 5' : 'بدون' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">لا توجد خدمات مطابقة.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $services->links() }}</div>
    </div>
    @endif
</div>
@endsection