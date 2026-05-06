@extends('admin.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">إدارة الوحدات</h1>
            <p class="text-muted mb-0">إضافة وتعديل وحذف الوحدات</p>
        </div>
        <a href="{{ route('admin.units.create') }}" class="btn btn-dark">إضافة وحدة</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>المعرف</th>
                    <th>اسم الوحدة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($units as $unit)
                    <tr>
                        <td>{{ $unit->id }}</td>
                        <td>{{ $unit->name }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.units.edit', $unit) }}"
                                    class="btn btn-sm btn-outline-primary">تعديل</a>
                                <form method="POST" action="{{ route('admin.units.destroy', $unit) }}"
                                    onsubmit="return confirm('هل أنت متأكد من حذف الوحدة؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">لا يوجد وحدات</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $units->links() }}
@endsection
