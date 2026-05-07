<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Admin\Permission;
use App\Models\Admin\PermissionGroup;
use App\Models\Admin\Role;
use App\Services\Security\PermissionCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminRoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->with(['permissions:id,slug', 'admins:id,name,phone', 'parent:id,name,slug'])
            ->orderBy('hierarchy_level')
            ->orderBy('name')
            ->get();
        $availableParents = Role::query()->orderBy('name')->get(['id', 'name', 'slug']);

        return view('admin.roles.index', compact('roles', 'availableParents'));
    }

    public function permissions(): View
    {
        $roles = Role::query()
            ->with(['permissions:id,slug', 'parent:id,name,slug'])
            ->orderBy('hierarchy_level')
            ->orderBy('name')
            ->get();

        $permissions = Permission::query()
            ->orderBy('group_key')
            ->orderBy('name')
            ->get();

        [$permissionGroups] = $this->buildPermissionGroups($permissions);

        return view('admin.roles.permissions', compact('roles', 'permissionGroups'));
    }

    public function adminAssignments(): View
    {
        $roles = Role::query()
            ->with(['admins:id,name,phone', 'parent:id,name,slug'])
            ->orderBy('hierarchy_level')
            ->orderBy('name')
            ->get();

        $admins = Admin::query()->orderBy('name')->get(['id', 'name', 'phone']);

        return view('admin.roles.admin-assignments', compact('roles', 'admins'));
    }

    public function permissionGroups(): View
    {
        $permissions = Permission::query()
            ->orderBy('group_key')
            ->orderBy('name')
            ->get();

        [, $definedGroups] = $this->buildPermissionGroups($permissions);

        return view('admin.roles.permission-groups', compact('definedGroups'));
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'group_key' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/', 'unique:admin_permission_groups,group_key'],
            'name' => ['required', 'string', 'max:120'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        PermissionGroup::query()->create([
            'group_key' => strtolower(trim((string) $data['group_key'])),
            'name' => trim((string) $data['name']),
            'display_order' => isset($data['display_order']) ? (int) $data['display_order'] : 999,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        app(PermissionCacheService::class)->bumpVersion();

        return redirect()->route('admin.permission-groups.index')->with('success', 'تم إنشاء مجموعة الصلاحيات بنجاح.');
    }

    public function updateGroup(Request $request, PermissionGroup $permissionGroup): RedirectResponse
    {
        $data = $request->validate([
            'group_key' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('admin_permission_groups', 'group_key')->ignore($permissionGroup->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data, $permissionGroup): void {
            $oldKey = (string) $permissionGroup->group_key;
            $newKey = strtolower(trim((string) $data['group_key']));

            $permissionGroup->update([
                'group_key' => $newKey,
                'name' => trim((string) $data['name']),
                'display_order' => isset($data['display_order']) ? (int) $data['display_order'] : 999,
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]);

            if ($oldKey !== $newKey) {
                Permission::query()->where('group_key', $oldKey)->update(['group_key' => $newKey]);
            }
        });

        app(PermissionCacheService::class)->bumpVersion();

        return redirect()->route('admin.permission-groups.index')->with('success', 'تم تحديث مجموعة الصلاحيات بنجاح.');
    }

    public function destroyGroup(PermissionGroup $permissionGroup): RedirectResponse
    {
        $usageCount = Permission::query()->where('group_key', $permissionGroup->group_key)->count();
        if ($usageCount > 0) {
            return redirect()->route('admin.permission-groups.index')->with('error', 'لا يمكن حذف المجموعة لأنها مرتبطة بصلاحيات قائمة.');
        }

        $permissionGroup->delete();
        app(PermissionCacheService::class)->bumpVersion();

        return redirect()->route('admin.permission-groups.index')->with('success', 'تم حذف مجموعة الصلاحيات بنجاح.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:admin_roles,slug'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'distinct', 'exists:admin_permissions,slug'],
            'admin_ids' => ['array'],
            'admin_ids.*' => ['integer', 'exists:admins,id'],
            'parent_role_id' => ['nullable', 'integer', 'exists:admin_roles,id'],
        ]);

        $role = Role::query()->create([
            'name' => $data['name'],
            'slug' => strtolower(trim((string) $data['slug'])),
            'parent_role_id' => isset($data['parent_role_id']) ? (int) $data['parent_role_id'] : null,
            'hierarchy_level' => $this->resolveHierarchyLevel(isset($data['parent_role_id']) ? (int) $data['parent_role_id'] : null),
        ]);

        $permissionIds = Permission::query()
            ->whereIn('slug', $this->normalizeSlugs($data['permissions'] ?? []))
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
        $role->admins()->sync($data['admin_ids'] ?? []);
        app(PermissionCacheService::class)->bumpVersion();

        return redirect()->route('admin.roles.index')->with('success', 'تم إنشاء الدور بنجاح.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'distinct', 'exists:admin_permissions,slug'],
            'admin_ids' => ['array'],
            'admin_ids.*' => ['integer', 'exists:admins,id'],
            'parent_role_id' => [
                'nullable',
                'integer',
                Rule::exists('admin_roles', 'id'),
                Rule::notIn([(int) $role->id]),
            ],
        ]);

        $parentRoleId = isset($data['parent_role_id']) ? (int) $data['parent_role_id'] : null;

        if ($this->wouldCreateCycle($role, $parentRoleId)) {
            return redirect()->route('admin.roles.index')->with('error', 'لا يمكن ربط الدور بشكل دائري في التسلسل الهرمي.');
        }

        $role->update([
            'name' => $data['name'],
            'parent_role_id' => $parentRoleId,
            'hierarchy_level' => $this->resolveHierarchyLevel($parentRoleId),
        ]);

        $permissionIds = Permission::query()
            ->whereIn('slug', $this->normalizeSlugs($data['permissions'] ?? []))
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
        $role->admins()->sync($data['admin_ids'] ?? []);
        app(PermissionCacheService::class)->bumpVersion();

        return redirect()->route('admin.roles.index')->with('success', 'تم تحديث الدور بنجاح.');
    }

    public function updateProfile(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_role_id' => [
                'nullable',
                'integer',
                Rule::exists('admin_roles', 'id'),
                Rule::notIn([(int) $role->id]),
            ],
        ]);

        $parentRoleId = isset($data['parent_role_id']) ? (int) $data['parent_role_id'] : null;

        if ($this->wouldCreateCycle($role, $parentRoleId)) {
            return redirect()->route('admin.roles.index')->with('error', 'لا يمكن ربط الدور بشكل دائري في التسلسل الهرمي.');
        }

        $role->update([
            'name' => $data['name'],
            'parent_role_id' => $parentRoleId,
            'hierarchy_level' => $this->resolveHierarchyLevel($parentRoleId),
        ]);

        app(PermissionCacheService::class)->bumpVersion();

        return redirect()->route('admin.roles.index')->with('success', 'تم تحديث بيانات الدور بنجاح.');
    }

    public function updatePermissions(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'distinct', 'exists:admin_permissions,slug'],
        ]);

        $permissionIds = Permission::query()
            ->whereIn('slug', $this->normalizeSlugs($data['permissions'] ?? []))
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
        app(PermissionCacheService::class)->bumpVersion();

        return redirect()->route('admin.roles.permissions')->with('success', 'تم تحديث صلاحيات الدور بنجاح.');
    }

    public function updateAdmins(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'admin_ids' => ['array'],
            'admin_ids.*' => ['integer', 'exists:admins,id'],
        ]);

        $role->admins()->sync($data['admin_ids'] ?? []);
        app(PermissionCacheService::class)->bumpVersion();

        return redirect()->route('admin.roles.admin-assignments')->with('success', 'تم تحديث ربط الأدمنات بالدور بنجاح.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->slug === 'super-admin') {
            return redirect()->route('admin.roles.index')->with('error', 'لا يمكن حذف دور المدير العام.');
        }

        $role->permissions()->detach();
        $role->admins()->detach();
        $role->delete();
        app(PermissionCacheService::class)->bumpVersion();

        return redirect()->route('admin.roles.index')->with('success', 'تم حذف الدور بنجاح.');
    }

    /**
     * @param array<int, mixed> $slugs
     * @return array<int, string>
     */
    private function normalizeSlugs(array $slugs): array
    {
        return collect($slugs)
            ->filter(fn($slug) => is_string($slug) && trim($slug) !== '')
            ->map(fn($slug) => strtolower(trim((string) $slug)))
            ->unique()
            ->values()
            ->all();
    }

    private function resolveHierarchyLevel(?int $parentRoleId): int
    {
        if (! $parentRoleId) {
            return 0;
        }

        $parentLevel = (int) (Role::query()->whereKey($parentRoleId)->value('hierarchy_level') ?? 0);

        return min(100, max(0, $parentLevel + 1));
    }

    private function wouldCreateCycle(Role $role, ?int $parentRoleId): bool
    {
        if (! $parentRoleId) {
            return false;
        }

        $visited = [];
        $currentId = $parentRoleId;

        while ($currentId > 0) {
            if ($currentId === (int) $role->id) {
                return true;
            }

            if (in_array($currentId, $visited, true)) {
                return true;
            }

            $visited[] = $currentId;
            $next = Role::query()->whereKey($currentId)->value('parent_role_id');
            $currentId = $next !== null ? (int) $next : 0;
        }

        return false;
    }

    /**
     * @return array{0:\Illuminate\Support\Collection,1:\Illuminate\Support\Collection}
     */
    private function buildPermissionGroups($permissions): array
    {
        $groupLabels = (array) config('operations.security.permission_group_labels', []);
        $groupOrder = array_values((array) config('operations.security.permission_group_order', []));
        $definedGroups = PermissionGroup::query()
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'group_key', 'name', 'display_order', 'is_active']);

        $usageByKey = Permission::query()
            ->select('group_key', DB::raw('COUNT(*) as permissions_count'))
            ->groupBy('group_key')
            ->pluck('permissions_count', 'group_key');

        $definedGroups = $definedGroups->map(function (PermissionGroup $group) use ($usageByKey): PermissionGroup {
            $usageCount = (int) ($usageByKey[$group->group_key] ?? 0);
            $group->setAttribute('usage_count', $usageCount);
            $group->setAttribute('can_delete', $usageCount === 0);

            return $group;
        });

        $grouped = $permissions->groupBy(fn(Permission $permission) => $permission->group_key ?: 'general');

        $orderedKeys = collect($groupOrder)
            ->merge($definedGroups->pluck('group_key'))
            ->merge($grouped->keys())
            ->filter(fn($key) => is_string($key) && trim($key) !== '')
            ->map(fn($key) => (string) $key)
            ->unique()
            ->values();

        $permissionGroups = $orderedKeys
            ->filter(fn(string $key) => $grouped->has($key))
            ->mapWithKeys(function (string $key) use ($grouped, $groupLabels, $definedGroups): array {
                $defined = $definedGroups->firstWhere('group_key', $key);

                return [
                    $key => [
                        'label' => $defined?->name ?: (string) ($groupLabels[$key] ?? str_replace('_', ' ', $key)),
                        'permissions' => $grouped->get($key),
                    ],
                ];
            });

        return [$permissionGroups, $definedGroups];
    }
}
