@extends('agent.layout.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">إضافة وتعديل كمية المخزون</h1>
        <p class="text-muted mb-0">تحكم مباشر بالكميات لكل منتج من خلال الجدول.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('agent.inventory.report.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-dark btn-sm">طباعة تقرير المخزون</a>
        <a href="{{ route('agent.inventory.index') }}" class="btn btn-outline-secondary btn-sm">العودة للوحة المخزون</a>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->has('inventory'))
<div class="alert alert-danger">{{ $errors->first('inventory') }}</div>
@endif

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-3">
                <label class="form-label mb-1">بحث</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="اسم المنتج أو الموديل أو الوحدة">
            </div>
            <div class="col-lg-2">
                <label class="form-label mb-1">التصنيف</label>
                <select name="category_id" class="form-select">
                    <option value="0">كل التصنيفات</option>
                    @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) ($filters['category_id'] ?? 0)===(int) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label mb-1">الوحدة</label>
                <select name="unit_id" class="form-select">
                    <option value="0">كل الوحدات</option>
                    @foreach ($units as $unit)
                    <option value="{{ $unit->id }}" @selected((int) ($filters['unit_id'] ?? 0)===(int) $unit->id)>{{ $unit->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label mb-1">حالة المخزون</label>
                <select name="stock_status" class="form-select">
                    <option value="">الكل</option>
                    <option value="low" @selected(($filters['stock_status'] ?? '' )==='low' )>منخفض</option>
                    <option value="normal" @selected(($filters['stock_status'] ?? '' )==='normal' )>طبيعي</option>
                </select>
            </div>
            <div class="col-lg-1">
                <label class="form-label mb-1">من</label>
                <input type="number" name="stock_from" step="0.001" min="0" value="{{ $filters['stock_from'] ?? '' }}" class="form-control" placeholder="0">
            </div>
            <div class="col-lg-1">
                <label class="form-label mb-1">إلى</label>
                <input type="number" name="stock_to" step="0.001" min="0" value="{{ $filters['stock_to'] ?? '' }}" class="form-control" placeholder="9999">
            </div>
            <div class="col-lg-1 d-grid">
                <button class="btn btn-dark" type="submit">تطبيق</button>
            </div>
            <div class="col-lg-12 d-flex justify-content-end">
                <a href="{{ route('agent.inventory.stock-management') }}" class="btn btn-link btn-sm text-decoration-none">إعادة تعيين الفلاتر</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>الموديل</th>
                        <th>الصورة</th>
                        <th>المنتج</th>
                        <th>التصنيف</th>
                        <th>المخزون الحالي</th>
                        <th>إضافة أو تعديل كمية</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inventory as $row)
                    <tr>
                        <td>{{ $row->product?->model ?: '-' }}</td>
                        <td>
                            @if ($row->product?->image)
                            <img src="{{ asset('storage/' . $row->product->image) }}" alt="صورة" style="width:56px;height:42px;object-fit:cover;border-radius:8px;">
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $row->product?->name }}</div>
                            <div class="small text-muted">{{ $row->unit?->name }}</div>
                        </td>
                        <td>{{ $row->product?->category?->name ?? '-' }}</td>
                        <td>{{ number_format((float) $row->stock_quantity, 3) }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('agent.inventory.add-stock') }}" class="d-flex gap-1">
                                    @csrf
                                    <input type="hidden" name="product_unit_id" value="{{ $row->id }}">
                                    <input type="number" name="quantity" step="0.001" min="0.001" class="form-control form-control-sm" style="width:110px;" placeholder="كمية">
                                    <button type="submit" class="btn btn-sm btn-dark">إضافة</button>
                                </form>

                                <form method="POST" action="{{ route('agent.inventory.adjust-stock') }}" class="d-flex gap-1">
                                    @csrf
                                    <input type="hidden" name="product_unit_id" value="{{ $row->id }}">
                                    <input type="number" name="new_quantity" step="0.001" min="0" class="form-control form-control-sm" style="width:110px;" placeholder="كمية جديدة">
                                    <button type="submit" class="btn btn-sm btn-outline-dark">تعديل</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">لا توجد بيانات مطابقة للفلترة الحالية.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $inventory->links() }}</div>
    </div>
</div>
@endsection