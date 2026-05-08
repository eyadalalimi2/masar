@extends('agent.layout.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">إدارة المخزون</h1>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('agent.inventory.report.pdf', request()->query()) }}" target="_blank" class="btn btn-dark btn-sm">طباعة المخزون</a>
        <a href="{{ route('agent.inventory.stock-management') }}" class="btn btn-outline-dark btn-sm">إضافة وتعديل كمية المخزون</a>
        <a href="{{ route('agent.inventory.distribution-page') }}" class="btn btn-outline-primary btn-sm">صفحة التوزيع على الفروع</a>
        <a href="{{ route('agent.replenishment.index') }}" class="btn btn-outline-dark btn-sm">صفحة طلبات الفروع</a>
        <a href="{{ route('agent.inventory.movements') }}" class="btn btn-outline-secondary btn-sm">صفحة سجل حركة المخزون</a>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->has('inventory'))
<div class="alert alert-danger">{{ $errors->first('inventory') }}</div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('agent.inventory.index') }}" class="row g-2 align-items-end mb-3">
            <div class="col-lg-4">
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
            <div class="col-lg-2 d-grid">
                <button class="btn btn-dark" type="submit">تطبيق</button>
            </div>
            <div class="col-lg-2 d-grid">
                <a href="{{ route('agent.inventory.index') }}" class="btn btn-outline-secondary">إعادة تعيين</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>الموديل</th>
                        <th>الصوره</th>
                        <th>الاسم</th>
                        <th>الوحده</th>
                        <th>الكميه</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inventory as $row)
                    <tr>
                        <td>{{ (string) ($row->product?->model ?? '-') }}</td>
                        <td>
                            @if (!empty($row->product?->image))
                            <img src="{{ asset('storage/' . ltrim((string) $row->product->image, '/')) }}" alt="صورة المنتج" style="width:56px;height:42px;object-fit:cover;border-radius:8px;">
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ (string) ($row->product?->name ?? '-') }}</td>
                        <td>{{ (string) ($row->unit?->name ?? '-') }}</td>
                        <td>{{ number_format((float) ($row->stock_quantity ?? 0), 3) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">لا توجد بيانات مخزون حالياً.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $inventory->links() }}</div>
    </div>
</div>
@endsection