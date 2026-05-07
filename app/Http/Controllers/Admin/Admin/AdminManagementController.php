<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Admin\Role;
use App\Models\Finance\Account;
use App\Services\Lookup\LookupService;
use App\Services\Security\PermissionCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $lookupService = app(LookupService::class);

        $admins = Admin::query()
            ->with('roles:id,name,slug')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, $lookupService->accountStatuses(), true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.admins.index', compact('admins'));
    }

    public function create(): View
    {
        $roles = Role::query()->orderBy('name')->get(['id', 'name', 'slug']);

        return view('admin.admins.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $lookupService = app(LookupService::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('admins', 'phone')],
            'password' => ['required', 'string', 'min:6'],
            'status' => ['required', Rule::in($lookupService->accountStatuses())],
            'role_ids' => ['array'],
            'role_ids.*' => ['integer', 'exists:admin_roles,id'],
        ]);

        $admin = Admin::query()->create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'status' => $data['status'],
        ]);

        $admin->roles()->sync($data['role_ids'] ?? []);
        app(PermissionCacheService::class)->bumpVersion();

        return redirect()->route('admin.admins.index')->with('success', 'تم إضافة حساب الأدمن بنجاح.');
    }

    public function edit(Admin $admin): View
    {
        $roles = Role::query()->orderBy('name')->get(['id', 'name', 'slug']);
        $admin->load('roles:id,name,slug');

        return view('admin.admins.edit', compact('admin', 'roles'));
    }

    public function update(Request $request, Admin $admin): RedirectResponse
    {
        $lookupService = app(LookupService::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('admins', 'phone')->ignore($admin->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'status' => ['required', Rule::in($lookupService->accountStatuses())],
            'role_ids' => ['array'],
            'role_ids.*' => ['integer', 'exists:admin_roles,id'],
        ]);

        $currentAdminId = (int) Auth::guard('admin')->id();
        if ($currentAdminId === (int) $admin->id && $data['status'] === Account::STATUS_INACTIVE) {
            return back()->withInput()->with('error', 'لا يمكنك تعطيل حسابك الحالي.');
        }

        $admin->fill([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'status' => $data['status'],
        ]);

        if (! empty($data['password'])) {
            $admin->password = $data['password'];
        }

        $admin->save();
        $admin->roles()->sync($data['role_ids'] ?? []);
        app(PermissionCacheService::class)->bumpVersion();

        return redirect()->route('admin.admins.index')->with('success', 'تم تعديل حساب الأدمن بنجاح.');
    }

    public function destroy(Admin $admin): RedirectResponse
    {
        $currentAdminId = (int) Auth::guard('admin')->id();
        if ((int) $admin->id === $currentAdminId) {
            return back()->with('error', 'لا يمكنك حذف حسابك الحالي.');
        }

        $hasSuperAdminRole = $admin->roles()->where('slug', 'super-admin')->exists();
        if ($hasSuperAdminRole) {
            $otherSuperAdminsCount = Admin::query()
                ->where('id', '!=', $admin->id)
                ->whereHas('roles', fn($query) => $query->where('slug', 'super-admin'))
                ->count();

            if ($otherSuperAdminsCount === 0) {
                return back()->with('error', 'لا يمكن حذف آخر حساب يحمل دور المدير العام.');
            }
        }

        $admin->roles()->detach();
        $admin->delete();
        app(PermissionCacheService::class)->bumpVersion();

        return redirect()->route('admin.admins.index')->with('success', 'تم حذف حساب الأدمن بنجاح.');
    }
}
