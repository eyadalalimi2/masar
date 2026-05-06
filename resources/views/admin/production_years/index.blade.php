@extends('admin.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">موديلات السيارة</h1>
            <p class="text-muted mb-0">إدارة موديلات السيارة المستخدمة لمنتجات الزيوت</p>
        </div>
        <a href="{{ route('admin.production-years.create') }}" class="btn btn-dark">إضافة موديل</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>المعرف</th>
                    <th>موديل السيارة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($productionYears as $productionYear)
                    <tr>
                        <td>{{ $productionYear->id }}</td>
                        <td>{{ $productionYear->year }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.production-years.edit', $productionYear) }}"
                                    class="btn btn-sm btn-outline-primary">تعديل</a>
                                <form method="POST" action="{{ route('admin.production-years.destroy', $productionYear) }}"
                                    onsubmit="return confirm('هل أنت متأكد من حذف موديل السيارة؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">لا توجد موديلات سيارة مضافة</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $productionYears->links() }}
@endsection
