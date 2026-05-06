<?php

namespace App\Services\Distribution;

use App\Models\Finance\Account;
use App\Models\Distribution\DistributorAccount;
use App\Models\Distribution\Distributor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DistributorService
{
    public function create(array $data): Distributor
    {
        return DB::transaction(function () use ($data) {
            $imagePath = $this->storeImage($data['image'] ?? null);

            $distributor = Distributor::create([
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'name' => $data['name'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'image' => $imagePath,
                'vehicle_type' => $data['vehicle_type'] ?? null,
                'distribution_points' => $data['distribution_points'] ?? null,
                'status' => $data['status'] ?? Account::STATUS_ACTIVE,
            ]);

            DistributorAccount::create([
                'distributor_id' => $distributor->id,
                'name' => $data['name'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'status' => $data['status'] ?? Account::STATUS_ACTIVE,
            ]);

            return $distributor;
        });
    }

    public function update(Distributor $distributor, array $data): Distributor
    {
        return DB::transaction(function () use ($distributor, $data) {
            $imagePath = $distributor->image;
            $plainPassword = $this->normalizePassword($data['password'] ?? null);

            if (! empty($data['image'])) {
                $newImagePath = $this->storeImage($data['image']);
                if ($newImagePath !== null) {
                    if (! empty($imagePath)) {
                        Storage::disk('public')->delete($imagePath);
                    }
                    $imagePath = $newImagePath;
                }
            }

            $distributorPayload = [
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'name' => $data['name'],
                'phone' => $data['phone'],
                'image' => $imagePath,
                'vehicle_type' => $data['vehicle_type'] ?? null,
                'distribution_points' => $data['distribution_points'] ?? null,
                'status' => $data['status'],
            ];

            if ($plainPassword !== null) {
                $distributorPayload['password'] = Hash::make($plainPassword);
            }

            $distributor->update($distributorPayload);

            $accountPayload = [
                'distributor_id' => $distributor->id,
                'name' => $data['name'],
                'phone' => $data['phone'],
                'status' => $data['status'],
            ];

            if ($plainPassword !== null) {
                $accountPayload['password'] = $plainPassword;
            }

            DistributorAccount::query()->updateOrCreate([
                'distributor_id' => $distributor->id,
            ], $accountPayload);

            return $distributor->fresh(['supplier', 'branch', 'account']);
        });
    }

    public function delete(Distributor $distributor): void
    {
        $distributor->delete();
    }

    public function restore(Distributor $distributor): void
    {
        if ($distributor->trashed()) {
            $distributor->restore();
        }
    }

    public function forceDelete(Distributor $distributor): void
    {
        DB::transaction(function () use ($distributor) {
            $account = $distributor->account;
            if (! empty($distributor->image)) {
                Storage::disk('public')->delete($distributor->image);
            }
            $distributor->forceDelete();
            $account?->delete();
        });
    }

    public function toggleStatus(Distributor $distributor): Distributor
    {
        $distributor->status = $distributor->status === Account::STATUS_ACTIVE ? Account::STATUS_INACTIVE : Account::STATUS_ACTIVE;
        $distributor->save();

        return $distributor;
    }

    private function storeImage(mixed $image): ?string
    {
        if ($image === null) {
            return null;
        }

        return $image->store('distributors', 'public');
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
