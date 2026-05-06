@extends('pos.layout.app')

@section('title', 'السوق')

@section('content')
<div class="hero-box reveal rv1">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="h4 mb-1">سوق التوريد من الفروع</h1>
            <p class="mb-0 text-white-50">ابحث عن المنتجات المتاحة، قارن بالسعر والقرب، وابنِ سلة توريد متعددة المنتجات.
            </p>
        </div>
        <a href="{{ route('pos.orders.index') }}" class="btn btn-light btn-sm">طلباتي</a>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
<div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="GET" class="table-wrap reveal rv1 mb-3">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label mb-1">بحث</label><input type="text" name="search"
                value="{{ request('search') }}" class="form-control" placeholder="اسم المنتج"></div>
        <div class="col-md-3"><label class="form-label mb-1">الفرع</label><select name="branch_id" class="form-select">
                <option value="0">الكل</option>
                @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((int) request('branch_id')===(int) $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select></div>
        <div class="col-md-3"><label class="form-label mb-1">الترتيب</label><select name="sort" class="form-select">
                <option value="price_asc" @selected(request('sort', 'price_asc' )==='price_asc' )>السعر تصاعدي</option>
                <option value="price_desc" @selected(request('sort')==='price_desc' )>السعر تنازلي</option>
                <option value="qty_desc" @selected(request('sort')==='qty_desc' )>التوفر الأعلى</option>
                <option value="distance" @selected(request('sort')==='distance' )>الأقرب أولًا</option>
            </select></div>
        <div class="col-md-2"><button class="btn btn-dark w-100">تطبيق</button></div>
    </div>
</form>

<div class="table-wrap reveal rv1 mb-3">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-3">مقارنة الموردين لنفس الصنف</h2>
        <form method="GET" action="{{ route('pos.marketplace.index') }}" class="row g-2 align-items-end mb-2">
            <div class="col-md-4">
                <label class="form-label mb-1">رقم Product Unit</label>
                <input type="number" min="1" name="compare_product_unit_id" class="form-control"
                    value="{{ (int) ($compareProductUnitId ?? 0) ?: '' }}" placeholder="مثال: 42">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">الكمية المقارنة</label>
                <input type="number" min="1" name="compare_quantity" class="form-control"
                    value="{{ (int) ($compareQuantity ?? 1) }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-dark w-100">قارن الآن</button>
            </div>
        </form>

        @if (($comparisonOffers ?? collect())->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>الفرع</th>
                        <th>المنتج</th>
                        <th>السعر/وحدة</th>
                        <th>الإجمالي التقديري</th>
                        <th>المسافة</th>
                        <th>المتوفر</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comparisonOffers as $offer)
                    <tr>
                        <td>{{ $offer->branch?->name }}</td>
                        <td>{{ $offer->product?->name }} ({{ $offer->productUnit?->unit?->name }})</td>
                        <td>{{ number_format((float) $offer->selling_price, 2) }}</td>
                        <td>{{ number_format((float) ($offer->estimated_total ?? 0), 2) }}</td>
                        <td>
                            @if (!is_null($offer->distance_km ?? null))
                            {{ number_format((float) $offer->distance_km, 1) }} كم
                            @else
                            -
                            @endif
                        </td>
                        <td>{{ number_format((float) $offer->quantity, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

<div class="table-wrap reveal rv1 mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <h2 class="h6 fw-bold mb-0">سلة التوريد</h2>
            <div class="small text-muted">إجمالي السلة: {{ number_format((float) $cartTotal, 2) }}</div>
        </div>

        @if ($cartItems->isEmpty())
        <div class="text-muted">السلة فارغة حاليًا.</div>
        @else
        <div class="table-responsive mb-2">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>الفرع</th>
                        <th>المنتج</th>
                        <th>الكمية</th>
                        <th>السعر</th>
                        <th>الإجمالي</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cartItems as $item)
                    <tr>
                        <td>{{ $item['branch_name'] }}</td>
                        <td>{{ $item['product_name'] }} ({{ $item['unit_name'] }})</td>
                        <td>{{ $item['quantity'] }}</td>
                        <td>{{ number_format((float) $item['unit_price'], 2) }}</td>
                        <td>{{ number_format((float) $item['unit_price'] * (float) $item['quantity'], 2) }}
                        </td>
                        <td>
                            <form method="POST"
                                action="{{ route('pos.marketplace.cart.remove', $item['key']) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">حذف</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <form method="POST" action="{{ route('pos.marketplace.cart.checkout') }}"
                class="d-flex gap-2 flex-wrap align-items-end">
                @csrf
                @if (($checkoutPaymentMethods ?? collect())->isNotEmpty())
                <div>
                    <label class="form-label mb-1">طريقة الدفع</label>
                    <select name="payment_method_id" class="form-select" required style="min-width: 280px;">
                        <option value="">اختر طريقة الدفع</option>
                        @foreach($checkoutPaymentMethods as $methodConfig)
                        <option value="{{ $methodConfig->payment_method_id }}" @selected((int) old('payment_method_id')===(int) $methodConfig->payment_method_id)>
                            {{ $methodConfig->paymentMethod?->name }}
                            ({{ $methodConfig->paymentMethod?->type === 'online' ? 'أونلاين' : 'أوفلاين' }})
                        </option>
                        @endforeach
                    </select>
                    @error('payment_method_id')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                @endif
                <input type="text" name="note" class="form-control" style="max-width: 360px;"
                    placeholder="ملاحظة عامة على الطلبات (اختياري)">
                <button class="btn btn-success">إنشاء طلبات السلة</button>
            </form>
            <form method="POST" action="{{ route('pos.marketplace.cart.clear') }}">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-secondary">تفريغ السلة</button>
            </form>
        </div>
        @if (($checkoutPaymentMethods ?? collect())->isNotEmpty())
        <div class="small text-muted mt-2">
            @foreach($checkoutPaymentMethods as $methodConfig)
            <div>
                {{ $methodConfig->paymentMethod?->name }}:
                رقم الحساب {{ $methodConfig->account_number ?: 'غير محدد' }}
                - اسم الحساب {{ $methodConfig->account_name ?: 'غير محدد' }}
                @if ($methodConfig->note)
                - ملاحظة: {{ $methodConfig->note }}
                @endif
            </div>
            @endforeach
        </div>
        @endif
        @endif
    </div>
</div>

<div class="row g-3">
    @forelse($stocks as $stock)
    <div class="col-lg-6 reveal rv2">
        <div class="stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <h2 class="h6 fw-bold mb-0 text-white">
                        {{ $stock->product?->name }}
                        <span class="text-white-50">({{ $stock->productUnit?->unit?->name }})</span>
                    </h2>
                    <div class="d-flex flex-wrap gap-1 justify-content-end">
                        <span class="badge rounded-pill text-bg-light">{{ $stock->branch?->supplier?->business_name ?: ($stock->branch?->supplier?->owner_name ?? 'الوكيل غير محدد') }}</span>
                        <span class="badge rounded-pill text-bg-secondary">{{ $stock->branch?->name }}</span>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge rounded-pill text-bg-warning">
                        <i class="bi bi-cash-stack"></i>
                        {{ number_format((float) $stock->selling_price, 2) }}
                    </span>
                    <span class="badge rounded-pill text-bg-primary">
                        <i class="bi bi-box"></i>
                        {{ $stock->productUnit?->unit?->name ?? 'وحدة غير محددة' }}
                    </span>
                    <span class="badge rounded-pill text-bg-info">
                        <i class="bi bi-geo-alt"></i>
                        @if (!is_null($stock->distance_km ?? null))
                        {{ number_format((float) $stock->distance_km, 1) }} كم
                        @else
                        غير متاحة
                        @endif
                    </span>
                </div>

                <form method="POST" action="{{ route('pos.marketplace.cart.add') }}" class="row g-2 align-items-end">
                    @csrf
                    <input type="hidden" name="branch_id" value="{{ $stock->branch_id }}">
                    <input type="hidden" name="product_unit_id" value="{{ $stock->product_unit_id }}">
                    <div class="col-7">
                        <label class="form-label mb-1 text-white-50 small">الكمية المطلوبة</label>
                        <input type="number" name="quantity" min="1" step="1" class="form-control" required
                            value="1" placeholder="مثال: 2">
                    </div>
                    <div class="col-5">
                        <label class="form-label mb-1 d-none d-md-block">&nbsp;</label>
                        <button class="btn btn-primary w-100">
                            <i class="bi bi-cart-plus"></i>
                            إضافة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 reveal rv2">
        <div class="alert alert-light border">لا توجد منتجات متاحة حاليا.</div>
    </div>
    @endforelse
</div>

<div class="mt-3">{{ $stocks->links() }}</div>
@endsection