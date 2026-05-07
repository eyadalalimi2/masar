@extends('distributor.layout.app')

@section('title', 'الملف الشخصي للمندوب')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-1">الملف الشخصي للمندوب</h1>
            <p class="text-muted mb-0">تعديل بياناتك الأساسية وبيانات التواصل</p>
        </div>
    </div>

    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('distributor.profile.update') }}" method="POST" enctype="multipart/form-data"
        class="card border-0 shadow-sm">
        @csrf
        @method('PUT')

        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">الاسم</label>
                    <input type="text" name="name" class="form-control"
                        value="{{ old('name', $distributor->name) }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="text" name="phone" class="form-control"
                        value="{{ old('phone', $distributor->phone) }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">كلمة المرور الجديدة (اختياري)</label>
                    <input type="password" name="password" class="form-control"
                        placeholder="اتركها فارغة إذا لا تريد التغيير">
                </div>

                <div class="col-md-6">
                    <label class="form-label">نوع المركبة</label>
                    <input type="text" name="vehicle_type" class="form-control"
                        value="{{ old('vehicle_type', $distributor->vehicle_type) }}"
                        placeholder="دراجة - سيارة - شاحنة">
                </div>

                <div class="col-md-6">
                    <label class="form-label">أماكن التوزيع</label>
                    <textarea name="distribution_points" class="form-control" rows="3"
                        placeholder="مثال: حي الجامعة، السوق المركزي، المنطقة الصناعية">{{ old('distribution_points', $distributor->distribution_points) }}</textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">صورة المندوب</label>
                    <input type="file" id="distributorImageInput" name="image"
                        class="form-control {{ $distributor->image ? 'd-none' : '' }}" accept="image/*">
                    @if ($distributor->image)
                    <label for="distributorImageInput" class="d-inline-block mt-1" style="cursor: pointer;">
                        <img src="{{ asset('storage/' . $distributor->image) }}" alt="صورة المندوب"
                            style="width: 90px; height: 90px; object-fit: cover; border-radius: 999px; border: 1px solid #e5e7eb;">
                    </label>
                    <div class="text-muted small">اضغط على الصورة لاستبدالها.</div>
                    @endif
                </div>

                <div class="col-md-6">
                    <label class="form-label">المعاينة الحالية</label>
                    <div>
                        @if ($distributor->image)
                        <img src="{{ asset('storage/' . $distributor->image) }}" alt="صورة المندوب"
                            style="width: 84px; height: 84px; object-fit: cover; border-radius: 999px; border: 1px solid #e5e7eb;">
                        @else
                        <div class="text-muted small">لا توجد صورة حالية</div>
                        @endif
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">الوكيل</label>
                    <input type="text" class="form-control"
                        value="{{ $distributor->supplier?->business_name ?? ($distributor->supplier?->owner_name ?? '-') }}"
                        disabled>
                </div>

                <div class="col-md-6">
                    <label class="form-label">الفرع</label>
                    <input type="text" class="form-control" value="{{ $distributor->branch?->name ?? '-' }}"
                        disabled>
                </div>
            </div>
        </div>

        <div class="card-footer bg-white d-flex gap-2">
            <button type="submit" class="btn btn-dark">حفظ التعديلات</button>
            <a href="{{ route('distributor.dashboard') }}" class="btn btn-outline-secondary">إلغاء</a>
        </div>
    </form>
</div>
@endsection