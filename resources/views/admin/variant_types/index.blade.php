@extends('admin.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">أنواع المواصفات</h1>
            <p class="text-muted mb-0">إدارة المقاس والأمبير واللزوجة وغيرها</p>
        </div>
        <a href="{{ route('admin.variant-types.create') }}" class="btn btn-dark">إضافة نوع</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>المعرف</th>
                    <th>الاسم</th>
                    <th>عدد القيم</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($variantTypes as $type)
                    <tr>
                        <td>{{ $type->id }}</td>
                        <td>{{ $type->name }}</td>
                        <td>{{ $type->values_count }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.variant-types.edit', $type) }}"
                                    class="btn btn-sm btn-outline-primary">تعديل</a>
                                <form method="POST" action="{{ route('admin.variant-types.destroy', $type) }}"
                                    onsubmit="return confirm('هل أنت متأكد من حذف نوع المواصفة؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">لا يوجد أنواع مواصفات</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $variantTypes->links() }}
@endsection
