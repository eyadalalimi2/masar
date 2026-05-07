<?php

namespace App\Http\Requests\Distribution;

use App\Models\Distribution\Distributor;
use App\Models\Distribution\DistributorAccount;
use App\Support\Validation\UniqueUserContact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DistributorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $distributorId = $this->route('distributor')?->id ?? $this->route('distributor');
        $distributor = $distributorId ? Distributor::query()->find((int) $distributorId) : null;
        $distributorAccount = $distributorId
            ? DistributorAccount::query()->where('owner_id', (int) $distributorId)->orderByDesc('id')->first()
            : null;
        $submittedPhone = trim((string) $this->input('phone', ''));
        $distributorPhone = trim((string) ($distributor?->phone ?? ''));
        $accountPhone = trim((string) ($distributorAccount?->phone ?? ''));
        $isSameExistingPhone = $submittedPhone !== ''
            && in_array($submittedPhone, [$distributorPhone, $accountPhone], true);

        $phoneRules = ['required', 'string', 'max:20'];
        if (! $isSameExistingPhone) {
            $phoneRules[] = new UniqueUserContact('phone', [
                UniqueUserContact::ignore('accounts', $distributorAccount?->id),
                UniqueUserContact::ignore('distributors', $distributor?->id ?? $distributorId),
            ]);
        }

        $lookupService = app('App\\Services\\Lookup\\LookupService');

        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => $phoneRules,
            'password' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'min:6'],
            'image' => ['nullable', 'image', 'max:4096'],
            'vehicle_type' => ['nullable', 'string', 'max:255'],
            'distribution_points' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in($lookupService->accountStatuses())],
        ];
    }

    public function attributes(): array
    {
        return [
            'supplier_id' => 'الوكيل',
            'branch_id' => 'الفرع',
            'name' => 'اسم المندوب',
            'phone' => 'رقم الهاتف',
            'password' => 'كلمة المرور',
            'image' => 'صورة المندوب',
            'vehicle_type' => 'نوع المركبة',
            'distribution_points' => 'أماكن التوزيع',
            'status' => 'الحالة',
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'حقل :attribute مطلوب.',
            'supplier_id.exists' => 'قيمة :attribute غير صحيحة.',
            'branch_id.exists' => 'قيمة :attribute غير صحيحة.',
            'name.required' => 'حقل :attribute مطلوب.',
            'name.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'name.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'phone.required' => 'حقل :attribute مطلوب.',
            'phone.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'phone.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'phone.unique' => 'رقم الهاتف مستخدم مسبقاً.',
            'password.required' => 'حقل :attribute مطلوب.',
            'password.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'password.min' => 'حقل :attribute يجب ألا يقل عن :min أحرف.',
            'image.image' => 'حقل :attribute يجب أن يكون صورة صحيحة.',
            'image.max' => 'حقل :attribute يجب ألا يزيد عن :max كيلوبايت.',
            'vehicle_type.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'vehicle_type.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'distribution_points.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'distribution_points.max' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
            'status.required' => 'حقل :attribute مطلوب.',
            'status.in' => 'قيمة :attribute غير صحيحة.',
        ];
    }
}
