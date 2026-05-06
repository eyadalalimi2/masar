<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminRoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()->with('permissions')->latest()->get();
        $permissions = Permission::query()->orderBy('name')->get();
        $admins = Admin::query()->orderBy('name')->get();

        return view('admin.roles.index', compact('roles', 'permissions', 'admins'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:admin_roles,slug'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:admin_permissions,slug'],
            'admin_ids' => ['array'],
            'admin_ids.*' => ['integer', 'exists:admins,id'],
        ]);

        $role = Role::query()->create([
            'name' => $data['name'],
            'slug' => $data['slug'],
        ]);

        $permissionIds = Permission::query()
            ->whereIn('slug', $data['permissions'] ?? [])
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
        $role->admins()->sync($data['admin_ids'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', 'تم إنشاء الدور بنجاح.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:admin_permissions,slug'],
            'admin_ids' => ['array'],
            'admin_ids.*' => ['integer', 'exists:admins,id'],
        ]);

        $role->update(['name' => $data['name']]);

        $permissionIds = Permission::query()
            ->whereIn('slug', $data['permissions'] ?? [])
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
        $role->admins()->sync($data['admin_ids'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', 'تم تحديث الدور بنجاح.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->slug === 'super-admin') {
            return redirect()->route('admin.roles.index')->with('error', 'لا يمكن حذف دور المدير العام.');
        }

        $role->permissions()->detach();
        $role->admins()->detach();
        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'تم حذف الدور بنجاح.');
    }
}
