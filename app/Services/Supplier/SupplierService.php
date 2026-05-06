<?php

namespace App\Services\Supplier;

use App\Models\Finance\Account;
use App\Models\Supplier\Agent;
use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierFieldChangeRequest;
use App\Support\WorkingHoursCodec;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SupplierService
{
    public function createSupplier(array $data): Supplier
    {
        return DB::transaction(function () use ($data) {
            $payload = $this->prepareSupplierPayload($data);
            $payload['status'] = $data['status'] ?? Account::STATUS_ACTIVE;

            $supplier = Supplier::create($payload);

            Agent::updateOrCreate([
                'supplier_id' => $supplier->id,
            ], [
                'name' => $data['owner_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'],
                'password' => $data['password'],
                'status' => $payload['status'],
            ]);

            return $supplier;
        });
    }

    public function updateSupplier(array $data): Supplier
    {
        return DB::transaction(function () use ($data) {
            $supplier = Supplier::with('agentAccount')->findOrFail($data['supplier_id']);

            $supplierPayload = $this->prepareSupplierPayload($data, $supplier);
            $supplier->update($supplierPayload);

            Agent::query()->updateOrCreate([
                'supplier_id' => $supplier->id,
            ], [
                'name' => $data['owner_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'],
                'status' => $supplier->status,
            ]);

            $plainPassword = $this->normalizePassword($data['password'] ?? null);
            if ($plainPassword !== null) {
                $agent = Agent::query()->where('supplier_id', $supplier->id)->first();
                if ($agent) {
                    $agent->update(['password' => $plainPassword]);
                }
            }

            return $supplier->fresh(['agentAccount']);
        });
    }

    public function toggleStatus(int $id): Supplier
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->status = $supplier->status === Account::STATUS_ACTIVE ? Account::STATUS_INACTIVE : Account::STATUS_ACTIVE;
        $supplier->save();

        return $supplier;
    }

    public function verifySupplier(int $id, int $verifiedByUserId): Supplier
    {
        $supplier = Supplier::findOrFail($id);

        if (! $supplier->has_verification_request) {
            throw new \DomainException('لا يوجد طلب توثيق صالح من الوكيل.');
        }

        if (! $supplier->is_verified) {
            $supplier->update([
                'is_verified' => true,
                'verified_at' => now(),
                'verified_by_user_id' => $verifiedByUserId,
                'verification_requested_at' => null,
                'verification_requested_by_user_id' => null,
            ]);
        }

        return $supplier->fresh();
    }

    public function unverifySupplier(int $id): Supplier
    {
        $supplier = Supplier::findOrFail($id);

        if ($supplier->is_verified) {
            $supplier->update([
                'is_verified' => false,
                'verified_at' => null,
                'verified_by_user_id' => null,
                'verification_requested_at' => null,
                'verification_requested_by_user_id' => null,
            ]);
        }

        return $supplier->fresh();
    }

    public function requestVerification(int $id, int $requestedByUserId): Supplier
    {
        $supplier = Supplier::findOrFail($id);

        if (! $supplier->is_verified && $supplier->verification_requested_at === null) {
            $supplier->update([
                'verification_requested_at' => now(),
                'verification_requested_by_user_id' => $requestedByUserId,
            ]);
        }

        return $supplier->fresh();
    }

    public function updateSupplierWorkingHours(int $supplierId, string $workingHours): Supplier
    {
        $supplier = Supplier::findOrFail($supplierId);
        $supplier->update([
            'working_hours' => $workingHours,
        ]);

        return $supplier->fresh();
    }

    public function updateAgentSecurity(
        int $supplierId,
        int $agentId,
        ?UploadedFile $agentImage = null,
        ?string $newPassword = null
    ): Supplier {
        return DB::transaction(function () use ($supplierId, $agentId, $agentImage, $newPassword) {
            $supplier = Supplier::findOrFail($supplierId);
            $agent = Agent::query()
                ->where('id', $agentId)
                ->where('supplier_id', $supplierId)
                ->firstOrFail();

            if ($agentImage instanceof UploadedFile) {
                if ($supplier->agent_image) {
                    $this->deleteFile($supplier->agent_image);
                }

                $supplier->update([
                    'agent_image' => $agentImage->store('suppliers/agent-image', 'public'),
                ]);
            }

            $plainPassword = $this->normalizePassword($newPassword);
            if ($plainPassword !== null) {
                $agent->update([
                    'password' => $plainPassword,
                ]);
            }

            return $supplier->fresh(['agentAccount']);
        });
    }

    public function createFieldChangeRequest(
        int $supplierId,
        int $requestedByUserId,
        string $fieldKey,
        ?string $requestedValue,
        ?string $note = null,
        ?UploadedFile $document = null,
        ?UploadedFile $requestedImage = null
    ): SupplierFieldChangeRequest {
        $documentPath = $document ? $document->store('suppliers/change-requests', 'public') : null;
        $requestedImagePath = $requestedImage ? $requestedImage->store('suppliers/change-requests/field-images', 'public') : null;

        $finalRequestedValue = $requestedImagePath ?: trim((string) $requestedValue);
        if ($finalRequestedValue === '') {
            throw new \DomainException('قيمة التعديل المطلوبة غير صالحة.');
        }

        return SupplierFieldChangeRequest::create([
            'supplier_id' => $supplierId,
            'requested_by_user_id' => $requestedByUserId,
            'field_key' => $fieldKey,
            'requested_value' => $finalRequestedValue,
            'note' => $note,
            'document_path' => $documentPath,
            'status' => 'pending',
        ]);
    }

    public function approveFieldChangeRequest(int $supplierId, int $requestId, int $reviewedByUserId): SupplierFieldChangeRequest
    {
        return DB::transaction(function () use ($supplierId, $requestId, $reviewedByUserId) {
            $changeRequest = SupplierFieldChangeRequest::where('supplier_id', $supplierId)->findOrFail($requestId);

            if ($changeRequest->status !== 'pending') {
                throw new \DomainException('تمت مراجعة هذا الطلب مسبقًا.');
            }

            $supplier = Supplier::with('user')->findOrFail($supplierId);

            $this->applyApprovedFieldChange($supplier, $changeRequest->field_key, $changeRequest->requested_value);

            $changeRequest->update([
                'status' => 'approved',
                'reviewed_by_user_id' => $reviewedByUserId,
                'reviewed_at' => now(),
            ]);

            return $changeRequest->fresh(['requestedByUser', 'reviewedByUser']);
        });
    }

    public function rejectFieldChangeRequest(int $supplierId, int $requestId, int $reviewedByUserId): SupplierFieldChangeRequest
    {
        $changeRequest = SupplierFieldChangeRequest::where('supplier_id', $supplierId)->findOrFail($requestId);

        if ($changeRequest->status !== 'pending') {
            throw new \DomainException('تمت مراجعة هذا الطلب مسبقًا.');
        }

        $changeRequest->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => $reviewedByUserId,
            'reviewed_at' => now(),
        ]);

        return $changeRequest->fresh(['requestedByUser', 'reviewedByUser']);
    }

    public function deleteSupplier(int $id): void
    {
        Supplier::query()->findOrFail($id)->delete();
    }

    public function restoreSupplier(int $id): void
    {
        $supplier = Supplier::withTrashed()->findOrFail($id);
        if ($supplier->trashed()) {
            $supplier->restore();
        }
    }

    public function forceDeleteSupplier(int $id): void
    {
        DB::transaction(function () use ($id) {
            $supplier = Supplier::withTrashed()->with('agentAccount')->findOrFail($id);

            $this->deleteFile($supplier->logo);
            $this->deleteFile($supplier->agent_image);
            $this->deleteFile($supplier->branch_manager_image);
            $this->deleteFile($supplier->commercial_reg_image);
            $this->deleteFile($supplier->license_image);
            $this->deleteFile($supplier->national_id_image);

            $supplier->agentAccount?->delete();
            $supplier->forceDelete();
        });
    }

    private function prepareSupplierPayload(array $data, ?Supplier $supplier = null): array
    {
        $payload = Arr::only($data, [
            'owner_name',
            'branch_manager_name',
            'business_name',
            'commercial_reg_number',
            'license_number',
            'national_id_number',
            'phone',
            'whatsapp',
            'address',
            'gps_location',
            'email',
            'working_hours',
            'status',
        ]);

        if (array_key_exists('working_hours', $payload) && is_array($payload['working_hours'])) {
            $payload['working_hours'] = WorkingHoursCodec::encode($payload['working_hours']);
        }

        if (isset($data['logo']) && $data['logo'] instanceof UploadedFile) {
            if ($supplier?->logo) {
                $this->deleteFile($supplier->logo);
            }
            $payload['logo'] = $data['logo']->store('suppliers/logo', 'public');
        }

        if (isset($data['agent_image']) && $data['agent_image'] instanceof UploadedFile) {
            if ($supplier?->agent_image) {
                $this->deleteFile($supplier->agent_image);
            }
            $payload['agent_image'] = $data['agent_image']->store('suppliers/agent-image', 'public');
        }

        if (isset($data['branch_manager_image']) && $data['branch_manager_image'] instanceof UploadedFile) {
            if ($supplier?->branch_manager_image) {
                $this->deleteFile($supplier->branch_manager_image);
            }
            $payload['branch_manager_image'] = $data['branch_manager_image']->store('suppliers/branch-manager', 'public');
        }

        if (! empty($data['branch_manager_password']) && is_string($data['branch_manager_password'])) {
            $payload['branch_manager_password'] = Hash::make($data['branch_manager_password']);
        }

        if (isset($data['commercial_reg_image']) && $data['commercial_reg_image'] instanceof UploadedFile) {
            if ($supplier?->commercial_reg_image) {
                $this->deleteFile($supplier->commercial_reg_image);
            }
            $payload['commercial_reg_image'] = $data['commercial_reg_image']->store('suppliers/commercial', 'public');
        }

        if (isset($data['license_image']) && $data['license_image'] instanceof UploadedFile) {
            if ($supplier?->license_image) {
                $this->deleteFile($supplier->license_image);
            }
            $payload['license_image'] = $data['license_image']->store('suppliers/license', 'public');
        }

        $nationalIdImage = $data['national_id_image'] ?? $data['id_card_image'] ?? null;
        if ($nationalIdImage instanceof UploadedFile) {
            if ($supplier?->national_id_image) {
                $this->deleteFile($supplier->national_id_image);
            }
            $payload['national_id_image'] = $nationalIdImage->store('suppliers/national-id', 'public');
        }

        return $payload;
    }

    private function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function applyApprovedFieldChange(Supplier $supplier, string $fieldKey, string $requestedValue): void
    {
        $allowedFields = [
            'owner_name',
            'branch_manager_name',
            'email',
            'phone',
            'whatsapp',
            'national_id_number',
            'business_name',
            'gps_location',
            'address',
            'commercial_reg_number',
            'license_number',
            'logo',
            'agent_image',
            'branch_manager_image',
            'national_id_image',
            'commercial_reg_image',
            'license_image',
        ];

        $imageFields = [
            'logo',
            'agent_image',
            'branch_manager_image',
            'national_id_image',
            'commercial_reg_image',
            'license_image',
        ];

        if (! in_array($fieldKey, $allowedFields, true)) {
            throw new \DomainException('الحقل المطلوب غير مدعوم للتعديل.');
        }

        if (in_array($fieldKey, $imageFields, true)) {
            if (trim($requestedValue) === '') {
                throw new \DomainException('ملف الصورة المطلوب للتعديل غير صالح.');
            }

            $oldPath = (string) data_get($supplier, $fieldKey, '');
            if ($oldPath !== '' && $oldPath !== $requestedValue) {
                $this->deleteFile($oldPath);
            }

            $supplier->update([
                $fieldKey => $requestedValue,
            ]);

            return;
        }

        if ($fieldKey === 'phone') {
            $phoneExists = Agent::query()
                ->where('phone', $requestedValue)
                ->where('supplier_id', '!=', $supplier->id)
                ->exists();

            if ($phoneExists) {
                throw new \DomainException('رقم الهاتف المطلوب مستخدم مسبقًا لحساب آخر.');
            }
        }

        $supplier->update([
            $fieldKey => $requestedValue,
        ]);

        if ($fieldKey === 'owner_name') {
            Agent::query()->where('supplier_id', $supplier->id)->update(['name' => $requestedValue]);
        }

        if ($fieldKey === 'phone') {
            Agent::query()->where('supplier_id', $supplier->id)->update(['phone' => $requestedValue]);
        }

        if ($fieldKey === 'email') {
            Agent::query()->where('supplier_id', $supplier->id)->update(['email' => $requestedValue]);
        }
    }

    private function normalizePassword(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
