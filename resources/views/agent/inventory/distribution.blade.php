@extends('agent.layout.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">التوزيع على الفروع</h1>
        <p class="text-muted mb-0">صرف الكميات من مخزون الوكيل إلى الفروع المعتمدة.</p>
    </div>
    <a href="{{ route('agent.inventory.index') }}" class="btn btn-outline-secondary btn-sm">العودة للوحة المخزون</a>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->has('inventory'))
<div class="alert alert-danger">{{ $errors->first('inventory') }}</div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h6 fw-bold mb-0">التوزيع والصرف حسب المنتج</h2>
            <button type="button" class="btn btn-dark btn-sm" id="addDistributionRowBtn">إضافة عنصر</button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" id="distributionTable">
                <thead>
                    <tr>
                        <th>الموديل</th>
                        <th>الصورة</th>
                        <th>المنتج</th>
                        <th>التصنيف</th>
                        <th>الوحدة</th>
                        <th>الكمية</th>
                    </tr>
                </thead>
                <tbody id="distributionTableBody">
                    <tr id="emptyDistributionRow">
                        <td colspan="6" class="text-center text-muted">لا يتم عرض كل المنتجات. استخدم زر "إضافة عنصر" ثم أدخل رقم الموديل.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h6 fw-bold mb-1">طلبات الفروع</h2>
                <p class="text-muted mb-0">الانتقال لإدارة طلبات توريد الفروع من صفحة مستقلة.</p>
            </div>
            <a href="{{ route('agent.replenishment.index') }}" class="btn btn-dark btn-sm">فتح طلبات الفروع</a>
        </div>
    </div>
</div>

<template id="distributionRowTemplate">
    <tr>
        <td style="min-width:180px;">
            <input type="hidden" name="product_unit_id" class="js-product-unit-id">
            <input type="text" class="form-control form-control-sm js-model-input" placeholder="رقم الموديل" required>
            <div class="small text-danger mt-1 d-none js-model-error"></div>
        </td>
        <td class="js-image-cell"><span class="text-muted">-</span></td>
        <td class="js-product-name">-</td>
        <td class="js-category-name">-</td>
        <td class="js-unit-name">-</td>
        <td>
            <form method="POST" action="{{ route('agent.inventory.distribute') }}" class="d-flex flex-wrap gap-2 align-items-center js-distribute-form">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="product_unit_id" class="js-form-product-unit-id">
                <select name="branch_id" class="form-select form-select-sm" style="min-width:170px;" required>
                    <option value="">اختر الفرع</option>
                    @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
                <input type="number" name="quantity" step="0.001" min="0.001" class="form-control form-control-sm" style="width:120px;" placeholder="الكمية" required>
                <button type="submit" class="btn btn-outline-primary btn-sm">صرف</button>
                <button type="button" class="btn btn-outline-danger btn-sm js-remove-row">حذف</button>
            </form>
            <div class="small text-muted mt-1 js-stock-hint"></div>
        </td>
    </tr>
</template>

<script>
    (() => {
        const tableBody = document.getElementById('distributionTableBody');
        const emptyRow = document.getElementById('emptyDistributionRow');
        const addBtn = document.getElementById('addDistributionRowBtn');
        const rowTemplate = document.getElementById('distributionRowTemplate');
        const lookupUrl = "{{ route('agent.inventory.distribution.model-lookup') }}";

        const hideEmptyRow = () => {
            if (emptyRow) {
                emptyRow.classList.add('d-none');
            }
        };

        const showEmptyRowIfNeeded = () => {
            const dataRows = tableBody.querySelectorAll('tr:not(#emptyDistributionRow)');
            if (emptyRow && dataRows.length === 0) {
                emptyRow.classList.remove('d-none');
            }
        };

        const resetRowDetails = (row) => {
            row.querySelector('.js-product-unit-id').value = '';
            row.querySelector('.js-form-product-unit-id').value = '';
            row.querySelector('.js-image-cell').innerHTML = '<span class="text-muted">-</span>';
            row.querySelector('.js-product-name').textContent = '-';
            row.querySelector('.js-category-name').textContent = '-';
            row.querySelector('.js-unit-name').textContent = '-';
            row.querySelector('.js-stock-hint').textContent = '';
        };

        const setRowError = (row, message) => {
            const error = row.querySelector('.js-model-error');
            error.textContent = message || '';
            error.classList.toggle('d-none', !message);
        };

        const fillRowDetails = (row, data) => {
            row.querySelector('.js-product-unit-id').value = String(data.id || '');
            row.querySelector('.js-form-product-unit-id').value = String(data.id || '');
            if (data.image_url) {
                row.querySelector('.js-image-cell').innerHTML = `<img src="${data.image_url}" alt="صورة" style="width:56px;height:42px;object-fit:cover;border-radius:8px;">`;
            } else {
                row.querySelector('.js-image-cell').innerHTML = '<span class="text-muted">-</span>';
            }
            row.querySelector('.js-product-name').textContent = data.product_name || '-';
            row.querySelector('.js-category-name').textContent = data.category_name || '-';
            row.querySelector('.js-unit-name').textContent = data.unit_name || '-';
            row.querySelector('.js-stock-hint').textContent = `المتاح: ${Number(data.stock_quantity || 0).toFixed(3)}`;
        };

        const lookupModel = async (row, modelValue) => {
            const model = String(modelValue || '').trim();
            resetRowDetails(row);
            setRowError(row, '');

            if (!model) {
                return;
            }

            try {
                const response = await fetch(`${lookupUrl}?model=${encodeURIComponent(model)}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    setRowError(row, payload.message || 'تعذر جلب بيانات الموديل.');
                    return;
                }

                fillRowDetails(row, payload);
            } catch (e) {
                setRowError(row, 'حدث خطأ في الاتصال أثناء البحث عن الموديل.');
            }
        };

        const wireRow = (row) => {
            const modelInput = row.querySelector('.js-model-input');
            const removeBtn = row.querySelector('.js-remove-row');
            const form = row.querySelector('.js-distribute-form');

            modelInput.addEventListener('change', () => lookupModel(row, modelInput.value));
            modelInput.addEventListener('blur', () => lookupModel(row, modelInput.value));
            modelInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    lookupModel(row, modelInput.value);
                }
            });

            removeBtn.addEventListener('click', () => {
                row.remove();
                showEmptyRowIfNeeded();
            });

            form.addEventListener('submit', (event) => {
                const productUnitId = row.querySelector('.js-form-product-unit-id').value;
                if (!productUnitId) {
                    event.preventDefault();
                    setRowError(row, 'أدخل رقم موديل صحيح أولاً.');
                }
            });
        };

        const addRow = () => {
            hideEmptyRow();
            const fragment = rowTemplate.content.cloneNode(true);
            const row = fragment.querySelector('tr');
            tableBody.appendChild(fragment);
            wireRow(tableBody.lastElementChild);
            const input = tableBody.lastElementChild.querySelector('.js-model-input');
            if (input) {
                input.focus();
            }
        };

        addBtn.addEventListener('click', addRow);
    })();
</script>
@endsection