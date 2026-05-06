<?php

namespace App\Support;

class WorkingHoursCodec
{
    public const WEEK_DAYS = [
        'saturday',
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
    ];

    public static function defaultSchedule(): array
    {
        $default = [];

        foreach (self::WEEK_DAYS as $day) {
            $default[$day] = [
                'enabled' => true,
                'start' => '08:00',
                'end' => '22:00',
            ];
        }

        return $default;
    }

    public static function encode(array $schedule): string
    {
        $normalized = self::normalize($schedule);
        $segments = [];

        foreach (self::WEEK_DAYS as $day) {
            $item = $normalized[$day] ?? ['enabled' => false, 'start' => null, 'end' => null];
            // Use a delimiter that does not conflict with time values (HH:MM).
            $segments[] = implode('|', [
                $day,
                $item['enabled'] ? '1' : '0',
                $item['start'] ?? '',
                $item['end'] ?? '',
            ]);
        }

        return implode(';', $segments);
    }

    public static function decode(mixed $value): array
    {
        if (is_array($value)) {
            return self::normalize($value['days'] ?? $value);
        }

        if (! is_string($value) || trim($value) === '') {
            return self::defaultSchedule();
        }

        $trimmed = trim($value);

        // Backward compatibility for old JSON values already in database.
        if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                return self::normalize($decoded['days'] ?? $decoded);
            }
        }

        $result = self::defaultSchedule();
        $segments = array_filter(array_map('trim', explode(';', $trimmed)));

        foreach ($segments as $segment) {
            $parsed = self::parseSegment($segment);
            if ($parsed === null) {
                continue;
            }

            $day = $parsed['day'];
            if (! in_array($day, self::WEEK_DAYS, true)) {
                continue;
            }

            $enabled = $parsed['enabled'];
            $start = $parsed['start'];
            $end = $parsed['end'];

            $result[$day] = [
                'enabled' => $enabled,
                'start' => $enabled && $start !== '' ? $start : null,
                'end' => $enabled && $end !== '' ? $end : null,
            ];
        }

        return $result;
    }

    public static function normalize(mixed $value): array
    {
        $raw = is_array($value) ? $value : [];
        $normalized = [];

        foreach (self::WEEK_DAYS as $day) {
            $dayData = $raw[$day] ?? [];
            $enabled = filter_var(data_get($dayData, 'enabled'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            $start = data_get($dayData, 'start');
            $end = data_get($dayData, 'end');

            $normalized[$day] = [
                'enabled' => $enabled,
                'start' => $enabled && is_string($start) ? trim($start) : null,
                'end' => $enabled && is_string($end) ? trim($end) : null,
            ];
        }

        return $normalized;
    }

    private static function parseSegment(string $segment): ?array
    {
        $segment = trim($segment);
        if ($segment === '') {
            return null;
        }

        // New safe format: day|enabled|start|end
        if (str_contains($segment, '|')) {
            $parts = array_map('trim', explode('|', $segment, 4));

            return [
                'day' => (string) ($parts[0] ?? ''),
                'enabled' => ((string) ($parts[1] ?? '0')) === '1',
                'start' => (string) ($parts[2] ?? ''),
                'end' => (string) ($parts[3] ?? ''),
            ];
        }

        // Legacy format support: day:enabled:start:end where start/end may contain ':'
        $first = strpos($segment, ':');
        if ($first === false) {
            return null;
        }

        $second = strpos($segment, ':', $first + 1);
        if ($second === false) {
            return null;
        }

        $day = trim(substr($segment, 0, $first));
        $enabledRaw = trim(substr($segment, $first + 1, $second - $first - 1));
        $rest = trim(substr($segment, $second + 1));

        $start = '';
        $end = '';

        if (preg_match('/^(\d{1,2}:\d{2})?:(\d{1,2}:\d{2})?$/', $rest, $matches) === 1) {
            $start = trim((string) ($matches[1] ?? ''));
            $end = trim((string) ($matches[2] ?? ''));
        } else {
            $parts = explode(':', $rest);
            if (count($parts) >= 2) {
                $start = trim($parts[0] . ':' . $parts[1]);
            }
            if (count($parts) >= 4) {
                $end = trim($parts[2] . ':' . $parts[3]);
            }
        }

        return [
            'day' => $day,
            'enabled' => $enabledRaw === '1',
            'start' => $start,
            'end' => $end,
        ];
    }
}
