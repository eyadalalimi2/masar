@extends('admin.layout.app')

@section('content')
    <h1 class="h4 fw-bold mb-4">تعديل قيمة المواصفة</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.variant-values.update', $variantValue) }}" class="card border-0 shadow-sm">
        @csrf
        @method('PUT')
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">نوع المواصفة</label>
                    <select name="variant_type_id" class="form-select" required>
                        @foreach ($variantTypes as $type)
                            <option value="{{ $type->id }}" @selected(old('variant_type_id', $variantValue->variant_type_id) == $type->id)>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">القيمة</label>
                    <input type="text" name="value" class="form-control"
                        value="{{ old('value', $variantValue->value) }}" required>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button type="submit" class="btn btn-dark">تحديث</button>
            <a href="{{ route('admin.variant-values.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        </div>
    </form>
@endsection
