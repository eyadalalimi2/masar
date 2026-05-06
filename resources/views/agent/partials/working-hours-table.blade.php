@php
$weekDays = [
'saturday' => 'السبت',
'sunday' => 'الأحد',
'monday' => 'الاثنين',
'tuesday' => 'الثلاثاء',
'wednesday' => 'الأربعاء',
'thursday' => 'الخميس',
'friday' => 'الجمعة',
];

$baseSchedule = [];
foreach ($weekDays as $dayKey => $dayLabel) {
$baseSchedule[$dayKey] = [
'enabled' => true,
'start' => '08:00',
'end' => '22:00',
];
}

$source = old('working_hours', $workingHours ?? null);
$decoded = \App\Support\WorkingHoursCodec::decode($source);

if (is_array($decoded)) {
foreach ($weekDays as $dayKey => $dayLabel) {
if (!isset($decoded[$dayKey]) || !is_array($decoded[$dayKey])) {
continue;
}

$baseSchedule[$dayKey]['enabled'] =
filter_var(data_get($decoded[$dayKey], 'enabled'), FILTER_VALIDATE_BOOLEAN) ?? false;

$start = data_get($decoded[$dayKey], 'start');
$end = data_get($decoded[$dayKey], 'end');

if (is_string($start) && trim($start) !== '') {
$baseSchedule[$dayKey]['start'] = trim($start);
}

if (is_string($end) && trim($end) !== '') {
$baseSchedule[$dayKey]['end'] = trim($end);
}
}
}
@endphp

<label class="form-label">أوقات الدوام</label>
<div class="table-responsive border rounded">
    <table class="table table-sm align-middle mb-0" id="workingHoursTable">
        <thead class="table-light">
            <tr>
                <th>اليوم</th>
                <th>تفعيل</th>
                <th>بداية الدوام</th>
                <th>نهاية الدوام</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($weekDays as $dayKey => $dayLabel)
            @php($isEnabled = (bool) data_get($baseSchedule, $dayKey . '.enabled', false))
            <tr>
                <td class="fw-semibold">{{ $dayLabel }}</td>
                <td>
                    <input type="hidden" name="working_hours[{{ $dayKey }}][enabled]" value="0">
                    <div class="form-check form-switch">
                        <input class="form-check-input working-day-toggle" type="checkbox"
                            name="working_hours[{{ $dayKey }}][enabled]" value="1"
                            data-day="{{ $dayKey }}" {{ $isEnabled ? 'checked' : '' }}>
                    </div>
                </td>
                <td>
                    <input type="time" class="form-control form-control-sm working-day-time"
                        name="working_hours[{{ $dayKey }}][start]"
                        value="{{ data_get($baseSchedule, $dayKey . '.start') }}" data-day="{{ $dayKey }}"
                        data-type="start" {{ $isEnabled ? '' : 'disabled' }}>
                </td>
                <td>
                    <input type="time" class="form-control form-control-sm working-day-time"
                        name="working_hours[{{ $dayKey }}][end]"
                        value="{{ data_get($baseSchedule, $dayKey . '.end') }}" data-day="{{ $dayKey }}"
                        data-type="end" {{ $isEnabled ? '' : 'disabled' }}>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<small class="text-muted d-block mt-1">فعّل الأيام التي يعمل فيها الوكيل، ثم حدّد وقت البداية والنهاية لكل
    يوم.</small>

<script>
    (() => {
        const table = document.getElementById('workingHoursTable');
        if (!table) {
            return;
        }

        function updateDayState(dayKey, enabled) {
            const startInput = table.querySelector(`input[data-day="${dayKey}"][data-type="start"]`);
            const endInput = table.querySelector(`input[data-day="${dayKey}"][data-type="end"]`);

            if (!startInput || !endInput) {
                return;
            }

            startInput.disabled = !enabled;
            endInput.disabled = !enabled;
            startInput.required = enabled;
            endInput.required = enabled;
        }

        const toggles = table.querySelectorAll('.working-day-toggle');
        toggles.forEach((toggle) => {
            updateDayState(toggle.dataset.day, toggle.checked);

            toggle.addEventListener('change', (event) => {
                updateDayState(toggle.dataset.day, event.target.checked);
            });
        });
    })();
</script>