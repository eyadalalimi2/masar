<?php

namespace App\Http\Requests\Supplier;

use App\Support\WorkingHoursCodec;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
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
        $routeSupplier = $this->route('supplier');
        $supplierId = is_object($routeSupplier) && isset($routeSupplier->id) ? (int) $routeSupplier->id : null;

        $emailUniqueRule = Rule::unique('agents', 'email');
        $supplierEmailUniqueRule = Rule::unique('suppliers', 'email')->ignore($supplierId);
        $supplierPhoneUniqueRule = Rule::unique('suppliers', 'phone')->ignore($supplierId);

        if (Auth::guard('agent')->check()) {
            $emailUniqueRule = $emailUniqueRule->ignore((int) Auth::guard('agent')->id());
        }

        return [
            'logo' => ['nullable', 'image', 'max:2048'],
            'agent_image' => ['nullable', 'image', 'max:4096'],
            'branch_manager_image' => ['nullable', 'image', 'max:4096'],
            'owner_name' => ['required', 'string', 'max:255'],
            'branch_manager_name' => ['nullable', 'string', 'max:255'],
            'branch_manager_password' => ['nullable', 'string', 'min:6', 'max:255'],
            'business_name' => ['required', 'string', 'max:255'],
            'commercial_reg_number' => ['required', 'string', 'max:255'],
            'commercial_reg_image' => ['nullable', 'image', 'max:4096'],
            'license_number' => ['required', 'string', 'max:255'],
            'license_image' => ['nullable', 'image', 'max:4096'],
            'national_id_number' => ['required', 'string', 'max:255'],
            'national_id_image' => ['nullable', 'image', 'max:4096'],
            'phone' => ['required', 'string', 'max:20', $supplierPhoneUniqueRule],
            'whatsapp' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'gps_location' => ['required', 'string', 'max:255', 'regex:/^\s*-?\d{1,2}(?:\.\d+)?\s*,\s*-?\d{1,3}(?:\.\d+)?\s*$/'],
            'email' => ['nullable', 'email', 'max:255', $emailUniqueRule, $supplierEmailUniqueRule],
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

    public function attributes(): array
    {
        return [
            'logo' => 'الشعار',
            'agent_image' => 'صورة الوكيل',
            'branch_manager_image' => 'صورة مدير الفرع',
            'owner_name' => 'اسم المالك',
            'branch_manager_name' => 'اسم مدير الفرع',
            'branch_manager_password' => 'كلمة مرور مدير الفرع',
            'business_name' => 'الاسم التجاري',
            'commercial_reg_number' => 'رقم السجل التجاري',
            'commercial_reg_image' => 'صورة السجل التجاري',
            'license_number' => 'رقم الرخصة',
            'license_image' => 'صورة الرخصة',
            'national_id_number' => 'رقم البطاقة الشخصية',
            'national_id_image' => 'صورة البطاقة الشخصية',
            'phone' => 'رقم الهاتف',
            'whatsapp' => 'واتساب',
            'address' => 'العنوان',
            'gps_location' => 'الموقع',
            'email' => 'البريد الإلكتروني',
            'working_hours' => 'أوقات الدوام',
            'working_hours.saturday.enabled' => 'تفعيل يوم السبت',
            'working_hours.saturday.start' => 'وقت بداية دوام السبت',
            'working_hours.saturday.end' => 'وقت نهاية دوام السبت',
            'working_hours.sunday.enabled' => 'تفعيل يوم الأحد',
            'working_hours.sunday.start' => 'وقت بداية دوام الأحد',
            'working_hours.sunday.end' => 'وقت نهاية دوام الأحد',
            'working_hours.monday.enabled' => 'تفعيل يوم الاثنين',
            'working_hours.monday.start' => 'وقت بداية دوام الاثنين',
            'working_hours.monday.end' => 'وقت نهاية دوام الاثنين',
            'working_hours.tuesday.enabled' => 'تفعيل يوم الثلاثاء',
            'working_hours.tuesday.start' => 'وقت بداية دوام الثلاثاء',
            'working_hours.tuesday.end' => 'وقت نهاية دوام الثلاثاء',
            'working_hours.wednesday.enabled' => 'تفعيل يوم الأربعاء',
            'working_hours.wednesday.start' => 'وقت بداية دوام الأربعاء',
            'working_hours.wednesday.end' => 'وقت نهاية دوام الأربعاء',
            'working_hours.thursday.enabled' => 'تفعيل يوم الخميس',
            'working_hours.thursday.start' => 'وقت بداية دوام الخميس',
            'working_hours.thursday.end' => 'وقت نهاية دوام الخميس',
            'working_hours.friday.enabled' => 'تفعيل يوم الجمعة',
            'working_hours.friday.start' => 'وقت بداية دوام الجمعة',
            'working_hours.friday.end' => 'وقت نهاية دوام الجمعة',
        ];
    }

    public function messages(): array
    {
        return [
            'logo.image' => 'حقل :attribute يجب أن يكون صورة.',
            'logo.max' => 'حجم :attribute يجب ألا يتجاوز :max كيلوبايت.',
            'agent_image.image' => 'حقل :attribute يجب أن يكون صورة.',
            'agent_image.max' => 'حجم :attribute يجب ألا يتجاوز :max كيلوبايت.',
            'branch_manager_image.image' => 'حقل :attribute يجب أن يكون صورة.',
            'branch_manager_image.max' => 'حجم :attribute يجب ألا يتجاوز :max كيلوبايت.',
            'owner_name.required' => 'حقل :attribute مطلوب.',
            'owner_name.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'owner_name.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'branch_manager_name.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'branch_manager_name.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'branch_manager_password.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'branch_manager_password.min' => 'حقل :attribute يجب ألا يقل عن :min أحرف.',
            'branch_manager_password.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'business_name.required' => 'حقل :attribute مطلوب.',
            'business_name.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'business_name.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'commercial_reg_number.required' => 'حقل :attribute مطلوب.',
            'commercial_reg_number.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'commercial_reg_number.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'commercial_reg_image.image' => 'حقل :attribute يجب أن يكون صورة.',
            'commercial_reg_image.max' => 'حجم :attribute يجب ألا يتجاوز :max كيلوبايت.',
            'license_number.required' => 'حقل :attribute مطلوب.',
            'license_number.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'license_number.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'license_image.image' => 'حقل :attribute يجب أن يكون صورة.',
            'license_image.max' => 'حجم :attribute يجب ألا يتجاوز :max كيلوبايت.',
            'national_id_number.required' => 'حقل :attribute مطلوب.',
            'national_id_number.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'national_id_number.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'national_id_image.image' => 'حقل :attribute يجب أن يكون صورة.',
            'national_id_image.max' => 'حجم :attribute يجب ألا يتجاوز :max كيلوبايت.',
            'phone.required' => 'حقل :attribute مطلوب.',
            'phone.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'phone.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'phone.unique' => 'رقم الهاتف مستخدم مسبقًا.',
            'whatsapp.required' => 'حقل :attribute مطلوب.',
            'whatsapp.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'whatsapp.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'address.required' => 'حقل :attribute مطلوب.',
            'address.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'address.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'gps_location.required' => 'حقل :attribute مطلوب.',
            'gps_location.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'gps_location.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'gps_location.regex' => 'حقل :attribute يجب أن يكون بصيغة latitude,longitude.',
            'email.email' => 'حقل :attribute يجب أن يكون بريداً إلكترونياً صحيحاً.',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقًا.',
            'email.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'working_hours.required' => 'حقل :attribute مطلوب.',
            'working_hours.array' => 'حقل :attribute غير صالح.',
            'working_hours.*.enabled.required' => 'حقل :attribute مطلوب.',
            'working_hours.*.enabled.boolean' => 'حقل :attribute يجب أن يكون صحيحًا أو خطأ.',
            'working_hours.*.start.required_if' => 'حقل :attribute مطلوب عند تفعيل اليوم.',
            'working_hours.*.start.date_format' => 'حقل :attribute يجب أن يكون بصيغة ساعة صحيحة HH:MM.',
            'working_hours.*.end.required_if' => 'حقل :attribute مطلوب عند تفعيل اليوم.',
            'working_hours.*.end.date_format' => 'حقل :attribute يجب أن يكون بصيغة ساعة صحيحة HH:MM.',
        ];
    }

    private function normalizeWorkingHours(mixed $value): array
    {
        if (is_string($value)) {
            return WorkingHoursCodec::decode($value);
        }

        return WorkingHoursCodec::normalize(is_array($value) ? $value : []);
    }

    private function normalizeGpsLocation(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $clean = trim(str_replace('،', ',', $value));
        if ($clean === '' || ! str_contains($clean, ',')) {
            return null;
        }

        [$lat, $lng] = array_map('trim', explode(',', $clean, 2));
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return number_format((float) $lat, 6, '.', '') . ',' . number_format((float) $lng, 6, '.', '');
    }
}
