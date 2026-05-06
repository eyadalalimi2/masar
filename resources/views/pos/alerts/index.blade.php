@extends('pos.layout.app')

@section('title', 'التنبيهات')

@section('content')
    <div class="hero-box reveal rv1">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h4 mb-1">تنبيهات المحل التجاري</h1>
                <p class="mb-0 text-white-50">طلبات جديدة وتحديثات حالات الطلبات.</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge text-bg-info">غير مقروء: {{ $unreadCount }}</span>
                <form method="POST" action="{{ route('pos.alerts.mark-all') }}">@csrf @method('PATCH')
                    <button class="btn btn-sm btn-light">تعليم الكل كمقروء</button>
                </form>
            </div>
        </div>
    </div>

    <form method="GET" class="table-wrap reveal rv1 mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label mb-1">فلتر الحالة</label><select name="status"
                    class="form-select">
                    <option value="all" @selected(($status ?? 'all') === 'all')>الكل</option>
                    <option value="unread" @selected(($status ?? 'all') === 'unread')>غير مقروء</option>
                    <option value="read" @selected(($status ?? 'all') === 'read')>مقروء</option>
                </select></div>
            <div class="col-md-2"><button class="btn btn-dark w-100">تطبيق</button></div>
        </div>
    </form>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-wrap reveal rv2">
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
                    @forelse($alerts as $alert)
                        <tr>
                            <td>{{ $alert->title }}</td>
                            <td>{{ $alert->body }}</td>
                            <td>{{ $alert->created_at?->format('Y-m-d H:i') }}</td>
                            <td>{!! $alert->read_at
                                ? '<span class="badge text-bg-success">مقروء</span>'
                                : '<span class="badge text-bg-warning">غير مقروء</span>' !!}</td>
                            <td>
                                @if (!$alert->read_at)
                                    <form method="POST" action="{{ route('pos.alerts.mark-read', $alert->id) }}">@csrf
                                        @method('PATCH')<button class="btn btn-sm btn-outline-primary">تعليم
                                            كمقروء</button></form>
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

