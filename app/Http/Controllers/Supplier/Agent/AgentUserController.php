<?php

namespace App\Http\Controllers\Supplier\Agent;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Models\Supplier\Agent;
use App\Services\Lookup\LookupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgentUserController extends Controller
{
    public function index(): View
    {
        $supplierId = (int) Auth::guard('agent')->user()->supplier->id;

        $users = Agent::query()
            ->where('supplier_id', $supplierId)
            ->orderByDesc('id')
            ->paginate(15);

        return view('agent.users.index', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $supplierId = (int) Auth::guard('agent')->user()->supplier->id;
        $lookupService = app(LookupService::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:agents,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:agents,phone'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'status' => ['required', Rule::in($lookupService->accountStatuses())],
        ]);

        Agent::query()->create([
            'supplier_id' => $supplierId,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'password' => $data['password'],
            'status' => $data['status'],
        ]);

        return back()->with('success', 'تم إنشاء المستخدم بنجاح.');
    }

    public function update(Request $request, Agent $user): RedirectResponse
    {
        $supplierId = (int) Auth::guard('agent')->user()->supplier->id;
        abort_unless((int) $user->supplier_id === $supplierId, 404);
        $lookupService = app(LookupService::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('agents', 'email')->ignore($user->id),
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('agents', 'phone')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'status' => ['required', Rule::in($lookupService->accountStatuses())],
        ]);

        if ((int) Auth::guard('agent')->id() === (int) $user->id && $data['status'] === Account::STATUS_INACTIVE) {
            return back()->withErrors(['users' => 'لا يمكن تعطيل الحساب المستخدم حاليًا.']);
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'status' => $data['status'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);

        return back()->with('success', 'تم تحديث بيانات المستخدم.');
    }

    public function toggle(Agent $user): RedirectResponse
    {
        $supplierId = (int) Auth::guard('agent')->user()->supplier->id;
        abort_unless((int) $user->supplier_id === $supplierId, 404);

        if ((int) Auth::guard('agent')->id() === (int) $user->id) {
            return back()->withErrors(['users' => 'لا يمكن تعطيل الحساب المستخدم حاليًا.']);
        }

        $user->update([
            'status' => $user->status === Account::STATUS_ACTIVE ? Account::STATUS_INACTIVE : Account::STATUS_ACTIVE,
        ]);

        return back()->with('success', 'تم تحديث حالة المستخدم.');
    }

    public function destroy(Agent $user): RedirectResponse
    {
        $supplierId = (int) Auth::guard('agent')->user()->supplier->id;
        abort_unless((int) $user->supplier_id === $supplierId, 404);

        if ((int) Auth::guard('agent')->id() === (int) $user->id) {
            return back()->withErrors(['users' => 'لا يمكن حذف الحساب المستخدم حاليًا.']);
        }

        $user->delete();

        return back()->with('success', 'تم حذف المستخدم بنجاح.');
    }
}
