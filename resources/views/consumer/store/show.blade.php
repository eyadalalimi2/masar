@extends('consumer.layout.app')

@section('title', 'عرض المتجر | المستهلك')

@section('content')
<div class="container-fluid py-2">
    @if ($errors->any())
    <div class="alert alert-danger rounded-4">
        {{ $errors->first() }}
    </div>
    @endif

    @if (session('status'))
    <div class="alert alert-success rounded-4">{{ session('status') }}</div>
    @endif

    <div class="border rounded-4 bg-white p-3 mb-3">
        <h1 class="h5 mb-1">{{ $store->name }}</h1>
        <div class="small text-muted mb-1">{{ $store->address ?: 'لا يوجد عنوان مفصل' }}</div>
        <div class="small">التقييم:
            {{ $ratingSummary['count'] > 0 ? number_format((float) $ratingSummary['avg'], 1) : '0.0' }} / 5
            ({{ $ratingSummary['count'] }})
        </div>
    </div>

    @if ($storeType === 'pos')
    <div class="border rounded-4 bg-white p-3 mb-3">
        <h2 class="h6 mb-3">منتجات المتجر</h2>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>الوحدة</th>
                        <th>السعر</th>
                        <th>مقارنة أسعار</th>
                        <th>طلب</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $item)
                    <tr>
                        <td>{{ $item->product?->name }}</td>
                        <td>{{ $item->productUnit?->unit?->name }}</td>
                        <td>{{ number_format((float) $item->selling_price, 2) }}</td>
                        <td>
                            <a href="{{ route('consumer.store.show', ['storeType' => 'pos', 'storeId' => $store->id, 'compare_product_unit_id' => $item->product_unit_id]) }}"
                                class="btn btn-sm btn-outline-secondary">قارن</a>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('consumer.orders.product.store') }}"
                                class="d-flex gap-2 align-items-center">
                                @csrf
                                <input type="hidden" name="branch_id" value="{{ $store->id }}">
                                <input type="hidden" name="product_unit_id"
                                    value="{{ $item->product_unit_id }}">
                                <input type="number" name="quantity" min="1" value="1"
                                    class="form-control form-control-sm" style="width:90px;">
                                <select name="fulfillment" class="form-select form-select-sm"
                                    style="width:120px;">
                                    <option value="pickup">استلام</option>
                                    <option value="delivery">توصيل</option>
                                </select>
                                <button class="btn btn-sm btn-primary">إنشاء طلب</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">لا توجد منتجات متاحة حاليا.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (($priceComparisons ?? collect())->isNotEmpty())
        <div class="mt-3">
            <h3 class="h6 mb-2">نتيجة مقارنة الأسعار لنفس الصنف</h3>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>الفرع</th>
                            <th>السعر</th>
                            <th>المسافة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($priceComparisons as $cmp)
                        <tr>
                            <td>{{ $cmp->branch?->name }}</td>
                            <td>{{ number_format((float) $cmp->selling_price, 2) }}</td>
                            <td>
                                @if (!is_null($cmp->distance_km ?? null))
                                {{ number_format((float) $cmp->distance_km, 1) }} كم
                                @else
                                -
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
        @if (method_exists($products, 'links'))
        <div class="mt-3">{{ $products->links() }}</div>
        @endif
    </div>
    @elseif ($storeType === 'retail')
    <div class="border rounded-4 bg-white p-3 mb-3">
        <h2 class="h6 mb-3">منتجات متجر POS</h2>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>الوحدة</th>
                        <th>السعر</th>
                        <th>طلب</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $item)
                    <tr>
                        <td>{{ $item->product?->name }}</td>
                        <td>{{ $item->productUnit?->unit?->name }}</td>
                        <td>{{ number_format((float) $item->selling_price, 2) }}</td>
                        <td>
                            <form method="POST" action="{{ route('consumer.orders.retail.store') }}"
                                class="d-flex gap-2 align-items-center">
                                @csrf
                                <input type="hidden" name="pos_id" value="{{ $store->id }}">
                                <input type="hidden" name="pos_local_product_id" value="{{ $item->id }}">
                                <input type="number" name="quantity" min="0.001" step="0.001" value="1"
                                    class="form-control form-control-sm" style="width:90px;">
                                <input type="text" name="notes" class="form-control form-control-sm"
                                    placeholder="ملاحظات">
                                <button class="btn btn-sm btn-primary">طلب</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">لا توجد منتجات POS متاحة حاليا.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($products, 'links'))
        <div class="mt-3">{{ $products->links() }}</div>
        @endif
    </div>
    @else
    <div class="border rounded-4 bg-white p-3 mb-3">
        <h2 class="h6 mb-3">خدمات الورشة</h2>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>الخدمة</th>
                        <th>السعر</th>
                        <th>المدة</th>
                        <th>طلب خدمة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                    <tr>
                        <td>{{ $service->name }}</td>
                        <td>{{ number_format((float) $service->price, 2) }}</td>
                        <td>{{ (int) $service->duration_minutes }} دقيقة</td>
                        <td>
                            <form method="POST" action="{{ route('consumer.orders.service.store') }}"
                                class="row g-2 align-items-center">
                                @csrf
                                <input type="hidden" name="workshop_id" value="{{ $store->id }}">
                                <input type="hidden" name="service_id" value="{{ $service->id }}">
                                <div class="col-md-5">
                                    <input type="datetime-local" name="appointment_at"
                                        class="form-control form-control-sm">
                                </div>
                                <div class="col-md-5">
                                    <input type="text" name="notes" class="form-control form-control-sm"
                                        placeholder="ملاحظات">
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button class="btn btn-sm btn-primary">إرسال</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">لا توجد خدمات متاحة حاليا.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($services, 'links'))
        <div class="mt-3">{{ $services->links() }}</div>
        @endif
    </div>
    @endif

    <div class="border rounded-4 bg-white p-3">
        <h2 class="h6 mb-2">آخر التقييمات</h2>
        <div class="row g-2">
            @forelse ($storeRatings as $rate)
            <div class="col-md-6">
                <div class="border rounded-3 p-2">
                    <div class="small fw-bold">{{ $rate->rating }} / 5</div>
                    <div class="small text-muted">{{ $rate->review ?: 'بدون تعليق' }}</div>
                </div>
            </div>
            @empty
            <div class="col-12 text-muted small">لا توجد تقييمات بعد.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection