@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">قيم المواصفات</h1>
        <p class="text-muted mb-0">ربط القيم بأنواعها مثل R15 و 60Ah و 20W-50</p>
    </div>
    <a href="{{ route('admin.variant-values.create') }}" class="btn btn-dark">إضافة قيمة</a>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="GET" action="{{ route('admin.variant-values.index') }}" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label for="variant_type_id" class="form-label">نوع المواصفة</label>
            <select name="variant_type_id" id="variant_type_id" class="form-select">
                <option value="">كل الأنواع</option>
                @foreach ($variantTypes as $type)
                <option value="{{ $type->id }}"
                    {{ (string) request('variant_type_id') === (string) $type->id ? 'selected' : '' }}>
                    {{ $type->name }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-5">
            <label for="q" class="form-label">بحث بالقيمة</label>
            <input type="text" id="q" name="q" class="form-control" value="{{ request('q') }}"
                placeholder="مثال: 60Ah أو R15">
        </div>

        <div class="col-md-2 d-grid gap-2">
            <button type="submit" class="btn btn-dark">تصفية</button>
            <a href="{{ route('admin.variant-values.index') }}" class="btn btn-outline-secondary">إعادة ضبط</a>
        </div>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>المعرف</th>
                <th>نوع المواصفة</th>
                <th>القيمة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($variantValues as $value)
            <tr>
                <td>{{ $value->id }}</td>
                <td>{{ $value->type?->name }}</td>
                <td>{{ $value->value }}</td>
                <td>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.variant-values.edit', $value) }}"
                            class="btn btn-sm btn-outline-primary">تعديل</a>
                        <form method="POST" action="{{ route('admin.variant-values.destroy', $value) }}"
                            onsubmit="return confirm('هل أنت متأكد من حذف قيمة المواصفة؟');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center text-muted py-4">لا توجد قيم مواصفات</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $variantValues->links() }}
@endsection