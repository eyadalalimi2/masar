@extends('agent.layout.app')

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

    <form action="{{ route('agent.distributors.update', $distributor->id) }}" method="POST" enctype="multipart/form-data"
        class="row g-3">
        @csrf
        @method('PUT')

        <input type="hidden" name="supplier_id" value="{{ auth()->user()->supplier->id }}">

        <div class="col-12">
            <label class="form-label">بيانات نشاطك التجاري</label>
            <div class="border rounded p-2 d-flex align-items-center gap-2">
                @if (auth()->user()->supplier?->logo_url)
                    <img src="{{ auth()->user()->supplier->logo_url }}" alt="لوجو الوكيل"
                        style="width:52px;height:52px;object-fit:cover;border-radius:10px;border:1px solid #e5e7eb;">
                @endif
                <div>
                    <div class="small text-muted">الاسم التجاري</div>
                    <div class="fw-semibold">{{ auth()->user()->supplier?->business_name ?: '-' }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <label class="form-label">الاسم</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $distributor->name) }}"
                required>
        </div>

        <div class="col-md-6">
            <label class="form-label">رقم الهاتف</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $distributor->phone) }}"
                required>
        </div>

        <div class="col-md-6">
            <label class="form-label">الفرع</label>
            <select name="branch_id" class="form-select">
                <option value="">بدون فرع</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(old('branch_id', $distributor->branch_id) == $branch->id)>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
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

        <div class="col-md-6">
            <label class="form-label">كلمة المرور (اختياري)</label>
            <input type="password" name="password" class="form-control">
        </div>

        <div class="col-md-4">
            <label class="form-label">الحالة</label>
            <select name="status" class="form-select" required>
                <option value="active" @selected(old('status', $distributor->status) === 'active')>مفعل</option>
                <option value="inactive" @selected(old('status', $distributor->status) === 'inactive')>معطل</option>
            </select>
        </div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-dark">تحديث</button>
            <a href="{{ route('agent.distributors.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        </div>
    </form>
@endsection
