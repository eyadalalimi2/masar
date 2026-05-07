<?php

namespace App\Http\Requests\Supplier;

use App\Support\Validation\UniqueUserContact;
use App\Support\WorkingHoursCodec;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class SupplierRegisterRequest extends FormRequest
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
            'owner_name' => ['required', 'string', 'max:255'],
            'branch_manager_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', new UniqueUserContact('email')],
            'phone' => ['required', 'string', 'max:20', new UniqueUserContact('phone')],
            'whatsapp' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', Password::min(6)],
            'business_name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'agent_image' => ['nullable', 'image', 'max:4096'],
            'branch_manager_image' => ['nullable', 'image', 'max:4096'],
            'branch_manager_password' => ['nullable', 'string', Password::min(6)],
            'gps_location' => ['required', 'string', 'max:255', 'regex:/^\s*-?\d{1,2}(?:\.\d+)?\s*,\s*-?\d{1,3}(?:\.\d+)?\s*$/'],
            'working_hours' => ['required'],
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
        $payload = [];

        if ($this->has('gps_location')) {
            $normalized = $this->normalizeGpsLocation($this->input('gps_location'));
            if ($normalized !== null) {
                $payload['gps_location'] = $normalized;
            }
        }

        if ($this->has('working_hours')) {
            $payload['working_hours'] = $this->normalizeWorkingHours($this->input('working_hours'));
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $location = $this->input('gps_location');
            if (is_string($location) && str_contains($location, ',')) {
                [$lat, $lng] = array_map('trim', explode(',', $location, 2));

                if (is_numeric($lat) && is_numeric($lng)) {
                    $latValue = (float) $lat;
                    $lngValue = (float) $lng;

                    if ($latValue < -90 || $latValue > 90 || $lngValue < -180 || $lngValue > 180) {
                        $validator->errors()->add('gps_location', 'قيمة الموقع يجب أن تكون إحداثيات صحيحة بصيغة latitude,longitude.');
                    }
                }
            }

            $hours = $this->input('working_hours');
            if (! is_array($hours)) {
                return;
            }

            $hasEnabledDay = false;

            foreach (self::WEEK_DAYS as $day) {
                $enabled = filter_var(data_get($hours, $day . '.enabled'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
                if (! $enabled) {
                    continue;
                }

                $hasEnabledDay = true;
                $start = data_get($hours, $day . '.start');
                $end = data_get($hours, $day . '.end');

                if (! is_string($start) || ! is_string($end)) {
                    continue;
                }

                if (strcmp($start, $end) >= 0) {
                    $validator->errors()->add('working_hours.' . $day . '.end', 'وقت انتهاء الدوام يجب أن يكون بعد وقت البداية.');
                }
            }

            if (! $hasEnabledDay) {
                $validator->errors()->add('working_hours', 'يجب تحديد يوم دوام واحد على الأقل.');
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

    public function attributes(): array
    {
        return [
            'owner_name' => 'اسم المالك',
            'branch_manager_name' => 'اسم مدير الفرع',
            'email' => 'البريد الإلكتروني',
            'phone' => 'رقم الهاتف',
            'whatsapp' => 'رقم واتساب',
            'password' => 'كلمة المرور',
            'business_name' => 'الاسم التجاري',
            'logo' => 'الشعار',
            'agent_image' => 'صورة الوكيل',
            'branch_manager_image' => 'صورة مدير الفرع',
            'branch_manager_password' => 'كلمة مرور مدير الفرع',
            'gps_location' => 'الموقع',
            'working_hours' => 'أوقات الدوام',
        ];
    }

    public function messages(): array
    {
        return [
            'owner_name.required' => 'حقل :attribute مطلوب.',
            'email.email' => 'حقل :attribute يجب أن يكون بريداً إلكترونياً صحيحاً.',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقًا.',
            'phone.required' => 'حقل :attribute مطلوب.',
            'phone.unique' => 'رقم الهاتف مستخدم مسبقًا.',
            'whatsapp.required' => 'حقل :attribute مطلوب.',
            'password.required' => 'حقل :attribute مطلوب.',
            'password.min' => 'حقل :attribute يجب ألا يقل عن :min أحرف.',
            'business_name.required' => 'حقل :attribute مطلوب.',
            'logo.image' => 'حقل :attribute يجب أن يكون صورة.',
            'logo.max' => 'حجم :attribute يجب ألا يتجاوز :max كيلوبايت.',
            'agent_image.image' => 'حقل :attribute يجب أن يكون صورة.',
            'agent_image.max' => 'حجم :attribute يجب ألا يتجاوز :max كيلوبايت.',
            'branch_manager_name.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'branch_manager_image.image' => 'حقل :attribute يجب أن يكون صورة.',
            'branch_manager_image.max' => 'حجم :attribute يجب ألا يتجاوز :max كيلوبايت.',
            'branch_manager_password.min' => 'حقل :attribute يجب ألا يقل عن :min أحرف.',
            'gps_location.required' => 'حقل :attribute مطلوب.',
            'gps_location.regex' => 'حقل :attribute يجب أن يكون بصيغة latitude,longitude.',
            'working_hours.required' => 'حقل :attribute مطلوب.',
            'working_hours.*.enabled.required' => 'حقل :attribute مطلوب.',
            'working_hours.*.enabled.boolean' => 'حقل :attribute يجب أن يكون صحيحًا أو خطأ.',
            'working_hours.*.start.required_if' => 'حقل :attribute مطلوب عند تفعيل اليوم.',
            'working_hours.*.start.date_format' => 'حقل :attribute يجب أن يكون بصيغة ساعة صحيحة HH:MM.',
            'working_hours.*.end.required_if' => 'حقل :attribute مطلوب عند تفعيل اليوم.',
            'working_hours.*.end.date_format' => 'حقل :attribute يجب أن يكون بصيغة ساعة صحيحة HH:MM.',
        ];
    }

    private function normalizeGpsLocation(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $parts = array_map('trim', explode(',', $value, 2));
        if (count($parts) !== 2 || ! is_numeric($parts[0]) || ! is_numeric($parts[1])) {
            return trim($value) !== '' ? trim($value) : null;
        }

        return sprintf('%.6f,%.6f', (float) $parts[0], (float) $parts[1]);
    }

    private function normalizeWorkingHours(mixed $value): array
    {
        if (is_string($value)) {
            return WorkingHoursCodec::decode($value);
        }

        return WorkingHoursCodec::normalize(is_array($value) ? $value : []);
    }
}
