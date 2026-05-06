@extends('admin.layout.app')

@section('content')
    <h1 class="h4 fw-bold mb-4">إضافة تصنيف</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.categories.store') }}" class="card border-0 shadow-sm">
        @csrf
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">اسم التصنيف</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">التصنيف الأب</label>
                    <select name="parent_id" class="form-select">
                        <option value="">بدون</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('parent_id') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button type="submit" class="btn btn-dark">حفظ</button>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        </div>
    </form>
@endsection
