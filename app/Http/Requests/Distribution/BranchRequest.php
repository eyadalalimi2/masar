<?php

namespace App\Http\Requests\Distribution;

use App\Models\Distribution\Branch;
use App\Models\Distribution\BranchAccount;
use App\Support\Validation\UniqueUserContact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branchId = $this->route('branch')?->id ?? $this->route('branch');
        $branchAccountId = $branchId ? BranchAccount::query()->where('owner_id', (int) $branchId)->value('id') : null;
        $currentBranch = $branchId ? Branch::query()->find((int) $branchId) : null;
        $currentBranchAccount = $branchId
            ? BranchAccount::query()->where('owner_id', (int) $branchId)->orderByDesc('id')->first()
            : null;

        $submittedPhone = trim((string) $this->input('phone', ''));
        $branchPhone = trim((string) ($currentBranch?->phone ?? ''));
        $branchAccountPhone = trim((string) ($currentBranchAccount?->phone ?? ''));
        $isSameExistingPhone = $submittedPhone !== ''
            && in_array($submittedPhone, [$branchPhone, $branchAccountPhone], true);

        $phoneRules = ['required', 'string', 'max:20'];
        if (! $isSameExistingPhone) {
            $phoneRules[] = new UniqueUserContact('phone', [
                UniqueUserContact::ignore('branches', $branchId),
                UniqueUserContact::ignore('accounts', $branchAccountId),
            ]);
        }
        $lookupService = app('App\\Services\\Lookup\\LookupService');

        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => $phoneRules,
            'branch_manager_name' => ['nullable', 'string', 'max:255'],
            'branch_manager_image' => ['nullable', 'image', 'max:4096'],
            'branch_manager_password' => ['nullable', 'string', 'min:6', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'gps_location' => ['nullable', 'string', 'max:255', 'regex:/^\s*-?\d{1,2}(?:\.\d+)?\s*,\s*-?\d{1,3}(?:\.\d+)?\s*$/'],
            'status' => ['required', Rule::in($lookupService->accountStatuses())],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('gps_location')) {
            return;
        }

        $normalized = $this->normalizeGpsLocation($this->input('gps_location'));
        if ($normalized === null && trim((string) $this->input('gps_location')) === '') {
            $this->merge(['gps_location' => null]);

            return;
        }

        if ($normalized !== null) {
            $this->merge(['gps_location' => $normalized]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $location = $this->input('gps_location');
            if ($location === null || $location === '') {
                return;
            }

            if (! is_string($location) || ! str_contains($location, ',')) {
                return;
            }

            [$lat, $lng] = array_map('trim', explode(',', $location, 2));
            if (! is_numeric($lat) || ! is_numeric($lng)) {
                return;
            }

            $latValue = (float) $lat;
            $lngValue = (float) $lng;

            if ($latValue < -90 || $latValue > 90 || $lngValue < -180 || $lngValue > 180) {
                $validator->errors()->add('gps_location', 'قيمة الموقع يجب أن تكون إحداثيات صحيحة بصيغة latitude,longitude.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'supplier_id' => 'الوكيل',
            'name' => 'اسم الفرع',
            'phone' => 'رقم الهاتف',
            'branch_manager_name' => 'اسم مدير الفرع',
            'branch_manager_image' => 'صورة مدير الفرع',
            'branch_manager_password' => 'كلمة مرور مدير الفرع',
            'address' => 'العنوان',
            'gps_location' => 'الموقع',
            'status' => 'الحالة',
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'حقل :attribute مطلوب.',
            'supplier_id.exists' => 'قيمة :attribute غير صحيحة.',
            'name.required' => 'حقل :attribute مطلوب.',
            'name.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'name.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'phone.required' => 'حقل :attribute مطلوب.',
            'phone.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'phone.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'phone.unique' => 'رقم الهاتف مستخدم لفرع آخر.',
            'branch_manager_name.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'branch_manager_name.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'branch_manager_image.image' => 'حقل :attribute يجب أن يكون صورة.',
            'branch_manager_image.max' => 'حجم :attribute يجب ألا يتجاوز :max كيلوبايت.',
            'branch_manager_password.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'branch_manager_password.min' => 'حقل :attribute يجب ألا يقل عن :min أحرف.',
            'branch_manager_password.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'address.required' => 'حقل :attribute مطلوب.',
            'address.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'address.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'gps_location.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'gps_location.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'gps_location.regex' => 'حقل :attribute يجب أن يكون بصيغة latitude,longitude.',
            'status.required' => 'حقل :attribute مطلوب.',
            'status.in' => 'قيمة :attribute غير صحيحة.',
        ];
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
