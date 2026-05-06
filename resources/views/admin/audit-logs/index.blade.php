@extends('admin.layout.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">سجل العمليات</h1>
            <p class="text-muted mb-0">متابعة جميع عمليات الأدمن التي تؤثر على البيانات</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label mb-1">الأدمن</label>
                    <select name="admin_id" class="form-select">
                        <option value="">الكل</option>
                        @foreach ($admins as $admin)
                            <option value="{{ $admin->id }}" @selected((string) request('admin_id') === (string) $admin->id)>
                                {{ $admin->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label mb-1">بحث بالإجراء</label>
                    <input type="text" name="action" class="form-control" value="{{ request('action') }}"
                        placeholder="admin.orders.status">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">الطريقة</label>
                    <select name="method" class="form-select">
                        <option value="">الكل</option>
                        @foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method)
                            <option value="{{ $method }}" @selected(request('method') === $method)>{{ $method }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-dark w-100">تطبيق</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline-secondary w-100">إعادة ضبط</a>
                </div>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>الأدمن</th>
                    <th>الإجراء</th>
                    <th>الطريقة</th>
                    <th>المسار</th>
                    <th>IP</th>
                    <th>التفاصيل</th>
                    <th>الإجراءات</th>
                    <th>الوقت</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td>{{ $log->admin?->name ?? 'غير معروف' }}</td>
                        <td><code>{{ $log->action }}</code></td>
                        <td>{{ $log->method }}</td>
                        <td><code>{{ $log->path }}</code></td>
                        <td>{{ $log->ip_address }}</td>
                        <td>
                            @if (!empty($log->meta))
                                <details>
                                    <summary>عرض</summary>
                                    <pre class="small mb-0">{{ json_encode($log->meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                </details>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.audit-logs.show', $log) }}"
                                class="btn btn-sm btn-outline-primary">عرض</a>
                        </td>
                        <td>{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">لا توجد سجلات بعد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $logs->links() }}
@endsection
