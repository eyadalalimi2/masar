@extends('admin.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">إدارة التصنيفات</h1>
            <p class="text-muted mb-0">إضافة وتعديل وحذف التصنيفات</p>
        </div>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-dark">إضافة تصنيف</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>المعرف</th>
                    <th>اسم التصنيف</th>
                    <th>التصنيف الأب</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>{{ $category->id }}</td>
                        <td>{{ $category->name }}</td>
                        <td>{{ $category->parent?->name ?: '-' }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.categories.edit', $category) }}"
                                    class="btn btn-sm btn-outline-primary">تعديل</a>
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                    onsubmit="return confirm('هل أنت متأكد من حذف التصنيف؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">لا يوجد تصنيفات</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $categories->links() }}
@endsection
