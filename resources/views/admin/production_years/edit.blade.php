@extends('admin.layout.app')

@section('content')
    <h1 class="h4 fw-bold mb-4">تعديل موديل السيارة</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.production-years.update', $productionYear) }}"
        class="card border-0 shadow-sm">
        @csrf
        @method('PUT')
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">موديل السيارة</label>
                    <input type="number" name="year" class="form-control" min="1900" max="2100"
                        value="{{ old('year', $productionYear->year) }}" required>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button type="submit" class="btn btn-dark">تحديث</button>
            <a href="{{ route('admin.production-years.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        </div>
    </form>
@endsection
