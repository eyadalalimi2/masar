@extends('admin.layout.app')

@section('content')
    <h1 class="h4 fw-bold mb-4">إضافة وحدة</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.units.store') }}" class="card border-0 shadow-sm">
        @csrf
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">اسم الوحدة</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button type="submit" class="btn btn-dark">حفظ</button>
            <a href="{{ route('admin.units.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        </div>
    </form>
@endsection
