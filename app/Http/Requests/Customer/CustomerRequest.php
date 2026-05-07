<?php

namespace App\Http\Requests\Customer;

use App\Models\Customer\Pos;
use App\Models\Customer\Workshop;
use App\Support\OptionLists;
use App\Support\Validation\UniqueUserContact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('customer')?->id;
        $posAccountId = $customerId ? Pos::query()->where('owner_id', (int) $customerId)->value('id') : null;
        $workshopAccountId = $customerId ? Workshop::query()->where('owner_id', (int) $customerId)->value('id') : null;
        $lookupService = app('App\\Services\\Lookup\\LookupService');

        return [
            'type' => ['required', Rule::in(OptionLists::CUSTOMER_TYPES)],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', new UniqueUserContact('phone', [
                UniqueUserContact::ignore('customers', $customerId),
                UniqueUserContact::ignore('accounts', $posAccountId),
                UniqueUserContact::ignore('accounts', $workshopAccountId),
            ])],
            'password' => [$customerId ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1500'],
            'gps_location' => ['nullable', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'owner_image' => ['nullable', 'image', 'max:5120'],
            'logo' => ['nullable', 'image', 'max:5120'],
            'store_images' => ['nullable', 'array'],
            'store_images.*' => ['image', 'max:5120'],
            'national_id_number' => ['nullable', 'string', 'max:255'],
            'national_id_image' => ['nullable', 'image', 'max:5120'],
            'commercial_reg_number' => ['nullable', 'string', 'max:255'],
            'commercial_reg_image' => ['nullable', 'image', 'max:5120'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'license_image' => ['nullable', 'image', 'max:5120'],
            'status' => ['required', Rule::in($lookupService->accountStatuses())],
        ];
    }
}
