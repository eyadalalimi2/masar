@extends('admin.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">تعديل المحل التجاري</h1>
        <a href="{{ route('admin.commercial-stores.index') }}" class="btn btn-outline-secondary">رجوع</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.commercial-stores.update', $customer) }}" enctype="multipart/form-data"
        class="card border-0 shadow-sm">
        @csrf
        @method('PUT')
        <div class="card-body row g-3">
            @include('admin.commercial-stores.partials.form')
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button type="submit" class="btn btn-dark">تحديث</button>
            <a href="{{ route('admin.commercial-stores.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.commercial-stores.updateWorkingHours', $customer) }}"
        class="card border-0 shadow-sm mt-3">
        @csrf
        @method('PUT')
        <div class="card-body">
            <h2 class="h6 fw-bold mb-1">تعديل أوقات الدوام</h2>
            <p class="text-muted small mb-3">يمكن للأدمن تعديل جدول عمل المحل التجاري من هنا.</p>
            @include('agent.partials.working-hours-table', [
                'workingHours' => old('working_hours', $customer->working_hours_schedule),
            ])
        </div>
        <div class="card-footer bg-white">
            <button type="submit" class="btn btn-dark">حفظ أوقات الدوام</button>
        </div>
    </form>
@endsection
