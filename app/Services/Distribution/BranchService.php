<?php

namespace App\Services\Distribution;

use App\Models\Finance\Account;
use App\Models\Distribution\BranchAccount;
use App\Models\Distribution\Branch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class BranchService
{
    public function create(array $data): Branch
    {
        $plainManagerPassword = $this->extractPlainManagerPassword($data);
        $data = $this->prepareBranchPayload($data);

        return DB::transaction(function () use ($data, $plainManagerPassword) {
            $branch = Branch::create($data);
            $this->syncBranchAccountCredentials($branch, $plainManagerPassword, null);

            return $branch;
        });
    }

    public function update(Branch $branch, array $data): Branch
    {
        $oldPhone = (string) $branch->phone;
        $plainManagerPassword = $this->extractPlainManagerPassword($data);
        $data = $this->prepareBranchPayload($data, $branch);

        return DB::transaction(function () use ($branch, $data, $plainManagerPassword, $oldPhone) {
            $branch->update($data);
            $freshBranch = $branch->fresh();

            if ($freshBranch instanceof Branch) {
                $this->syncBranchAccountCredentials($freshBranch, $plainManagerPassword, $oldPhone);
            }

            return $freshBranch ?? $branch;
        });
    }

    public function delete(Branch $branch): void
    {
        $branch->delete();
    }

    public function restore(Branch $branch): void
    {
        if ($branch->trashed()) {
            $branch->restore();
        }
    }

    public function forceDelete(Branch $branch): void
    {
        DB::transaction(function () use ($branch) {
            $phone = trim((string) $branch->phone);

            $this->deleteFile($branch->branch_manager_image);
            $branch->forceDelete();

            if ($phone !== '') {
                BranchAccount::query()
                    ->where('phone', $phone)
                    ->delete();
            }
        });
    }

    public function toggleStatus(Branch $branch): Branch
    {
        $branch->status = $branch->status === Account::STATUS_ACTIVE ? Account::STATUS_INACTIVE : Account::STATUS_ACTIVE;
        $branch->save();

        return $branch;
    }

    private function prepareBranchPayload(array $data, ?Branch $branch = null): array
    {
        if (isset($data['branch_manager_image']) && $data['branch_manager_image'] instanceof UploadedFile) {
            if ($branch?->branch_manager_image) {
                $this->deleteFile($branch->branch_manager_image);
            }

            $data['branch_manager_image'] = $data['branch_manager_image']->store('branches/branch-manager', 'public');
        } else {
            unset($data['branch_manager_image']);
        }

        if (! empty($data['branch_manager_password']) && is_string($data['branch_manager_password'])) {
            $data['branch_manager_password'] = Hash::make($data['branch_manager_password']);
        } else {
            unset($data['branch_manager_password']);
        }

        return $data;
    }

    private function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function extractPlainManagerPassword(array $data): ?string
    {
        $password = $data['branch_manager_password'] ?? null;
        if (! is_string($password)) {
            return null;
        }

        $password = trim($password);

        return $password !== '' ? $password : null;
    }

    private function syncBranchAccountCredentials(Branch $branch, ?string $plainPassword, ?string $oldPhone): void
    {
        $phone = trim((string) $branch->phone);
        if ($phone === '') {
            return;
        }

        $accountQuery = BranchAccount::query()->where(function ($query) use ($phone, $oldPhone) {
            $query->where('phone', $phone);

            $normalizedOldPhone = trim((string) $oldPhone);
            if ($normalizedOldPhone !== '' && $normalizedOldPhone !== $phone) {
                $query->orWhere('phone', $normalizedOldPhone);
            }
        });

        $account = $accountQuery->first();

        if (! $account) {
            if ($plainPassword === null) {
                return;
            }

            BranchAccount::query()->create([
                'branch_id' => $branch->id,
                'name' => $this->resolveBranchUserName($branch),
                'phone' => $phone,
                'password' => $plainPassword,
                'status' => $branch->status,
            ]);

            return;
        }

        $updates = [];

        $resolvedName = $this->resolveBranchUserName($branch);
        if ($resolvedName !== '' && $account->name !== $resolvedName) {
            $updates['name'] = $resolvedName;
        }

        if ($account->phone !== $phone) {
            $updates['phone'] = $phone;
        }

        if ((int) $account->branch_id !== (int) $branch->id) {
            $updates['branch_id'] = $branch->id;
        }

        if ($account->status !== $branch->status) {
            $updates['status'] = $branch->status;
        }

        if ($plainPassword !== null) {
            $updates['password'] = $plainPassword;
        }

        if ($updates !== []) {
            $account->update($updates);
        }
    }

    private function resolveBranchUserName(Branch $branch): string
    {
        $managerName = trim((string) $branch->branch_manager_name);
        if ($managerName !== '') {
            return $managerName;
        }

        return trim((string) $branch->name);
    }
}
