@extends('distributor.layout.app')

@section('title', 'تفاصيل الطلب')

@section('content')
@php
$latestLog = $order->locationLogs->first();
@endphp

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 fw-bold mb-0">تفاصيل الطلب #{{ $order->id }}</h1>
        <a href="{{ route('distributor.orders.index') }}" class="btn btn-outline-secondary">رجوع</a>
    </div>

    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div>العميل: {{ $order->customer?->name ?? ($order->consumer?->name ?? $order->customer_name) }}</div>
            <div>الهاتف: {{ $order->customer?->phone ?? ($order->consumer?->phone ?? $order->customer_phone) }}</div>
            <div>العنوان: {{ $order->customer?->address ?? ($order->consumer?->address ?? $order->customer_address) }}
            </div>
            <div>حالة الطلب: {{ \App\Support\StatusLabel::order($order->status) }}</div>
            <div>مرحلة المندوب:
                {{ \App\Support\StatusLabel::distributorStage($order->distributor_stage ?: 'assigned') }}
            </div>
            <div>ملاحظات الطلب: {{ $order->notes ?? '-' }}</div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('distributor.orders.status', $order) }}"
                enctype="multipart/form-data" class="row g-2 align-items-end">
                @csrf
                @method('PATCH')
                <div class="col-md-3">
                    <label class="form-label mb-1">تحديث الحالة</label>
                    <select name="status" class="form-select">
                        <option value="assigned">مُسند</option>
                        <option value="accepted">تم القبول</option>
                        <option value="picked_up">تم الاستلام</option>
                        <option value="out_for_delivery">خرج للتوصيل</option>
                        <option value="delivered">تم التسليم</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label mb-1">ملاحظة</label>
                    <input type="text" name="note" class="form-control" placeholder="ملاحظة التحديث (اختياري)">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">توقيع المستلم</label>
                    <input type="text" name="delivery_signature" class="form-control" placeholder="الاسم/التوقيع">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">صورة التسليم</label>
                    <input type="file" name="delivery_proof_image" accept="image/*" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">ترتيب المسار</label>
                    <input type="number" min="1" name="route_sequence" class="form-control" placeholder="#">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100">تحديث الحالة</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">إرسال الموقع (التتبع)</h2>
            <form method="POST" action="{{ route('distributor.orders.location', $order) }}"
                class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label mb-1">خط العرض</label>
                    <input id="latitude-input" type="number" step="0.0000001" name="latitude" class="form-control"
                        required>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">خط الطول</label>
                    <input id="longitude-input" type="number" step="0.0000001" name="longitude" class="form-control"
                        required>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">الدقة (متر)</label>
                    <input id="accuracy-input" type="number" step="0.1" name="accuracy_meters"
                        class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">ملاحظة</label>
                    <input id="note-input" type="text" name="note" class="form-control"
                        placeholder="موقع العميل/ملاحظة">
                </div>
                <div class="col-md-1">
                    <button class="btn btn-outline-primary w-100">إرسال</button>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2 align-items-center mt-2">
                    <button type="button" id="capture-location-btn" class="btn btn-sm btn-dark">التقاط موقعي
                        تلقائيا</button>
                    <select id="live-interval" class="form-select form-select-sm" style="max-width:170px;">
                        <option value="15">تحديث كل 15 ثانية</option>
                        <option value="30" selected>تحديث كل 30 ثانية</option>
                        <option value="60">تحديث كل 60 ثانية</option>
                    </select>
                    <button type="button" id="start-live-btn" class="btn btn-sm btn-success">بدء التتبع الحي</button>
                    <button type="button" id="stop-live-btn" class="btn btn-sm btn-outline-danger" disabled>إيقاف
                        التتبع</button>
                    <span id="location-status" class="small text-muted">لم يتم التقاط الموقع بعد.</span>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">الخريطة المصغرة (آخر موقع)</h2>
            @if ($latestLog)
            <div class="ratio ratio-16x9 rounded overflow-hidden border mb-2">
                <iframe
                    src="https://www.openstreetmap.org/export/embed.html?bbox={{ $latestLog->longitude - 0.01 }},{{ $latestLog->latitude - 0.01 }},{{ $latestLog->longitude + 0.01 }},{{ $latestLog->latitude + 0.01 }}&marker={{ $latestLog->latitude }},{{ $latestLog->longitude }}"
                    style="border:0;" loading="lazy"></iframe>
            </div>
            <a href="https://www.google.com/maps?q={{ $latestLog->latitude }},{{ $latestLog->longitude }}"
                target="_blank" class="btn btn-sm btn-outline-secondary">
                فتح على الخريطة
            </a>
            @else
            <div class="text-muted">لا توجد نقطة تتبع بعد لعرضها على الخريطة.</div>
            @endif
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">تسلسل التنفيذ</h2>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>المرحلة</th>
                            <th>الملاحظة</th>
                            <th>الوقت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->distributorEvents as $event)
                        <tr>
                            <td>{{ $event->stage }}</td>
                            <td>{{ $event->note ?: '-' }}</td>
                            <td>{{ $event->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">لا يوجد تحديثات مراحل بعد.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>المنتج</th>
                            <th>الكمية</th>
                            <th>ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                        <tr>
                            <td>{{ $item->product?->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->notes ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">مزامنة غير متصلة (JSON)</h2>
            <form id="offline-sync-form" class="mb-3">
                <textarea id="offline-events-json" class="form-control" rows="4"
                    placeholder='{"events":[{"type":"location","order_id":123,"latitude":15.3,"longitude":44.2},{"type":"status_update","order_id":123,"stage":"delivered","delivery_signature":"Ali"}]}'></textarea>
                <div class="d-flex gap-2 mt-2">
                    <button type="button" id="offline-sync-btn" class="btn btn-sm btn-outline-dark">إرسال المزامنة</button>
                    <span id="offline-sync-status" class="small text-muted align-self-center">جاهز للإرسال.</span>
                </div>
            </form>

            <h2 class="h6 fw-bold mb-3">آخر نقاط التتبع</h2>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>خط العرض</th>
                            <th>خط الطول</th>
                            <th>الدقة</th>
                            <th>ملاحظة</th>
                            <th>الوقت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->locationLogs as $log)
                        <tr>
                            <td>{{ $log->latitude }}</td>
                            <td>{{ $log->longitude }}</td>
                            <td>{{ $log->accuracy_meters ?? '-' }}</td>
                            <td>{{ $log->note ?: '-' }}</td>
                            <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">لا توجد نقاط تتبع حتى الآن.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const captureBtn = document.getElementById('capture-location-btn');
        const startLiveBtn = document.getElementById('start-live-btn');
        const stopLiveBtn = document.getElementById('stop-live-btn');
        const intervalSelect = document.getElementById('live-interval');
        const statusEl = document.getElementById('location-status');
        const latInput = document.getElementById('latitude-input');
        const lngInput = document.getElementById('longitude-input');
        const accuracyInput = document.getElementById('accuracy-input');
        const noteInput = document.getElementById('note-input');
        const locationForm = captureBtn?.closest('form');
        const locationEndpoint = locationForm?.getAttribute('action') || '';
        const csrfToken = '{{ csrf_token() }}';
        const currentStage = '{{ $order->distributor_stage ?: "assigned" }}';
        const offlineSyncBtn = document.getElementById('offline-sync-btn');
        const offlineSyncStatus = document.getElementById('offline-sync-status');
        const offlineSyncJson = document.getElementById('offline-events-json');
        const offlineSyncEndpoint = '{{ route("distributor.orders.offline-sync") }}';

        let liveTimer = null;
        let isSending = false;

        if (!captureBtn || !statusEl || !latInput || !lngInput || !locationEndpoint) {
            return;
        }

        const setStatus = (message, isError = false) => {
            statusEl.textContent = message;
            statusEl.classList.toggle('text-danger', isError);
            statusEl.classList.toggle('text-success', !isError);
            statusEl.classList.toggle('text-muted', false);
        };

        const capture = (onSuccess) => {
            if (!navigator.geolocation) {
                setStatus('المتصفح لا يدعم GPS.', true);
                return;
            }

            setStatus('جاري التقاط الموقع...');

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    latInput.value = Number(position.coords.latitude).toFixed(7);
                    lngInput.value = Number(position.coords.longitude).toFixed(7);

                    if (accuracyInput && position.coords.accuracy !== null) {
                        accuracyInput.value = Number(position.coords.accuracy).toFixed(1);
                    }

                    if (noteInput && !noteInput.value) {
                        noteInput.value = 'تم التقاط الموقع تلقائيا من الجهاز';
                    }

                    setStatus('تم التقاط الموقع بنجاح.');
                    if (typeof onSuccess === 'function') {
                        onSuccess();
                    }
                },
                (error) => {
                    const map = {
                        1: 'تم رفض إذن الموقع من المستخدم.',
                        2: 'تعذر تحديد الموقع حاليا.',
                        3: 'انتهت مهلة التقاط الموقع.',
                    };
                    setStatus(map[error.code] || 'حدث خطأ أثناء التقاط الموقع.', true);
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0,
                }
            );
        };

        const sendLocation = async () => {
            if (isSending) {
                return;
            }

            isSending = true;
            setStatus('جاري إرسال التتبع الحي...');

            try {
                const payload = new FormData();
                payload.append('latitude', latInput.value);
                payload.append('longitude', lngInput.value);
                if (accuracyInput && accuracyInput.value !== '') {
                    payload.append('accuracy_meters', accuracyInput.value);
                }
                if (noteInput && noteInput.value !== '') {
                    payload.append('note', noteInput.value + ' (تتبع حي)');
                }

                const response = await fetch(locationEndpoint, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: payload,
                });

                if (!response.ok) {
                    throw new Error('تعذر إرسال التتبع الحي.');
                }

                setStatus('تم إرسال الموقع الحي بنجاح.');
            } catch (error) {
                setStatus(error?.message || 'حدث خطأ أثناء إرسال التتبع.', true);
            } finally {
                isSending = false;
            }
        };

        const startLive = () => {
            if (currentStage !== 'out_for_delivery') {
                setStatus('يعمل التتبع الحي عند مرحلة out_for_delivery فقط.', true);
                return;
            }

            if (liveTimer) {
                clearInterval(liveTimer);
                liveTimer = null;
            }

            const seconds = Number(intervalSelect?.value || 30);
            const intervalMs = Math.max(10, seconds) * 1000;

            const captureAndSend = () => capture(sendLocation);
            captureAndSend();
            liveTimer = setInterval(captureAndSend, intervalMs);

            startLiveBtn.disabled = true;
            stopLiveBtn.disabled = false;
            setStatus('تم بدء التتبع الحي.');
        };

        const stopLive = () => {
            if (liveTimer) {
                clearInterval(liveTimer);
                liveTimer = null;
            }

            startLiveBtn.disabled = false;
            stopLiveBtn.disabled = true;
            setStatus('تم إيقاف التتبع الحي.');
        };

        captureBtn.addEventListener('click', capture);

        startLiveBtn?.addEventListener('click', startLive);
        stopLiveBtn?.addEventListener('click', stopLive);

        window.addEventListener('beforeunload', () => {
            if (liveTimer) {
                clearInterval(liveTimer);
            }
        });

        offlineSyncBtn?.addEventListener('click', async () => {
            if (!offlineSyncJson || !offlineSyncStatus) {
                return;
            }

            let payload;
            try {
                payload = JSON.parse(offlineSyncJson.value || '{}');
            } catch (e) {
                offlineSyncStatus.textContent = 'JSON غير صالح.';
                offlineSyncStatus.classList.add('text-danger');
                return;
            }

            offlineSyncStatus.textContent = 'جاري الإرسال...';
            offlineSyncStatus.classList.remove('text-danger');

            try {
                const response = await fetch(offlineSyncEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                });

                if (!response.ok) {
                    throw new Error('تعذر مزامنة الأحداث غير المتصلة.');
                }

                const result = await response.json();
                offlineSyncStatus.textContent = 'تمت المزامنة: ' + (result.processed || 0) +
                    '، تم التخطي: ' + (result.skipped || 0);
            } catch (e) {
                offlineSyncStatus.textContent = e?.message || 'حدث خطأ أثناء المزامنة.';
                offlineSyncStatus.classList.add('text-danger');
            }
        });
    })();
</script>
@endsection