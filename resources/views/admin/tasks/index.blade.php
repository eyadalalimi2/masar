@extends('admin.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">مهام الأدمن</h1>
            <p class="text-muted mb-0">إضافة المهام ومتابعتها ثم إتمامها</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.tasks.store') }}" class="card border-0 shadow-sm mb-3">
        @csrf
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label mb-1">اسم المهمة</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label mb-1">التفاصيل</label>
                    <input type="text" name="details" class="form-control" value="{{ old('details') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-dark w-100">إضافة المهمة</button>
                </div>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>الرقم</th>
                    <th>الاسم</th>
                    <th>التفاصيل</th>
                    <th>تاريخ الإضافة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tasks as $task)
                    <tr>
                        <td>{{ $task->id }}</td>
                        <td>{{ $task->name }}</td>
                        <td>{{ $task->details ?: '-' }}</td>
                        <td>{{ $task->created_at?->format('Y-m-d H:i') }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.tasks.edit', $task) }}"
                                    class="btn btn-sm btn-outline-primary">تعديل</a>

                                <form method="POST" action="{{ route('admin.tasks.destroy', $task) }}"
                                    onsubmit="return confirm('هل تريد إتمام هذه المهمة؟ سيتم حذفها.');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-success" type="submit">إتمام المهمة</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">لا توجد مهام حالياً</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $tasks->links() }}
@endsection
