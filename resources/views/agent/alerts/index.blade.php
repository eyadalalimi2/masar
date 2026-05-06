@extends('agent.layout.app')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-1">كل التنبيهات</h1>
            <p class="text-muted mb-0">متابعة تنبيهات الوكيل وتعليمها كمقروء.</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge text-bg-info">غير مقروء: {{ $unreadCount }}</span>
            <form method="POST" action="{{ route('agent.alerts.mark-all') }}">
                @csrf
                @method('PATCH')
                <button class="btn btn-sm btn-outline-dark">تعليم الكل كمقروء</button>
            </form>
        </div>
    </div>

    <form method="GET" action="{{ route('agent.alerts.index') }}" class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">فلتر الحالة</label>
                    <select name="status" class="form-select">
                        <option value="all" @selected(($status ?? 'all') === 'all')>الكل</option>
                        <option value="unread" @selected(($status ?? 'all') === 'unread')>غير مقروء</option>
                        <option value="read" @selected(($status ?? 'all') === 'read')>مقروء</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-dark w-100">تطبيق</button>
                </div>
            </div>
        </div>
    </form>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>العنوان</th>
                        <th>التفاصيل</th>
                        <th>التاريخ</th>
                        <th>الحالة</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($alerts as $alert)
                        <tr>
                            <td>{{ $alert->title }}</td>
                            <td>{{ $alert->body }}</td>
                            <td>{{ $alert->created_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                @if ($alert->read_at)
                                    <span class="badge text-bg-success">مقروء</span>
                                @else
                                    <span class="badge text-bg-warning">غير مقروء</span>
                                @endif
                            </td>
                            <td>
                                @if (!$alert->read_at)
                                    <form method="POST" action="{{ route('agent.alerts.mark-read', $alert->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-primary">تعليم كمقروء</button>
                                    </form>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">لا توجد تنبيهات بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $alerts->appends(['status' => $status ?? 'all'])->links() }}</div>
    </div>
@endsection
