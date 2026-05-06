@extends('admin.layout.app')

@section('content')
    <h1 class="h4 fw-bold mb-4">تعديل بيانات المندوب</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.distributors.update', $distributor) }}" method="POST" enctype="multipart/form-data"
        class="card border-0 shadow-sm">
        @csrf
        @method('PUT')

        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">الوكيل</label>
                    <select name="supplier_id" id="supplierSelect" class="form-select" required>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" data-business="{{ $supplier->business_name }}"
                                data-logo="{{ $supplier->logo_url ?? '' }}" @selected(old('supplier_id', $distributor->supplier_id) == $supplier->id)>
                                {{ $supplier->owner_name }} - {{ $supplier->business_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">بيانات نشاط الوكيل</label>
                    <div class="border rounded p-2 d-flex align-items-center gap-2" id="supplierPreviewCard">
                        <img id="supplierPreviewLogo" src="" alt="لوجو الوكيل"
                            style="width:52px;height:52px;object-fit:cover;border-radius:10px;border:1px solid #e5e7eb;display:none;">
                        <div>
                            <div class="small text-muted">الاسم التجاري</div>
                            <div id="supplierPreviewBusiness" class="fw-semibold">-</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">الفرع</label>
                    <select name="branch_id" id="branchSelect" class="form-select">
                        <option value="">بدون فرع</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" data-supplier="{{ $branch->supplier_id }}"
                                @selected(old('branch_id', $distributor->branch_id) == $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">الاسم</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $distributor->name) }}"
                        required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="text" name="phone" class="form-control"
                        value="{{ old('phone', $distributor->phone) }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">كلمة المرور (اختياري)</label>
                    <input type="password" name="password" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label">نوع المركبة</label>
                    <input type="text" name="vehicle_type" class="form-control"
                        value="{{ old('vehicle_type', $distributor->vehicle_type) }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">أماكن التوزيع</label>
                    <textarea name="distribution_points" class="form-control" rows="2"
                        placeholder="مثال: حي الجامعة، السوق المركزي، المنطقة الصناعية">{{ old('distribution_points', $distributor->distribution_points) }}</textarea>
                </div>

                <div class="col-md-3">
                    <label class="form-label">صورة المندوب</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    @if ($distributor->image)
                        <img src="{{ asset('storage/' . $distributor->image) }}" alt="صورة المندوب" class="mt-2"
                            style="width:72px;height:72px;object-fit:cover;border-radius:50%;">
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $distributor->status) === 'active')>مفعل</option>
                        <option value="inactive" @selected(old('status', $distributor->status) === 'inactive')>معطل</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-footer bg-white d-flex gap-2">
            <button type="submit" class="btn btn-dark">تحديث</button>
            <a href="{{ route('admin.distributors.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        </div>
    </form>

    <script>
        (() => {
            const supplierSelect = document.getElementById('supplierSelect');
            const branchSelect = document.getElementById('branchSelect');
            const supplierPreviewLogo = document.getElementById('supplierPreviewLogo');
            const supplierPreviewBusiness = document.getElementById('supplierPreviewBusiness');

            function syncSupplierPreview() {
                if (!supplierSelect) {
                    return;
                }

                const selected = supplierSelect.options[supplierSelect.selectedIndex];
                const business = (selected?.dataset?.business || '').trim();
                const logo = (selected?.dataset?.logo || '').trim();

                supplierPreviewBusiness.textContent = business || '-';
                if (logo !== '') {
                    supplierPreviewLogo.src = logo;
                    supplierPreviewLogo.style.display = '';
                } else {
                    supplierPreviewLogo.removeAttribute('src');
                    supplierPreviewLogo.style.display = 'none';
                }
            }

            function syncBranchOptions() {
                if (!supplierSelect || !branchSelect) {
                    return;
                }

                const selectedSupplierId = supplierSelect.value;
                const options = Array.from(branchSelect.options);

                options.forEach((option, index) => {
                    if (index === 0) {
                        option.hidden = false;
                        option.disabled = false;
                        return;
                    }

                    const optionSupplierId = option.dataset.supplier || '';
                    const isMatch = optionSupplierId === selectedSupplierId;
                    option.hidden = !isMatch;
                    option.disabled = !isMatch;
                });

                const selectedOption = branchSelect.options[branchSelect.selectedIndex];
                if (selectedOption && selectedOption.disabled) {
                    branchSelect.value = '';
                }
            }

            supplierSelect?.addEventListener('change', () => {
                syncSupplierPreview();
                syncBranchOptions();
            });
            syncSupplierPreview();
            syncBranchOptions();
        })();
    </script>
@endsection
