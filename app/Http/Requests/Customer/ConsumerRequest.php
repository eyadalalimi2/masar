<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConsumerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $consumerId = $this->route('consumer')?->id;
        $lookupService = app('App\\Services\\Lookup\\LookupService');

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', Rule::unique('consumers', 'phone')->ignore($consumerId)],
            'password' => [$consumerId ? 'nullable' : 'required', 'confirmed', 'min:6'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1500'],
            'gps_location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in($lookupService->accountStatuses())],
        ];
    }
}
