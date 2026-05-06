<?php

namespace App\Services\Customer;

use App\Models\Finance\Account;
use App\Models\Customer\Customer;
use App\Models\Customer\Workshop as WorkshopAccount;
use App\Models\Pos;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CustomerService
{
    public function create(array $data): Customer
    {
        $payload = $this->preparePayload($data);

        return Customer::create($payload);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $oldPhone = $customer->phone;
        $plainPassword = $this->extractPlainPassword($data['password'] ?? null);

        $payload = $this->preparePayload($data, $customer);
        $customer->update($payload);

        $freshCustomer = $customer->fresh();
        $this->syncRetailStorePosAccount($freshCustomer, $plainPassword, $oldPhone);
        $this->syncWorkshopAccount($freshCustomer, $plainPassword, $oldPhone);

        return $freshCustomer;
    }

    public function toggleStatus(Customer $customer): Customer
    {
        $customer->update([
            'status' => $customer->status === Account::STATUS_ACTIVE ? Account::STATUS_INACTIVE : Account::STATUS_ACTIVE,
        ]);

        $freshCustomer = $customer->fresh();

        if ($freshCustomer->type === 'retail_store') {
            Pos::query()
                ->where(function ($query) use ($freshCustomer): void {
                    $query->where('owner_id', $freshCustomer->id)
                        ->orWhere('phone', $freshCustomer->phone);
                })
                ->update([
                    'owner_id' => $freshCustomer->id,
                    'status' => $freshCustomer->status,
                ]);
        }

        if ($freshCustomer->type === 'workshop') {
            WorkshopAccount::query()
                ->where(function ($query) use ($freshCustomer): void {
                    $query->where('owner_id', $freshCustomer->id)
                        ->orWhere('phone', $freshCustomer->phone);
                })
                ->update([
                    'owner_id' => $freshCustomer->id,
                    'status' => $freshCustomer->status,
                ]);
        }

        return $freshCustomer;
    }

    public function delete(Customer $customer): void
    {
        $customer->delete();
    }

    public function restore(Customer $customer): void
    {
        if ($customer->trashed()) {
            $customer->restore();
        }
    }

    public function forceDelete(Customer $customer): void
    {
        $this->deleteIfExists($customer->owner_image);
        $this->deleteIfExists($customer->logo);
        $this->deleteManyIfExists($customer->store_images);
        $this->deleteIfExists($customer->national_id_image);
        $this->deleteIfExists($customer->commercial_reg_image);
        $this->deleteIfExists($customer->license_image);

        $customer->forceDelete();
    }

    public function removeStoreImageByIndex(Customer $customer, int $imageIndex): bool
    {
        $images = $this->normalizePaths($customer->store_images);

        if (! array_key_exists($imageIndex, $images)) {
            return false;
        }

        $pathToDelete = $images[$imageIndex];
        unset($images[$imageIndex]);

        $images = array_values($images);

        $this->deleteIfExists($pathToDelete);

        $customer->update([
            'store_images' => $images === [] ? null : $images,
        ]);

        return true;
    }

    private function preparePayload(array $data, ?Customer $customer = null): array
    {
        $plainPassword = $this->extractPlainPassword($data['password'] ?? null);
        if ($plainPassword === null) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($plainPassword);
        }

        $data = $this->storeUploadedImage($data, 'owner_image', 'customers/owner-image', $customer?->owner_image);
        $data = $this->storeUploadedImage($data, 'logo', 'customers/logo', $customer?->logo);
        $data = $this->storeUploadedGallery($data, 'store_images', 'customers/store-images', $customer?->store_images);
        $data = $this->storeUploadedImage($data, 'national_id_image', 'customers/national-id', $customer?->national_id_image);
        $data = $this->storeUploadedImage($data, 'commercial_reg_image', 'customers/commercial', $customer?->commercial_reg_image);
        $data = $this->storeUploadedImage($data, 'license_image', 'customers/license', $customer?->license_image);

        return $data;
    }

    private function extractPlainPassword(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $password = trim($value);

        return $password !== '' ? $password : null;
    }

    private function syncRetailStorePosAccount(Customer $customer, ?string $plainPassword, ?string $oldPhone): void
    {
        if ($customer->type !== 'retail_store') {
            return;
        }

        $lookupPhones = array_values(array_unique(array_filter([
            is_string($oldPhone) ? trim($oldPhone) : null,
            is_string($customer->phone) ? trim($customer->phone) : null,
        ])));

        if ($lookupPhones === []) {
            return;
        }

        $posAccountQuery = Pos::query();

        if ((int) $customer->id > 0) {
            $posAccountQuery->where('owner_id', $customer->id);
        }

        $posAccount = $posAccountQuery
            ->orWhereIn('phone', $lookupPhones)
            ->orderByRaw('CASE WHEN phone = ? THEN 0 ELSE 1 END', [$lookupPhones[0]])
            ->first();

        if (! $posAccount) {
            Pos::create([
                'owner_id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'password' => $this->resolveAccountPassword($customer, $plainPassword),
                'status' => $customer->status,
            ]);

            return;
        }

        $updates = [
            'owner_id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'status' => $customer->status,
        ];

        if ($plainPassword !== null) {
            $updates['password'] = $plainPassword;
        }

        $posAccount->update($updates);
    }

    private function syncWorkshopAccount(Customer $customer, ?string $plainPassword, ?string $oldPhone): void
    {
        if ($customer->type !== 'workshop') {
            return;
        }

        $lookupPhones = array_values(array_unique(array_filter([
            is_string($oldPhone) ? trim($oldPhone) : null,
            is_string($customer->phone) ? trim($customer->phone) : null,
        ])));

        if ($lookupPhones === []) {
            return;
        }

        $workshopAccountQuery = WorkshopAccount::query();

        if ((int) $customer->id > 0) {
            $workshopAccountQuery->where('owner_id', $customer->id);
        }

        $workshopAccount = $workshopAccountQuery
            ->orWhereIn('phone', $lookupPhones)
            ->orderByRaw('CASE WHEN phone = ? THEN 0 ELSE 1 END', [$lookupPhones[0]])
            ->first();

        if (! $workshopAccount) {
            WorkshopAccount::create([
                'owner_id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'password' => $this->resolveAccountPassword($customer, $plainPassword),
                'status' => $customer->status,
            ]);

            return;
        }

        $updates = [
            'owner_id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'status' => $customer->status,
        ];

        if ($plainPassword !== null) {
            $updates['password'] = $plainPassword;
        }

        $workshopAccount->update($updates);
    }

    private function resolveAccountPassword(Customer $customer, ?string $plainPassword): string
    {
        if ($plainPassword !== null) {
            return $plainPassword;
        }

        $customerPasswordHash = $customer->getRawOriginal('password');
        if (is_string($customerPasswordHash) && $customerPasswordHash !== '') {
            return $customerPasswordHash;
        }

        return Hash::make(Str::random(32));
    }

    private function storeUploadedGallery(array $data, string $key, string $folder, mixed $oldPaths = null): array
    {
        if (! array_key_exists($key, $data)) {
            return $data;
        }

        $files = $data[$key];

        if ($files instanceof UploadedFile) {
            $files = [$files];
        }

        if (! is_array($files)) {
            unset($data[$key]);

            return $data;
        }

        $validFiles = array_values(array_filter($files, fn($file) => $file instanceof UploadedFile));

        if ($validFiles === []) {
            unset($data[$key]);

            return $data;
        }

        $this->deleteManyIfExists($oldPaths);

        $paths = [];
        foreach ($validFiles as $file) {
            $paths[] = $file->store($folder, 'public');
        }

        $data[$key] = $paths;

        return $data;
    }

    private function storeUploadedImage(array $data, string $key, string $folder, ?string $oldPath = null): array
    {
        if (! array_key_exists($key, $data) || ! $data[$key] instanceof UploadedFile) {
            unset($data[$key]);

            return $data;
        }

        $this->deleteIfExists($oldPath);
        $data[$key] = $data[$key]->store($folder, 'public');

        return $data;
    }

    private function deleteIfExists(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function deleteManyIfExists(mixed $paths): void
    {
        $normalized = $this->normalizePaths($paths);
        foreach ($normalized as $path) {
            $this->deleteIfExists($path);
        }
    }

    private function normalizePaths(mixed $paths): array
    {
        if (is_string($paths)) {
            $decoded = json_decode($paths, true);
            $paths = is_array($decoded) ? $decoded : [$paths];
        }

        if (! is_array($paths)) {
            return [];
        }

        return array_values(array_filter(array_map(fn($path) => is_string($path) ? trim($path) : '', $paths)));
    }
}
