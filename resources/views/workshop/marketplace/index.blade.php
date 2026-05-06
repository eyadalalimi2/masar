@extends('workshop.layout.app')

@section('content')
@if (session('status'))
<div class="alert alert-success">{{ session('status') }}</div>
@endif
@if ($errors->any())
<div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
@if ($errors->has('cart'))
<div class="alert alert-danger">{{ $errors->first('cart') }}</div>
@endif

<h1 class="workshop-section-title">السوق</h1>
<p class="workshop-section-subtitle">سوق حي مرتبط بمخزون الفروع الفعلي لإنشاء طلبات شراء دقيقة للورشة.</p>

<form method="GET" class="workshop-panel mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label mb-1">بحث</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                placeholder="اسم المنتج أو الموديل">
        </div>
        <div class="col-md-3">
            <label class="form-label mb-1">الفرع</label>
            <select name="branch_id" class="form-select">
                <option value="0">كل الفروع</option>
                @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((int) request('branch_id')===(int) $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label mb-1">الترتيب</label>
            <select name="sort" class="form-select">
                <option value="price_asc" @selected(request('sort', 'price_asc' )==='price_asc' )>السعر تصاعدي</option>
                <option value="price_desc" @selected(request('sort')==='price_desc' )>السعر تنازلي</option>
                <option value="qty_desc" @selected(request('sort')==='qty_desc' )>الأعلى توفرًا</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-dark w-100">تطبيق</button>
        </div>
    </div>
</form>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="workshop-panel">
            <h2 class="h6 fw-bold mb-3">منتجات متاحة للطلب</h2>
            <div class="row g-3">
                @forelse ($stocks as $stock)
                <div class="col-12">
                    <div class="border rounded-3 p-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-bold">{{ $stock->product?->name }}
                                    ({{ $stock->productUnit?->unit?->name }})
                                </div>
                                <div class="small text-muted">الموديل: {{ $stock->product?->model ?: '—' }}</div>
                                <div class="small text-muted">الفرع: {{ $stock->branch?->name }}</div>
                                <div class="small text-muted">التوفر:
                                    {{ number_format((float) $stock->quantity, 3) }}
                                </div>
                                <div class="small text-muted">السعر:
                                    {{ number_format((float) $stock->selling_price, 2) }} ر.ي
                                </div>
                            </div>

                            <form method="POST" action="{{ route('workshop.marketplace.cart.add') }}"
                                class="row g-2">
                                @csrf
                                <input type="hidden" name="stock_id" value="{{ $stock->id }}">
                                <div class="col-12">
                                    <input type="number" name="quantity" min="1" step="1"
                                        class="form-control form-control-sm" placeholder="الكمية" required>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary btn-sm w-100" type="submit">إضافة إلى
                                        السلة</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="alert alert-light border mb-0">لا توجد منتجات متاحة حاليا في مخزون الفروع.</div>
                </div>
                @endforelse
            </div>

            <div class="mt-3">{{ $stocks->links() }}</div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="workshop-panel h-100">
            <h2 class="h6 fw-bold mb-3">مؤشرات السوق</h2>
            <ul class="workshop-list">
                <li>منتجات متاحة حاليًا: {{ $marketStats['available_products'] }}</li>
                <li>فروع نشطة في السوق: {{ $marketStats['active_branches'] }}</li>
                <li>طلبات شراء معلقة: {{ $marketStats['pending_purchase_orders'] }}</li>
            </ul>

            <hr>
            <h2 class="h6 fw-bold mb-3">سلة الشراء</h2>
            <div class="small text-muted mb-2">عدد العناصر: {{ $cartSummary['items_count'] }}</div>
            <div class="small text-muted mb-2">إجمالي الكمية:
                {{ number_format((float) $cartSummary['total_quantity'], 3) }}
            </div>
            <div class="small text-muted mb-3">الإجمالي: {{ number_format((float) $cartSummary['total_amount'], 2) }}
                ر.ي</div>

            @if ($cartItems->isEmpty())
            <div class="alert alert-light border mb-0">السلة فارغة حاليًا.</div>
            @else
            <div class="table-responsive mb-2">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>الصنف</th>
                            <th>الفرع</th>
                            <th>الكمية</th>
                            <th>الإجمالي</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cartItems as $item)
                        <tr>
                            <td>{{ $item['product_name'] }}<br><span
                                    class="small text-muted">{{ $item['unit_name'] }}</span></td>
                            <td>{{ $item['branch_name'] }}</td>
                            <td>{{ number_format((float) $item['quantity'], 3) }}</td>
                            <td>{{ number_format((float) $item['line_total'], 2) }} ر.ي</td>
                            <td>
                                <form method="POST"
                                    action="{{ route('workshop.marketplace.cart.remove', $item['stock_id']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" type="submit">حذف</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <form method="POST" action="{{ route('workshop.marketplace.cart.checkout') }}" class="mb-2">
                @csrf
                @if (($checkoutPaymentMethods ?? collect())->isNotEmpty())
                <label class="form-label mb-1">طريقة الدفع</label>
                <select name="payment_method_id" class="form-select form-select-sm mb-2" required>
                    <option value="">اختر طريقة الدفع</option>
                    @foreach ($checkoutPaymentMethods as $methodConfig)
                    <option value="{{ $methodConfig->payment_method_id }}" @selected((int) old('payment_method_id')===(int) $methodConfig->payment_method_id)>
                        {{ $methodConfig->paymentMethod?->name }}
                        ({{ $methodConfig->paymentMethod?->type === 'online' ? 'أونلاين' : 'أوفلاين' }})
                    </option>
                    @endforeach
                </select>
                @error('payment_method_id')
                <div class="text-danger small mb-2">{{ $message }}</div>
                @enderror
                @endif
                <button class="btn btn-success btn-sm w-100" type="submit">اعتماد السلة وإنشاء الطلبات</button>
            </form>
            @if (($checkoutPaymentMethods ?? collect())->isNotEmpty())
            <div class="small text-muted mb-2">
                @foreach ($checkoutPaymentMethods as $methodConfig)
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
            <form method="POST" action="{{ route('workshop.marketplace.cart.clear') }}">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-secondary btn-sm w-100" type="submit">تفريغ السلة</button>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection