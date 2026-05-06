@extends('admin.layout.app')

@section('content')
    <h1 class="h4 fw-bold mb-4">تعديل المهمة</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.tasks.update', $task) }}" class="card border-0 shadow-sm">
        @csrf
        @method('PUT')
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">اسم المهمة</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $task->name) }}"
                        required>
                </div>
                <div class="col-md-12">
                    <label class="form-label">التفاصيل</label>
                    <textarea name="details" class="form-control" rows="4">{{ old('details', $task->details) }}</textarea>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button type="submit" class="btn btn-dark">حفظ التعديل</button>
            <a href="{{ route('admin.tasks.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        </div>
    </form>
@endsection
