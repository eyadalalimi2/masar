@extends('agent.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">منتجاتي</h1>
            <p class="text-muted mb-0">إدارة منتجات الوكيل فقط</p>
        </div>
        <a href="{{ route('agent.products.create') }}" class="btn btn-dark">إضافة منتج</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('agent.products.index') }}" class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label mb-1">بحث</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                        placeholder="اسم المنتج أو الموديل">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">التصنيف</label>
                    <select name="category_id" class="form-select">
                        <option value="">الكل</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) request('category_id') === (int) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100">تطبيق</button>
                </div>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('agent.products.bulk-pricing') }}" class="card border-0 shadow-sm mb-3">
        @csrf
        @method('PATCH')
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 fw-bold mb-0">تحديث الأسعار بشكل جماعي</h2>
                <span class="small text-muted">تطبيق على جميع وحدات منتجات الوكيل</span>
            </div>
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">طريقة التحديث</label>
                    <select name="update_mode" class="form-select" required>
                        <option value="percentage">نسبة مئوية</option>
                        <option value="fixed">قيمة ثابتة</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">القيمة</label>
                    <input type="number" step="0.01" name="value" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">يطبق على</label>
                    <select name="apply_to" class="form-select" required>
                        <option value="wholesale">سعر الجملة</option>
                        <option value="retail">سعر التجزئة المقترح</option>
                        <option value="both">كلاهما</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-dark w-100"
                        onclick="return confirm('سيتم تحديث أسعار جميع وحدات المنتجات، هل تريد المتابعة؟');">
                        تحديث جماعي
                    </button>
                </div>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>الموديل</th>
                    <th>الصورة</th>
                    <th>المنتج</th>
                    <th>التصنيف</th>
                    <th>سعر الجملة</th>
                    <th>المواصفات</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>{{ $product->model }}</td>
                        <td style="width:90px;">
                            @if ($product->image)
                                <img src="{{ asset('storage/' . $product->image) }}" alt="صورة"
                                    style="width:64px;height:48px;object-fit:cover;border-radius:8px;">
                            @else
                                <span class="text-muted small">لا يوجد</span>
                            @endif
                        </td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->category?->name }}</td>
                        <td>
                            @if ($product->productUnits->isNotEmpty())
                                @foreach ($product->productUnits as $unitRow)
                                    <div class="small">
                                        {{ $unitRow->unit?->name ?? 'وحدة' }}:
                                        {{ number_format((float) $unitRow->wholesale_price, 2) }}
                                    </div>
                                @endforeach
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if ($product->productVariants->isNotEmpty())
                                @foreach ($product->productVariants as $variant)
                                    <div class="small">
                                        {{ $variant->variantValue?->type?->name ?? 'المواصفة' }}:
                                        {{ $variant->variantValue?->value ?? '-' }}
                                    </div>
                                @endforeach
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <form action="{{ route('agent.products.toggle', $product) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                @if ($product->status === 'active')
                                    <button type="submit" class="badge text-bg-success border-0">مفعل</button>
                                @else
                                    <button type="submit" class="badge text-bg-secondary border-0">معطل</button>
                                @endif
                            </form>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('agent.products.show', $product) }}"
                                    class="btn btn-sm btn-outline-dark">عرض</a>
                                <a href="{{ route('agent.products.edit', $product) }}"
                                    class="btn btn-sm btn-outline-primary">تعديل</a>
                                <form method="POST" action="{{ route('agent.products.destroy', $product) }}"
                                    onsubmit="return confirm('هل أنت متأكد من حذف المنتج؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">لا يوجد منتجات</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $products->links() }}
@endsection
