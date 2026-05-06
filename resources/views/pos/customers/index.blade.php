@extends('pos.layout.app')

@section('title', 'العملاء')

@section('content')
<div class="hero-box reveal rv1">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="h4 mb-1">إدارة العملاء</h1>
            <p class="mb-0 text-white-50">عرض نشاط العملاء وتكرار الشراء وإجمالي الإنفاق.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('pos.sales.index') }}" class="btn btn-light btn-sm">المبيعات</a>
        </div>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success reveal rv1 mb-3">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('pos.customers.store') }}" class="table-wrap reveal rv1 mb-3">
    @csrf
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label mb-1">اسم العميل</label>
            <input type="text" name="customer_name" value="{{ old('customer_name') }}" class="form-control"
                placeholder="اسم العميل" required>
        </div>
        <div class="col-md-3">
            <label class="form-label mb-1">الهاتف</label>
            <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" class="form-control"
                placeholder="رقم الهاتف" required>
        </div>
        <div class="col-md-3">
            <label class="form-label mb-1">ملاحظات</label>
            <input type="text" name="notes" value="{{ old('notes') }}" class="form-control"
                placeholder="اختياري">
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100">إضافة عميل</button>
        </div>
    </div>
</form>

<form method="GET" class="table-wrap reveal rv1 mb-3">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label mb-1">بحث</label><input type="text" name="search"
                value="{{ request('search') }}" class="form-control" placeholder="اسم/هاتف"></div>
        <div class="col-md-2"><button class="btn btn-dark w-100">تطبيق</button></div>
    </div>
</form>

<div class="table-wrap reveal rv2">
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>العميل</th>
                    <th>الهاتف</th>
                    <th>عدد العمليات</th>
                    <th>إجمالي المشتريات</th>
                    <th>آخر عملية</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $row)
                <tr>
                    <td>{{ $row->customer_name ?: '-' }}</td>
                    <td>{{ $row->customer_phone ?: '-' }}</td>
                    <td>{{ (int) $row->sales_count }}</td>
                    <td>{{ number_format((float) $row->total_spent, 2) }}</td>
                    <td>{{ $row->last_sale_at }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">لا توجد بيانات عملاء بعد.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $customers->links() }}</div>
</div>
@endsection