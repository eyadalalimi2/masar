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

    $schedule = is_array($schedule ?? null) ? $schedule : [];
@endphp

<div class="table-responsive border rounded mt-2">
    <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>اليوم</th>
                <th>الحالة</th>
                <th>بداية الدوام</th>
                <th>نهاية الدوام</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($weekDays as $dayKey => $dayLabel)
                @php
                    $dayData = $schedule[$dayKey] ?? [
                        'enabled' => false,
                        'start' => null,
                        'end' => null,
                    ];
                    $enabled = (bool) data_get($dayData, 'enabled', false);
                @endphp
                <tr>
                    <td class="fw-semibold">{{ $dayLabel }}</td>
                    <td>
                        @if ($enabled)
                            <span class="badge text-bg-success">مفعّل</span>
                        @else
                            <span class="badge text-bg-secondary">معطّل</span>
                        @endif
                    </td>
                    <td>{{ data_get($dayData, 'start') ?: '-' }}</td>
                    <td>{{ data_get($dayData, 'end') ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
