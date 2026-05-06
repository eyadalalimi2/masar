@extends('admin.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">إضافة عميل</h1>
        <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">رجوع</a>
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

    <form method="POST" action="{{ route('admin.customers.store') }}" enctype="multipart/form-data"
        class="card border-0 shadow-sm">
        @csrf
        <div class="card-body row g-3">
            @include('admin.customers.partials.form')
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button type="submit" class="btn btn-dark">حفظ</button>
            <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        </div>
    </form>
@endsection
