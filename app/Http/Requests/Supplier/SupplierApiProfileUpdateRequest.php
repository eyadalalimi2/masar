<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierApiProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = (int) ($this->user()?->id ?? 0);

        return [
            'logo' => ['nullable', 'image', 'max:4096'],
            'agent_image' => ['nullable', 'image', 'max:4096'],
            'branch_manager_image' => ['nullable', 'image', 'max:4096'],
            'id_card_image' => ['nullable', 'image', 'max:4096'],
            'national_id_image' => ['nullable', 'image', 'max:4096'],
            'commercial_reg_image' => ['nullable', 'image', 'max:4096'],
            'license_image' => ['nullable', 'image', 'max:4096'],
            'owner_name' => ['required', 'string', 'max:255'],
            'branch_manager_name' => ['nullable', 'string', 'max:255'],
            'branch_manager_password' => ['nullable', 'string', 'min:6', 'max:255'],
            'business_name' => ['required', 'string', 'max:255'],
            'commercial_reg_number' => ['nullable', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'national_id_number' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($userId)],
            'whatsapp' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'gps_location' => ['required', 'string', 'max:255', 'regex:/^\s*-?\d{1,2}(?:\.\d+)?\s*,\s*-?\d{1,3}(?:\.\d+)?\s*$/'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('gps_location')) {
            return;
        }

        $normalized = $this->normalizeGpsLocation($this->input('gps_location'));
        if ($normalized !== null) {
            $this->merge(['gps_location' => $normalized]);
        }
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






