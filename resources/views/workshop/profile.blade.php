@extends('workshop.layout.app')

@section('content')
@if (session('status'))
<div class="alert alert-success">{{ session('status') }}</div>
@endif

@if ($errors->any())
<div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<h1 class="workshop-section-title">الملف الشخصي</h1>
<p class="workshop-section-subtitle">تحديث بيانات الورشة وأوقات الدوام.</p>

<div class="mb-3 text-end">
    <a href="{{ route('workshop.profile.verification') }}" class="btn btn-outline-success btn-sm">طلب التوثيق</a>
</div>

<form method="POST" action="{{ route('workshop.profile.update') }}" class="workshop-panel mb-3">
    @csrf
    @method('PUT')
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label">اسم الورشة</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $workshop->name) }}"
                required>
        </div>
        <div class="col-md-4">
            <label class="form-label">الهاتف</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $workshop->phone) }}"
                required>
        </div>
        <div class="col-md-4">
            <label class="form-label">واتساب</label>
            <input type="text" name="whatsapp" class="form-control"
                value="{{ old('whatsapp', $workshop->whatsapp) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">اسم المالك</label>
            <input type="text" name="owner_name" class="form-control"
                value="{{ old('owner_name', $workshop->owner_name) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">العنوان</label>
            <input type="text" name="address" class="form-control" value="{{ old('address', $workshop->address) }}"
                required>
        </div>
        <div class="col-md-4">
            <label class="form-label">GPS</label>
            <input type="text" name="gps_location" class="form-control"
                value="{{ old('gps_location', $workshop->gps_location) }}">
        </div>
        <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary btn-sm">حفظ البيانات</button>
        </div>
    </div>
</form>

<form method="POST" action="{{ route('workshop.profile.update-working-hours') }}" class="workshop-panel">
    @csrf
    @method('PUT')
    <h2 class="h6 fw-bold mb-3">أوقات الدوام</h2>
    @include('agent.partials.working-hours-table', [
    'workingHours' => $workshop->working_hours_schedule,
    ])
    <div class="text-end mt-3">
        <button type="submit" class="btn btn-primary btn-sm">حفظ أوقات الدوام</button>
    </div>
</form>
@endsection