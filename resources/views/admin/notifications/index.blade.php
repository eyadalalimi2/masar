@extends('admin.layout.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">مركز الإشعارات</h1>
        <p class="text-muted mb-0">إدارة الإشعارات المركزية ومتابعة إشعارات الأدمن.</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="badge text-bg-info">غير مقروء: {{ $unreadCount }}</span>
        <form method="POST" action="{{ route('admin.notifications.dispatch-scheduled') }}">
            @csrf
            <button class="btn btn-sm btn-outline-dark">تنفيذ المجدول الآن</button>
        </form>
        <form method="POST" action="{{ route('admin.notifications.smart-alerts.generate') }}">
            @csrf
            <button class="btn btn-sm btn-outline-warning">توليد Smart Alerts</button>
        </form>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
<div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-3">إرسال إشعار مركزي</h2>
        <form method="POST" action="{{ route('admin.notifications.store') }}" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label class="form-label">العنوان</label>
                <input type="text" name="title" class="form-control" required value="{{ old('title') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">الفئة المستهدفة</label>
                <select name="target_type" class="form-select" required>
                    <option value="all" @selected(old('target_type')==='all' )>الكل</option>
                    <option value="suppliers" @selected(old('target_type')==='suppliers' )>الوكلاء</option>
                    <option value="branches" @selected(old('target_type')==='branches' )>الفروع</option>
                    <option value="distributors" @selected(old('target_type')==='distributors' )>المندوبون</option>
                    <option value="customers" @selected(old('target_type')==='customers' )>العملاء التجاريون وPOS</option>
                    <option value="consumers" @selected(old('target_type')==='consumers' )>المستهلكون</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="w-100">
                    <label class="form-label">وضع الإرسال</label>
                    <select name="send_mode" id="send_mode" class="form-select" required>
                        <option value="now" @selected(old('send_mode', 'now' )==='now' )>فوري</option>
                        <option value="scheduled" @selected(old('send_mode')==='scheduled' )>مجدول</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4" id="scheduled_for_wrap" style="display:none;">
                <label class="form-label">وقت الجدولة</label>
                <input type="datetime-local" name="scheduled_for" class="form-control"
                    value="{{ old('scheduled_for') }}">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active"
                        @checked(old('is_active', '1' )=='1' )>
                    <label class="form-check-label" for="is_active">الرسالة مفعلة</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label">المحتوى</label>
                <textarea name="message" class="form-control" rows="4" required>{{ old('message') }}</textarea>
            </div>
            <div class="col-12 text-end">
                <button class="btn btn-dark">إرسال الإشعار</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 fw-bold mb-0">إشعاراتي</h2>
                    <form method="POST" action="{{ route('admin.notifications.mark-all') }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-sm btn-outline-dark">تعليم الكل كمقروء</button>
                    </form>
                </div>

                <form method="GET" action="{{ route('admin.notifications.index') }}" class="row g-2 mb-3">
                    <div class="col-sm-4">
                        <select name="status" class="form-select">
                            <option value="all" @selected($status==='all' )>الكل</option>
                            <option value="unread" @selected($status==='unread' )>غير مقروء</option>
                            <option value="read" @selected($status==='read' )>مقروء</option>
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <select name="type" class="form-select">
                            <option value="all" @selected(($alertType ?? 'all' )==='all' )>كل الأنواع</option>
                            <option value="delay" @selected(($alertType ?? 'all' )==='delay' )>تنبيهات التأخير</option>
                            <option value="smart" @selected(($alertType ?? 'all' )==='smart' )>تنبيهات ذكية</option>
                            <option value="broadcast" @selected(($alertType ?? 'all' )==='broadcast' )>الإشعارات المركزية</option>
                            <option value="other" @selected(($alertType ?? 'all' )==='other' )>أخرى</option>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <select name="source" class="form-select">
                            <option value="all" @selected(($alertSource ?? 'all' )==='all' )>كل المصادر</option>
                            <option value="orders" @selected(($alertSource ?? 'all' )==='orders' )>الطلبات</option>
                            <option value="dispatch" @selected(($alertSource ?? 'all' )==='dispatch' )>التوزيع</option>
                            <option value="system" @selected(($alertSource ?? 'all' )==='system' )>النظام</option>
                        </select>
                    </div>
                    <div class="col-sm-1">
                        <button class="btn btn-outline-primary w-100">تطبيق</button>
                    </div>
                </form>

                <div class="row g-2 mb-3">
                    <div class="col-md-4 col-6">
                        <div class="border rounded p-2 bg-light">
                            <div class="small text-muted">إجمالي اليوم</div>
                            <div class="fw-bold">{{ number_format((int) ($dailyAlertSummary['total'] ?? 0)) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-6">
                        <div class="border rounded p-2 bg-light">
                            <div class="small text-muted">تأخير</div>
                            <div class="fw-bold text-warning">
                                {{ number_format((int) ($dailyAlertSummary['delay'] ?? 0)) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-6">
                        <div class="border rounded p-2 bg-light">
                            <div class="small text-muted">ذكي</div>
                            <div class="fw-bold text-danger">
                                {{ number_format((int) ($dailyAlertSummary['smart'] ?? 0)) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-6">
                        <div class="border rounded p-2 bg-light">
                            <div class="small text-muted">مركزية</div>
                            <div class="fw-bold text-primary">
                                {{ number_format((int) ($dailyAlertSummary['broadcast'] ?? 0)) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-6">
                        <div class="border rounded p-2 bg-light">
                            <div class="small text-muted">أخرى</div>
                            <div class="fw-bold text-secondary">
                                {{ number_format((int) ($dailyAlertSummary['other'] ?? 0)) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-6">
                        <div class="border rounded p-2 bg-light">
                            <div class="small text-muted">الطلبات (اليوم)</div>
                            <div class="fw-bold text-dark">
                                {{ number_format((int) ($dailySourceSummary['orders'] ?? 0)) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-6">
                        <div class="border rounded p-2 bg-light">
                            <div class="small text-muted">التوزيع (اليوم)</div>
                            <div class="fw-bold text-dark">
                                {{ number_format((int) ($dailySourceSummary['dispatch'] ?? 0)) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-6">
                        <div class="border rounded p-2 bg-light">
                            <div class="small text-muted">النظام (اليوم)</div>
                            <div class="fw-bold text-dark">
                                {{ number_format((int) ($dailySourceSummary['system'] ?? 0)) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>العنوان</th>
                                <th>النوع</th>
                                <th>المصدر</th>
                                <th>التفاصيل</th>
                                <th>الوقت</th>
                                <th>الحالة</th>
                                <th>إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($alerts as $alert)
                            <tr>
                                <td>{{ $alert->title }}</td>
                                <td>
                                    @php
                                    $dataType = (string) ($alert->data['type'] ?? '');
                                    $dataSource = (string) ($alert->data['source'] ?? '');
                                    $alertKind =
                                    str_contains($alert->title, 'تأخير') ||
                                    str_contains($dataType, 'delay')
                                    ? 'delay'
                                    : (str_contains($alert->title, 'تحذير') ||
                                    $dataType === 'smart_alert'
                                    ? 'smart'
                                    : (str_contains($alert->title, 'إشعار مركزي') ||
                                    str_contains($alert->title, 'مجدولة') ||
                                    $dataType === 'admin_broadcast_result'
                                    ? 'broadcast'
                                    : 'other'));
                                    $alertSource =
                                    $dataSource === 'orders' ||
                                    str_contains($dataType, 'order') ||
                                    str_contains($dataType, 'delay') ||
                                    str_contains($dataType, 'stage') ||
                                    str_contains($alert->title, 'طلب')
                                    ? 'orders'
                                    : ($dataSource === 'dispatch' ||
                                    str_contains($dataType, 'dispatch') ||
                                    str_contains($dataType, 'distributor') ||
                                    str_contains($alert->title, 'توزيع') ||
                                    str_contains($alert->title, 'إسناد')
                                    ? 'dispatch'
                                    : 'system');
                                    @endphp
                                    @if ($alertKind === 'delay')
                                    <span class="badge text-bg-warning">تأخير</span>
                                    @elseif ($alertKind === 'smart')
                                    <span class="badge text-bg-danger">ذكي</span>
                                    @elseif ($alertKind === 'broadcast')
                                    <span class="badge text-bg-primary">مركزي</span>
                                    @else
                                    <span class="badge text-bg-secondary">أخرى</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($alertSource === 'orders')
                                    <span class="badge text-bg-dark">الطلبات</span>
                                    @elseif ($alertSource === 'dispatch')
                                    <span class="badge text-bg-info">التوزيع</span>
                                    @else
                                    <span class="badge text-bg-secondary">النظام</span>
                                    @endif
                                </td>
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
                                    <form method="POST"
                                        action="{{ route('admin.notifications.mark-read', $alert->id) }}">
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
                                <td colspan="7" class="text-center text-muted py-4">لا توجد إشعارات بعد.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $alerts->appends(['status' => $status, 'type' => $alertType ?? 'all', 'source' => $alertSource ?? 'all'])->links() }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">آخر الرسائل المركزية</h2>
                <div class="d-flex flex-column gap-2">
                    @forelse ($broadcasts as $broadcast)
                    <div class="border rounded p-2">
                        <div class="fw-bold">{{ $broadcast->title }}</div>
                        <div class="small text-muted mb-1">{{ $broadcast->message }}</div>
                        <div class="d-flex justify-content-between small mb-2">
                            <span class="text-muted">الهدف: {{ $broadcast->target_type }}</span>
                            <span class="text-muted">{{ $broadcast->created_at?->format('Y-m-d H:i') }}</span>
                        </div>
                        <div class="small text-muted mb-2">
                            الحالة:
                            @if (!$broadcast->is_active)
                            <span class="badge text-bg-secondary">متوقفة</span>
                            @elseif ($broadcast->dispatched_at)
                            <span class="badge text-bg-success">مرسلة</span>
                            @elseif ($broadcast->scheduled_for)
                            <span class="badge text-bg-info">مجدولة
                                {{ $broadcast->scheduled_for?->format('Y-m-d H:i') }}</span>
                            @else
                            <span class="badge text-bg-warning">بانتظار الإرسال</span>
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            <form method="POST"
                                action="{{ route('admin.notifications.broadcasts.toggle', $broadcast) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-secondary">
                                    {{ $broadcast->is_active ? 'إيقاف' : 'تفعيل' }}
                                </button>
                            </form>

                            @if ($broadcast->is_active && !$broadcast->dispatched_at)
                            <form method="POST"
                                action="{{ route('admin.notifications.broadcasts.dispatch', $broadcast) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-primary">إرسال الآن</button>
                            </form>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-4">لا توجد رسائل مركزية بعد.</div>
                    @endforelse
                </div>
                <div class="mt-3">
                    {{ $broadcasts->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const sendMode = document.getElementById('send_mode');
    const scheduleWrap = document.getElementById('scheduled_for_wrap');

    function toggleScheduleInput() {
        scheduleWrap.style.display = sendMode.value === 'scheduled' ? '' : 'none';
    }

    sendMode.addEventListener('change', toggleScheduleInput);
    toggleScheduleInput();
</script>
@endpush
@endsection