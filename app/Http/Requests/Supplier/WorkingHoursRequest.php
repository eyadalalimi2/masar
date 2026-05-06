<?php

namespace App\Http\Requests\Supplier;

use App\Support\WorkingHoursCodec;
use Illuminate\Foundation\Http\FormRequest;

class WorkingHoursRequest extends FormRequest
{
    private const WEEK_DAYS = [
        'saturday',
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'working_hours' => ['required', 'array'],
            'working_hours.saturday.enabled' => ['required', 'boolean'],
            'working_hours.saturday.start' => ['nullable', 'date_format:H:i', 'required_if:working_hours.saturday.enabled,1'],
            'working_hours.saturday.end' => ['nullable', 'date_format:H:i', 'required_if:working_hours.saturday.enabled,1'],
            'working_hours.sunday.enabled' => ['required', 'boolean'],
            'working_hours.sunday.start' => ['nullable', 'date_format:H:i', 'required_if:working_hours.sunday.enabled,1'],
            'working_hours.sunday.end' => ['nullable', 'date_format:H:i', 'required_if:working_hours.sunday.enabled,1'],
            'working_hours.monday.enabled' => ['required', 'boolean'],
            'working_hours.monday.start' => ['nullable', 'date_format:H:i', 'required_if:working_hours.monday.enabled,1'],
            'working_hours.monday.end' => ['nullable', 'date_format:H:i', 'required_if:working_hours.monday.enabled,1'],
            'working_hours.tuesday.enabled' => ['required', 'boolean'],
            'working_hours.tuesday.start' => ['nullable', 'date_format:H:i', 'required_if:working_hours.tuesday.enabled,1'],
            'working_hours.tuesday.end' => ['nullable', 'date_format:H:i', 'required_if:working_hours.tuesday.enabled,1'],
            'working_hours.wednesday.enabled' => ['required', 'boolean'],
            'working_hours.wednesday.start' => ['nullable', 'date_format:H:i', 'required_if:working_hours.wednesday.enabled,1'],
            'working_hours.wednesday.end' => ['nullable', 'date_format:H:i', 'required_if:working_hours.wednesday.enabled,1'],
            'working_hours.thursday.enabled' => ['required', 'boolean'],
            'working_hours.thursday.start' => ['nullable', 'date_format:H:i', 'required_if:working_hours.thursday.enabled,1'],
            'working_hours.thursday.end' => ['nullable', 'date_format:H:i', 'required_if:working_hours.thursday.enabled,1'],
            'working_hours.friday.enabled' => ['required', 'boolean'],
            'working_hours.friday.start' => ['nullable', 'date_format:H:i', 'required_if:working_hours.friday.enabled,1'],
            'working_hours.friday.end' => ['nullable', 'date_format:H:i', 'required_if:working_hours.friday.enabled,1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('working_hours')) {
            return;
        }

        $this->merge([
            'working_hours' => $this->normalizeWorkingHours($this->input('working_hours')),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hours = $this->input('working_hours');
            if (! is_array($hours)) {
                return;
            }

            foreach (self::WEEK_DAYS as $day) {
                $enabled = filter_var(data_get($hours, $day . '.enabled'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
                if (! $enabled) {
                    continue;
                }

                $start = data_get($hours, $day . '.start');
                $end = data_get($hours, $day . '.end');

                if (! is_string($start) || ! is_string($end)) {
                    continue;
                }

                if (strcmp($start, $end) >= 0) {
                    $validator->errors()->add('working_hours.' . $day . '.end', 'وقت انتهاء الدوام يجب أن يكون بعد وقت البداية.');
                }
            }
        });
    }

    protected function passedValidation(): void
    {
        $schedule = $this->normalizeWorkingHours($this->input('working_hours'));

        $this->merge([
            'working_hours' => WorkingHoursCodec::encode($schedule),
        ]);
    }

    private function normalizeWorkingHours(mixed $value): array
    {
        if (is_string($value)) {
            return WorkingHoursCodec::decode($value);
        }

        return WorkingHoursCodec::normalize(is_array($value) ? $value : []);
    }
}
