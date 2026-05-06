@extends('agent.layout.app')

@section('content')
    <h1 class="h4 fw-bold mb-4">إنشاء طلب جديد</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('agent.orders.store') }}" class="card border-0 shadow-sm" id="orderForm">
        @csrf
        <input type="hidden" name="supplier_id" value="{{ auth()->user()->supplier->id }}">

        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">نوع العميل</label>
                    <select name="customer_type" class="form-select" required>
                        <option value="b2b" @selected(old('customer_type', 'b2b') === 'b2b')>عميل تجاري</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">ورش الصيانه والمحلات التجارية</label>
                    <select name="customer_id" class="form-select">
                        <option value="">اختر ورش الصيانه والمحلات التجارية</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>
                                {{ $customer->name }} - {{ $customer->phone }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">نوع البائع</label>
                    <select name="seller_type" class="form-select" required>
                        @php $oldSellerType = old('seller_type', 'supplier'); @endphp
                        <option value="supplier" @selected($oldSellerType === 'supplier')>وكيل</option>
                        <option value="branch" @selected($oldSellerType === 'branch')>فرع</option>
                        <option value="distributor" @selected($oldSellerType === 'distributor')>مندوب</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">البائع</label>
                    <select name="seller_id" class="form-select" required></select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">الفرع</label>
                    <select name="branch_id" class="form-select">
                        <option value="">بدون</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">المندوب (اختياري)</label>
                    <select name="distributor_id" class="form-select">
                        <option value="">بدون</option>
                        @foreach ($distributors as $distributor)
                            <option value="{{ $distributor->id }}" @selected(old('distributor_id') == $distributor->id)>{{ $distributor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 fw-bold mb-0">عناصر الطلب</h2>
                <button type="button" id="addItem" class="btn btn-sm btn-outline-dark">إضافة منتج</button>
            </div>

            <div id="itemsWrapper" class="d-flex flex-column gap-2">
                @php
                    $oldItems = old('items', [
                        ['product_id' => '', 'product_unit_id' => '', 'product_variant_id' => '', 'quantity' => 1],
                    ]);
                @endphp
                @foreach ($oldItems as $index => $item)
                    <div class="row g-2 item-row" data-selected-unit="{{ $item['product_unit_id'] ?? '' }}"
                        data-selected-variant="{{ $item['product_variant_id'] ?? '' }}">
                        <div class="col-md-3">
                            <select name="items[{{ $index }}][product_id]" class="form-select product-select"
                                required>
                                <option value="">اختر المنتج</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @selected(($item['product_id'] ?? '') == $product->id)>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="items[{{ $index }}][product_unit_id]" class="form-select unit-select"
                                required>
                                <option value="">اختر الوحدة</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="items[{{ $index }}][product_variant_id]"
                                class="form-select variant-select">
                                <option value="">بدون مواصفة</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" min="1" name="items[{{ $index }}][quantity]"
                                class="form-control quantity-input" placeholder="الكمية"
                                value="{{ $item['quantity'] ?? 1 }}" required>
                            <div class="small text-muted mt-1 line-price-preview">سعر الوحدة: - | إجمالي السطر: -</div>
                        </div>
                        <div class="col-md-2 d-flex">
                            <button type="button" class="btn btn-outline-danger w-100 remove-item">-</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card-footer bg-white d-flex gap-2">
            <button type="submit" class="btn btn-dark">حفظ الطلب</button>
            <a href="{{ route('agent.orders.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        </div>
    </form>

    <script>
        (() => {
            const products = @json(
                $products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'units' => $product->productUnits->map(function ($unit) {
                                    return [
                                        'id' => $unit->id,
                                        'unit_id' => $unit->unit_id,
                                        'name' => $unit->unit?->name,
                                        'wholesale_price' => (float) $unit->wholesale_price,
                                        'retail_price' => (float) $unit->retail_price,
                                    ];
                                })->values(),
                            'variants' => $product->productVariants->map(function ($variant) {
                                    $unitPrices = [];
                                    foreach ($variant->variantUnits as $variantUnit) {
                                        $unitPrices[(string) $variantUnit->unit_id] = [
                                            'wholesale_price' => (float) $variantUnit->wholesale_price,
                                            'retail_price' => (float) $variantUnit->retail_price,
                                        ];
                                    }
            
                                    return [
                                        'id' => $variant->id,
                                        'label' =>
                                            ($variant->variantValue?->type?->name ?? '') .
                                            ': ' .
                                            ($variant->variantValue?->value ?? ''),
                                        'unit_prices' => $unitPrices,
                                    ];
                                })->values(),
                        ];
                    })->values());

            const productsMap = Object.fromEntries(products.map((item) => [String(item.id), item]));

            const sellersByType = {
                supplier: [{
                    id: {{ auth()->user()->supplier->id }},
                    name: 'الوكيل الحالي'
                }],
                branch: @json($branches->map(fn($branch) => ['id' => $branch->id, 'name' => $branch->name])->values()),
                distributor: @json($distributors->map(fn($distributor) => ['id' => $distributor->id, 'name' => $distributor->name])->values()),
            };

            const productsOptions = [
                '<option value="">اختر المنتج</option>',
                ...products.map((item) => `<option value="${item.id}">${item.name}</option>`),
            ].join('');

            const wrapper = document.getElementById('itemsWrapper');
            const addBtn = document.getElementById('addItem');
            const sellerTypeSelect = document.querySelector('select[name="seller_type"]');
            const sellerIdSelect = document.querySelector('select[name="seller_id"]');
            const oldSellerId = '{{ old('seller_id') }}';

            function renderSellerOptions(selectedSellerId = '') {
                const sellerType = sellerTypeSelect.value;
                const options = sellersByType[sellerType] || [];

                sellerIdSelect.innerHTML = [
                    '<option value="">اختر البائع</option>',
                    ...options.map((item) =>
                        `<option value="${item.id}" ${String(item.id) === String(selectedSellerId) ? 'selected' : ''}>${item.name}</option>`,
                    ),
                ].join('');
            }

            function formatMoney(value) {
                return Number(value || 0).toFixed(2);
            }

            function updateLinePricePreview(row) {
                const previewEl = row.querySelector('.line-price-preview');
                const productSelect = row.querySelector('.product-select');
                const unitSelect = row.querySelector('.unit-select');
                const variantSelect = row.querySelector('.variant-select');
                const quantityInput = row.querySelector('.quantity-input');
                const customerType = 'b2b';

                const product = productsMap[productSelect.value];
                const quantity = Math.max(1, Number(quantityInput?.value || 1));

                if (!product || !unitSelect.value) {
                    previewEl.textContent = 'سعر الوحدة: - | إجمالي السطر: -';
                    return;
                }

                const selectedUnit = product.units.find((unit) => String(unit.id) === String(unitSelect.value));
                if (!selectedUnit) {
                    previewEl.textContent = 'سعر الوحدة: - | إجمالي السطر: -';
                    return;
                }

                let unitPrice = customerType === 'b2b' ? Number(selectedUnit.wholesale_price) : Number(selectedUnit
                    .retail_price);

                if (variantSelect.value) {
                    const selectedVariant = product.variants.find((variant) => String(variant.id) === String(
                        variantSelect.value));
                    const variantUnitPrice = selectedVariant?.unit_prices?.[String(selectedUnit.unit_id)];

                    if (variantUnitPrice) {
                        unitPrice = customerType === 'b2b' ? Number(variantUnitPrice.wholesale_price) : Number(
                            variantUnitPrice.retail_price);
                    }
                }

                const lineTotal = unitPrice * quantity;
                previewEl.textContent =
                    `سعر الوحدة: ${formatMoney(unitPrice)} | إجمالي السطر: ${formatMoney(lineTotal)}`;
            }

            function updateDependentSelects(row, selectedUnit = '', selectedVariant = '') {
                const productSelect = row.querySelector('.product-select');
                const unitSelect = row.querySelector('.unit-select');
                const variantSelect = row.querySelector('.variant-select');

                const product = productsMap[productSelect.value];

                if (!product) {
                    unitSelect.innerHTML = '<option value="">اختر الوحدة</option>';
                    variantSelect.innerHTML = '<option value="">بدون مواصفة</option>';
                    updateLinePricePreview(row);
                    return;
                }

                unitSelect.innerHTML = [
                    '<option value="">اختر الوحدة</option>',
                    ...product.units.map(
                        (unit) =>
                        `<option value="${unit.id}" ${String(unit.id) === String(selectedUnit) ? 'selected' : ''}>${unit.name}</option>`,
                    ),
                ].join('');

                variantSelect.innerHTML = [
                    '<option value="">بدون مواصفة</option>',
                    ...product.variants.map(
                        (variant) =>
                        `<option value="${variant.id}" ${String(variant.id) === String(selectedVariant) ? 'selected' : ''}>${variant.label}</option>`,
                    ),
                ].join('');

                updateLinePricePreview(row);
            }

            function bindProductChange(row) {
                const productSelect = row.querySelector('.product-select');
                productSelect.onchange = () => updateDependentSelects(row);

                row.querySelector('.unit-select').onchange = () => updateLinePricePreview(row);
                row.querySelector('.variant-select').onchange = () => updateLinePricePreview(row);
                row.querySelector('.quantity-input').oninput = () => updateLinePricePreview(row);
            }

            function bindRemoveButtons() {
                wrapper.querySelectorAll('.remove-item').forEach((button) => {
                    button.onclick = () => {
                        const rows = wrapper.querySelectorAll('.item-row');
                        if (rows.length > 1) {
                            button.closest('.item-row').remove();
                            reindexRows();
                        }
                    };
                });
            }

            function reindexRows() {
                wrapper.querySelectorAll('.item-row').forEach((row, index) => {
                    row.querySelector('.product-select').setAttribute('name', `items[${index}][product_id]`);
                    row.querySelector('.unit-select').setAttribute('name', `items[${index}][product_unit_id]`);
                    row.querySelector('.variant-select').setAttribute('name',
                        `items[${index}][product_variant_id]`);
                    row.querySelector('.quantity-input').setAttribute('name', `items[${index}][quantity]`);
                });
            }

            addBtn.addEventListener('click', () => {
                const row = document.createElement('div');
                row.className = 'row g-2 item-row';
                row.innerHTML = `
                    <div class="col-md-3">
                        <select class="form-select product-select" required>${productsOptions}</select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select unit-select" required>
                            <option value="">اختر الوحدة</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select variant-select">
                            <option value="">بدون مواصفة</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" min="1" class="form-control quantity-input" placeholder="الكمية" required>
                        <div class="small text-muted mt-1 line-price-preview">سعر الوحدة: - | إجمالي السطر: -</div>
                    </div>
                    <div class="col-md-2 d-flex">
                        <button type="button" class="btn btn-outline-danger w-100 remove-item">-</button>
                    </div>
                `;
                wrapper.appendChild(row);
                bindProductChange(row);
                reindexRows();
                bindRemoveButtons();
            });

            wrapper.querySelectorAll('.item-row').forEach((row) => {
                bindProductChange(row);
                updateDependentSelects(row, row.dataset.selectedUnit, row.dataset.selectedVariant);
            });

            sellerTypeSelect?.addEventListener('change', () => renderSellerOptions());

            bindRemoveButtons();
            reindexRows();
            renderSellerOptions(oldSellerId || sellerIdSelect.value);
        })();
    </script>
@endsection
