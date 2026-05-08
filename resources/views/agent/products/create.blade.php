@extends('agent.layout.app')

@section('content')
<h1 class="h4 fw-bold mb-4">إضافة منتج</h1>

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('agent.products.store') }}" enctype="multipart/form-data" class="row g-3"
    id="productForm">
    @csrf
    <input type="hidden" name="supplier_id" value="{{ auth()->user()->supplier->id }}">

    <div class="col-md-6">
        <label class="form-label">اسم المنتج</label>
        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">موديل المنتج</label>
        <input type="text" name="model" class="form-control" value="{{ old('model') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">التصنيف</label>
        <select name="category_id" class="form-select" required>
            <option value="">اختر التصنيف</option>
            @foreach ($categories as $category)
            <option value="{{ $category->id }}"
                data-is-oil="{{ str_contains(mb_strtolower($category->name), 'زيت') || str_contains(mb_strtolower($category->name), 'زيوت') || str_contains(mb_strtolower($category->name), 'oil') || str_contains(mb_strtolower($category->name), 'oils') || str_contains(mb_strtolower($category->name), 'lubricant') ? '1' : '0' }}"
                @selected(old('category_id')==$category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 car-models-field" style="display:none;">
        <label class="form-label">موديلات السيارة</label>
        @php $oldCarModels = collect(old('car_models', []))->map(fn($item) => (string) $item)->all(); @endphp
        <div class="car-models-panel border rounded p-2 bg-light-subtle">
            <div class="d-flex flex-column flex-md-row gap-2 mb-2">
                <input type="text" class="form-control form-control-sm car-model-search"
                    placeholder="ابحث عن موديل السيارة">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-dark car-model-action"
                        data-action="select-all">تحديد الكل</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary car-model-action"
                        data-action="clear">إلغاء التحديد</button>
                </div>
            </div>
            <div class="car-models-list border rounded bg-white p-2" style="max-height: 180px; overflow:auto;">
                @foreach ($productionYears as $year)
                <div class="form-check car-model-item" data-year="{{ $year }}">
                    <input class="form-check-input" type="checkbox" name="car_models[]"
                        id="car-model-agent-create-{{ $year }}" value="{{ $year }}"
                        @checked(in_array((string) $year, $oldCarModels, true))>
                    <label class="form-check-label" for="car-model-agent-create-{{ $year }}">
                        {{ $year }}
                    </label>
                </div>
                @endforeach
            </div>
            <div class="small text-muted mt-2 car-model-count">لم يتم اختيار أي موديل</div>
        </div>
        <small class="text-muted">يمكن اختيار موديل واحد أو عدة موديلات بسهولة من القائمة.</small>
    </div>
    <div class="col-md-6">
        <label class="form-label">الصورة</label>
        <input type="file" name="image" class="form-control" accept="image/*">
    </div>
    <div class="col-md-6">
        <label class="form-label">الحالة</label>
        <select name="status" class="form-select" required>
            <option value="active" @selected(old('status', 'active' )==='active' )>مفعل</option>
            <option value="inactive" @selected(old('status')==='inactive' )>معطل</option>
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">الوصف</label>
        <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
    </div>

    <div class="col-12 mt-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h6 fw-bold mb-0">الوحدات</h2>
            <button type="button" class="btn btn-sm btn-outline-dark" id="addUnitRow">إضافة وحدة</button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle bg-white">
                <thead class="table-light">
                    <tr>
                        <th>الوحدة</th>
                        <th>سعر الجملة</th>
                        <th>المخزون</th>
                        <th>معامل التحويل</th>
                        <th>حذف</th>
                    </tr>
                </thead>
                <tbody id="unitsTableBody">
                    @php $oldUnits = old('units', [['unit_id' => '', 'wholesale_price' => '', 'stock_quantity' => '', 'conversion_factor' => 1]]); @endphp
                    @foreach ($oldUnits as $index => $row)
                    <tr class="unit-row">
                        <td>
                            <select name="units[{{ $index }}][unit_id]" class="form-select" required>
                                <option value="">اختر الوحدة</option>
                                @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected(($row['unit_id'] ?? '' )==$unit->id)>
                                    {{ $unit->name }}
                                </option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" step="0.01" min="0"
                                name="units[{{ $index }}][wholesale_price]" class="form-control"
                                value="{{ $row['wholesale_price'] ?? '' }}" required></td>
                        <td><input type="number" step="0.001" min="0"
                                name="units[{{ $index }}][stock_quantity]" class="form-control"
                                value="{{ $row['stock_quantity'] ?? '' }}" required></td>
                        <td><input type="number" step="0.0001" min="0.0001"
                                name="units[{{ $index }}][conversion_factor]" class="form-control"
                                value="{{ $row['conversion_factor'] ?? 1 }}"></td>
                        <td style="width: 90px;"><button type="button"
                                class="btn btn-sm btn-outline-danger w-100 remove-unit-row">حذف</button></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-12 mt-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h6 fw-bold mb-0">المواصفات</h2>
            <button type="button" class="btn btn-sm btn-outline-dark" id="addVariantRow">إضافة مواصفة</button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle bg-white">
                <thead class="table-light">
                    <tr>
                        <th>نوع المواصفة</th>
                        <th>القيمة</th>
                        <th>حذف</th>
                    </tr>
                </thead>
                <tbody id="variantsTableBody">
                    @php $oldVariants = old('variants', []); @endphp
                    @forelse ($oldVariants as $index => $row)
                    <tr class="variant-row">
                        <td>
                            <select name="variants[{{ $index }}][variant_type_id]"
                                class="form-select variant-type" data-index="{{ $index }}">
                                <option value="">اختر النوع</option>
                                @foreach ($variantTypes as $type)
                                <option value="{{ $type->id }}" @selected(($row['variant_type_id'] ?? '' )==$type->id)>
                                    {{ $type->name }}
                                </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select name="variants[{{ $index }}][variant_value_id]"
                                class="form-select variant-value" data-index="{{ $index }}">
                                <option value="">اختر القيمة</option>
                            </select>
                        </td>
                        <td style="width: 90px;"><button type="button"
                                class="btn btn-sm btn-outline-danger w-100 remove-variant-row">حذف</button></td>
                    </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-dark">حفظ</button>
        <a href="{{ route('agent.products.index') }}" class="btn btn-outline-secondary">إلغاء</a>
    </div>
</form>

<script>
    @php
    $variantTypesPayload = $variantTypes -
        > map(
            fn($type) => [
                'id' => $type - > id,
                'name' => $type - > name,
                'values' => $type - > values - > map(fn($value) => ['id' => $value - > id, 'value' => $value - > value]),
            ],
        ) -
        > values();
    @endphp

        (() => {
            const units = @json($units);
            const variantTypes = @json($variantTypesPayload);
            const oldVariants = @json($oldVariants);
            const categorySelect = document.querySelector('select[name="category_id"]');
            const carModelFields = document.querySelectorAll('.car-models-field');
            const carModelInputs = document.querySelectorAll('[name="car_models[]"]');
            const carModelCountLabels = document.querySelectorAll('.car-model-count');

            const unitsBody = document.getElementById('unitsTableBody');
            const variantsBody = document.getElementById('variantsTableBody');

            const unitOptions = ['<option value="">اختر الوحدة</option>', ...units.map((unit) =>
                `<option value="${unit.id}">${unit.name}</option>`)].join('');

            function toggleCarModels() {
                const selectedOption = categorySelect?.selectedOptions?.[0];
                const isOil = selectedOption?.dataset?.isOil === '1';

                carModelFields.forEach((field) => {
                    field.style.display = isOil ? '' : 'none';
                });

                applyCarModelRequiredState(isOil);
            }

            function updateCarModelCount() {
                const selectedCount = Array.from(carModelInputs).filter((input) => input.checked).length;

                carModelCountLabels.forEach((label) => {
                    label.textContent = selectedCount > 0 ?
                        `تم اختيار ${selectedCount} موديل` :
                        'لم يتم اختيار أي موديل';
                });
            }

            function applyCarModelRequiredState(isOil) {
                carModelInputs.forEach((input) => {
                    input.required = false;
                });

                if (!isOil || carModelInputs.length === 0) {
                    updateCarModelCount();
                    return;
                }

                const hasSelection = Array.from(carModelInputs).some((input) => input.checked);

                if (!hasSelection) {
                    carModelInputs[0].required = true;
                }

                updateCarModelCount();
            }

            function initCarModelControls() {
                carModelFields.forEach((field) => {
                    const searchInput = field.querySelector('.car-model-search');
                    const items = field.querySelectorAll('.car-model-item');
                    const actionButtons = field.querySelectorAll('.car-model-action');

                    searchInput?.addEventListener('input', () => {
                        const term = searchInput.value.trim();

                        items.forEach((item) => {
                            const year = item.dataset.year ?? '';
                            item.style.display = year.includes(term) ? '' : 'none';
                        });
                    });

                    actionButtons.forEach((button) => {
                        button.addEventListener('click', () => {
                            const action = button.dataset.action;

                            carModelInputs.forEach((input) => {
                                input.checked = action === 'select-all';
                            });

                            const selectedOption = categorySelect?.selectedOptions?.[0];
                            applyCarModelRequiredState(selectedOption?.dataset?.isOil ===
                                '1');
                        });
                    });
                });

                carModelInputs.forEach((input) => {
                    input.addEventListener('change', () => {
                        const selectedOption = categorySelect?.selectedOptions?.[0];
                        applyCarModelRequiredState(selectedOption?.dataset?.isOil === '1');
                    });
                });
            }

            function reindexUnits() {
                unitsBody.querySelectorAll('.unit-row').forEach((row, index) => {
                    row.querySelector('select').setAttribute('name', `units[${index}][unit_id]`);
                    row.querySelectorAll('input')[0].setAttribute('name',
                        `units[${index}][wholesale_price]`);
                    row.querySelectorAll('input')[1].setAttribute('name',
                        `units[${index}][stock_quantity]`);
                    row.querySelectorAll('input')[2].setAttribute('name',
                        `units[${index}][conversion_factor]`);
                });
            }

            function bindUnitRemove() {
                unitsBody.querySelectorAll('.remove-unit-row').forEach((btn) => {
                    btn.onclick = () => {
                        const rows = unitsBody.querySelectorAll('.unit-row');
                        if (rows.length > 1) {
                            btn.closest('.unit-row').remove();
                            reindexUnits();
                        }
                    };
                });
            }

            function addUnitRow() {
                const row = document.createElement('tr');
                row.className = 'unit-row';
                row.innerHTML = `
                    <td><select class="form-select" required>${unitOptions}</select></td>
                    <td><input type="number" step="0.01" min="0" class="form-control" required></td>
                    <td><input type="number" step="0.001" min="0" class="form-control" required></td>
                    <td><input type="number" step="0.0001" min="0.0001" class="form-control" value="1"></td>
                    <td style="width: 90px;"><button type="button" class="btn btn-sm btn-outline-danger w-100 remove-unit-row">حذف</button></td>
                `;
                unitsBody.appendChild(row);
                reindexUnits();
                bindUnitRemove();
            }

            function fillVariantValues(row, typeId, selectedValue = '') {
                const valueSelect = row.querySelector('.variant-value');
                const type = variantTypes.find((item) => Number(item.id) === Number(typeId));
                valueSelect.innerHTML = '<option value="">اختر القيمة</option>';

                if (!type) {
                    return;
                }

                type.values.forEach((value) => {
                    const option = document.createElement('option');
                    option.value = value.id;
                    option.textContent = value.value;
                    if (String(selectedValue) === String(value.id)) {
                        option.selected = true;
                    }
                    valueSelect.appendChild(option);
                });
            }

            function reindexVariants() {
                variantsBody.querySelectorAll('.variant-row').forEach((row, index) => {
                    const typeSelect = row.querySelector('.variant-type');
                    const valueSelect = row.querySelector('.variant-value');
                    typeSelect.setAttribute('name', `variants[${index}][variant_type_id]`);
                    valueSelect.setAttribute('name', `variants[${index}][variant_value_id]`);
                    typeSelect.dataset.index = index;
                    valueSelect.dataset.index = index;
                });
            }

            function bindVariantRows() {
                variantsBody.querySelectorAll('.variant-row').forEach((row) => {
                    const typeSelect = row.querySelector('.variant-type');
                    const removeBtn = row.querySelector('.remove-variant-row');

                    typeSelect.onchange = () => fillVariantValues(row, typeSelect.value);

                    removeBtn.onclick = () => {
                        row.remove();
                        reindexVariants();
                    };
                });
            }

            function addVariantRow() {
                const row = document.createElement('tr');
                row.className = 'variant-row';
                row.innerHTML = `
                    <td>
                        <select class="form-select variant-type">
                            <option value="">اختر النوع</option>
                            ${variantTypes.map((type) => `<option value="${type.id}">${type.name}</option>`).join('')}
                        </select>
                    </td>
                    <td>
                        <select class="form-select variant-value">
                            <option value="">اختر القيمة</option>
                        </select>
                    </td>
                    <td style="width: 90px;"><button type="button" class="btn btn-sm btn-outline-danger w-100 remove-variant-row">حذف</button></td>
                `;
                variantsBody.appendChild(row);
                reindexVariants();
                bindVariantRows();
            }

            document.getElementById('addUnitRow').addEventListener('click', addUnitRow);
            document.getElementById('addVariantRow').addEventListener('click', addVariantRow);
            categorySelect?.addEventListener('change', toggleCarModels);

            bindUnitRemove();
            reindexUnits();
            bindVariantRows();
            reindexVariants();
            initCarModelControls();
            toggleCarModels();

            variantsBody.querySelectorAll('.variant-row').forEach((row, index) => {
                const selectedType = oldVariants[index]?.variant_type_id ?? '';
                const selectedValue = oldVariants[index]?.variant_value_id ?? '';
                if (selectedType) {
                    fillVariantValues(row, selectedType, selectedValue);
                }
            });
        })();
</script>
@endsection