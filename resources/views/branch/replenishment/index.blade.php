@extends('branch.layout.app')

@section('title', 'طلب توريد من الوكيل')

@section('content')
<div class="container-fluid py-2">
    <h1 class="h4 fw-bold mb-3">طلب توريد من الوكيل</h1>

    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">إنشاء طلب جديد (متعدد المنتجات)</h2>
            <form method="POST" action="{{ route('branch.replenishment.store') }}" class="row g-2" id="replenishmentForm">
                @csrf
                @php
                $rows = old('items', [
                ['product_id' => '', 'product_unit_id' => '', 'requested_quantity' => '', 'note' => ''],
                ]);
                $productsPayload = $products->map(fn($product) => [
                'id' => $product->id,
                'label' => $product->name . ' (' . $product->model . ')',
                'units' => $product->productUnits->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->unit?->name ?? 'وحدة',
                ])->values(),
                ])->values();
                $oldRowsPayload = base64_encode(json_encode($rows, JSON_UNESCAPED_UNICODE));
                @endphp

                <div id="replenishmentConfig" data-products='@json($productsPayload)'
                    data-old-rows="{{ $oldRowsPayload }}" hidden></div>

                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-2">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 280px;">المنتج</th>
                                    <th style="min-width: 180px;">الوحدة</th>
                                    <th style="min-width: 140px;">الكمية</th>
                                    <th>ملاحظة</th>
                                    <th style="width: 90px;">حذف</th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                @foreach ($rows as $i => $row)
                                <tr class="item-row">
                                    <td>
                                        <select name="items[{{ $i }}][product_id]" class="form-select product-select" required>
                                            <option value="">اختر المنتج</option>
                                            @foreach ($products as $product)
                                            <option value="{{ $product->id }}" @selected((int) ($row['product_id'] ?? 0)===(int) $product->id)>
                                                {{ $product->name }} ({{ $product->model }})
                                            </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="items[{{ $i }}][product_unit_id]" class="form-select unit-select" required>
                                            <option value="">اختر الوحدة</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input class="form-control" type="number" step="0.001" min="0.001"
                                            name="items[{{ $i }}][requested_quantity]" value="{{ $row['requested_quantity'] ?? '' }}" required>
                                    </td>
                                    <td>
                                        <input class="form-control" name="items[{{ $i }}][note]" value="{{ $row['note'] ?? '' }}"
                                            placeholder="ملاحظة (اختياري)">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-row">حذف</button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-dark w-100" id="addRowBtn">+ إضافة منتج</button>
                </div>
                <div class="col-md-2"><button class="btn btn-dark w-100">إرسال</button></div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>المنتج</th>
                        <th>الوحدة</th>
                        <th>الكمية</th>
                        <th>الحالة</th>
                        <th>تاريخ الطلب</th>
                        <th>ملاحظة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $requestRow)
                    <tr>
                        <td>#{{ $requestRow->id }}</td>
                        <td>{{ $requestRow->product?->name }}</td>
                        <td>{{ $requestRow->productUnit?->unit?->name }}</td>
                        <td>{{ number_format((float) $requestRow->requested_quantity, 3) }}</td>
                        <td>{{ $requestRow->status }}</td>
                        <td>{{ $requestRow->requested_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $requestRow->note ?: '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">لا توجد طلبات توريد حتى الآن.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $requests->links() }}</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const configNode = document.getElementById('replenishmentConfig');
        const tableBody = document.getElementById('itemsTableBody');
        const addRowBtn = document.getElementById('addRowBtn');
        if (!configNode || !tableBody || !addRowBtn) return;

        const products = JSON.parse(configNode.dataset.products || '[]');
        const productsById = new Map(products.map((p) => [String(p.id), p]));

        const oldRows = JSON.parse(atob(configNode.dataset.oldRows || 'W10='));

        function productOptions(selectedProductId = '') {
            return ['<option value="">اختر المنتج</option>', ...products.map((p) => {
                const selected = String(selectedProductId) === String(p.id) ? 'selected' : '';
                return `<option value="${p.id}" ${selected}>${p.label}</option>`;
            })].join('');
        }

        function unitOptions(productId, selectedUnitId = '') {
            const product = productsById.get(String(productId));
            const units = product?.units || [];

            return ['<option value="">اختر الوحدة</option>', ...units.map((u) => {
                const selected = String(selectedUnitId) === String(u.id) ? 'selected' : '';
                return `<option value="${u.id}" ${selected}>${u.name || 'وحدة'}</option>`;
            })].join('');
        }

        function reindexRows() {
            tableBody.querySelectorAll('.item-row').forEach((row, index) => {
                row.querySelector('.product-select')?.setAttribute('name', `items[${index}][product_id]`);
                row.querySelector('.unit-select')?.setAttribute('name', `items[${index}][product_unit_id]`);
                row.querySelector('.qty-input')?.setAttribute('name', `items[${index}][requested_quantity]`);
                row.querySelector('.note-input')?.setAttribute('name', `items[${index}][note]`);
            });
        }

        function bindRow(row) {
            const productSelect = row.querySelector('.product-select');
            const unitSelect = row.querySelector('.unit-select');
            const removeBtn = row.querySelector('.remove-row');

            productSelect?.addEventListener('change', () => {
                unitSelect.innerHTML = unitOptions(productSelect.value);
            });

            removeBtn?.addEventListener('click', () => {
                const rowCount = tableBody.querySelectorAll('.item-row').length;
                if (rowCount <= 1) return;
                row.remove();
                reindexRows();
            });
        }

        function addRow(data = null) {
            const row = document.createElement('tr');
            row.className = 'item-row';

            const productId = data?.product_id ?? '';
            const unitId = data?.product_unit_id ?? '';
            const qty = data?.requested_quantity ?? '';
            const note = data?.note ?? '';

            const escapeAttr = (value) => String(value)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            row.innerHTML = `
                    <td>
                        <select class="form-select product-select" required>
                            ${productOptions(productId)}
                        </select>
                    </td>
                    <td>
                        <select class="form-select unit-select" required>
                            ${unitOptions(productId, unitId)}
                        </select>
                    </td>
                    <td>
                        <input class="form-control qty-input" type="number" step="0.001" min="0.001" value="${escapeAttr(qty)}" required>
                    </td>
                    <td>
                        <input class="form-control note-input" value="${escapeAttr(note)}" placeholder="ملاحظة (اختياري)">
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-row">حذف</button>
                    </td>
                `;

            tableBody.appendChild(row);
            bindRow(row);
            reindexRows();
        }

        tableBody.innerHTML = '';
        (oldRows.length ? oldRows : [{}]).forEach((row) => addRow(row));

        addRowBtn.addEventListener('click', () => addRow());
    })();
</script>
@endpush