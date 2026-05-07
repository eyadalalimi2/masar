<?php

namespace App\Http\Requests\Supplier;

use App\Models\Supplier\Agent;
use App\Models\Supplier\Supplier;
use App\Support\Validation\UniqueUserContact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SupplierApiProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agentId = (int) (Auth::guard('agent')->id() ?? 0);
        $supplierId = (int) (Auth::guard('agent')->user()?->supplier_id ?? 0);
        $currentAgent = $agentId > 0 ? Agent::withTrashed()->find($agentId) : null;
        $currentSupplier = $supplierId > 0 ? Supplier::query()->find($supplierId) : null;

        $submittedPhone = trim((string) $this->input('phone', ''));
        $submittedEmail = strtolower(trim((string) $this->input('email', '')));
        $agentPhone = trim((string) ($currentAgent?->phone ?? ''));
        $supplierPhone = trim((string) ($currentSupplier?->phone ?? ''));
        $agentEmail = strtolower(trim((string) ($currentAgent?->email ?? '')));
        $supplierEmail = strtolower(trim((string) ($currentSupplier?->email ?? '')));
        $isSameExistingPhone = $submittedPhone !== ''
            && in_array($submittedPhone, [$agentPhone, $supplierPhone], true);
        $isSameExistingEmail = $submittedEmail !== ''
            && in_array($submittedEmail, [$agentEmail, $supplierEmail], true);

        $phoneRules = ['required', 'string', 'max:20'];
        if (! $isSameExistingPhone) {
            $phoneRules[] = new UniqueUserContact('phone', [
                UniqueUserContact::ignore('agents', $agentId > 0 ? $agentId : null),
                UniqueUserContact::ignore('suppliers', $supplierId > 0 ? $supplierId : null),
            ]);
        }

        $emailRules = ['nullable', 'email', 'max:255'];
        if (! $isSameExistingEmail) {
            $emailRules[] = new UniqueUserContact('email', [
                UniqueUserContact::ignore('agents', $agentId > 0 ? $agentId : null),
                UniqueUserContact::ignore('suppliers', $supplierId > 0 ? $supplierId : null),
            ]);
        }

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
            'phone' => $phoneRules,
            'whatsapp' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'gps_location' => ['required', 'string', 'max:255', 'regex:/^\s*-?\d{1,2}(?:\.\d+)?\s*,\s*-?\d{1,3}(?:\.\d+)?\s*$/'],
            'email' => $emailRules,
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
