<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Models\Distribution\Branch;
use App\Models\Distribution\BranchAccount;
use App\Services\Lookup\LookupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchUserController extends Controller
{
    public function index(): View
    {
        $branch = $this->currentBranch();

        $users = BranchAccount::query()
            ->where('branch_id', $branch->id)
            ->latest()
            ->paginate(15);

        return view('branch.users.index', compact('branch', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $branch = $this->currentBranch();
        $lookupService = app(LookupService::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('accounts', 'phone')->where(fn($q) => $q->where('account_type', 'branch'))],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'status' => ['required', Rule::in($lookupService->accountStatuses())],
        ]);

        BranchAccount::query()->create([
            'branch_id' => $branch->id,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'status' => $data['status'],
        ]);

        return back()->with('success', 'تم إضافة مستخدم الفرع بنجاح.');
    }

    public function update(Request $request, BranchAccount $user): RedirectResponse
    {
        $branch = $this->currentBranch();
        abort_unless((int) $user->branch_id === (int) $branch->id, 404);
        $lookupService = app(LookupService::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('accounts', 'phone')->where(fn($q) => $q->where('account_type', 'branch'))->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'status' => ['required', Rule::in($lookupService->accountStatuses())],
        ]);

        if ((int) Auth::guard('branch')->id() === (int) $user->id && $data['status'] === Account::STATUS_INACTIVE) {
            return back()->withErrors(['branch_users' => 'لا يمكن تعطيل الحساب المستخدم حاليًا.']);
        }

        $payload = [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'status' => $data['status'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);

        return back()->with('success', 'تم تحديث بيانات المستخدم بنجاح.');
    }

    public function toggle(BranchAccount $user): RedirectResponse
    {
        $branch = $this->currentBranch();
        abort_unless((int) $user->branch_id === (int) $branch->id, 404);

        if ((int) Auth::guard('branch')->id() === (int) $user->id) {
            return back()->withErrors(['branch_users' => 'لا يمكن تعطيل الحساب المستخدم حاليًا.']);
        }

        $user->update([
            'status' => $user->status === Account::STATUS_ACTIVE ? Account::STATUS_INACTIVE : Account::STATUS_ACTIVE,
        ]);

        return back()->with('success', 'تم تحديث حالة المستخدم بنجاح.');
    }

    public function destroy(BranchAccount $user): RedirectResponse
    {
        $branch = $this->currentBranch();
        abort_unless((int) $user->branch_id === (int) $branch->id, 404);

        if ((int) Auth::guard('branch')->id() === (int) $user->id) {
            return back()->withErrors(['branch_users' => 'لا يمكن حذف الحساب المستخدم حاليًا.']);
        }

        $user->delete();

        return back()->with('success', 'تم حذف مستخدم الفرع بنجاح.');
    }

    private function currentBranch(): Branch
    {
        $account = Auth::guard('branch')->user();

        if ($account && isset($account->branch_id) && (int) $account->branch_id > 0) {
            return Branch::query()->whereKey((int) $account->branch_id)->firstOrFail();
        }

        $phone = trim((string) ($account->phone ?? ''));
        if ($phone === '') {
            abort(403);
        }

        return Branch::query()->where('phone', $phone)->firstOrFail();
    }
}
