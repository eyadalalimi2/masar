<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Admin\Permission;
use App\Models\Admin\PermissionGroup;
use App\Models\Admin\Role;
use App\Services\Security\PermissionCacheService;
use Illuminate\Database\Seeder;

class AdminRbacSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'عرض لوحة المعلومات', 'slug' => 'dashboard.view', 'group_key' => 'dashboard'],
            ['name' => 'إدارة المستخدمين', 'slug' => 'users.manage', 'group_key' => 'users'],
            ['name' => 'التحقق من المستخدمين', 'slug' => 'users.verify', 'group_key' => 'users'],
            ['name' => 'إدارة الكيانات', 'slug' => 'organizations.manage', 'group_key' => 'organizations'],
            ['name' => 'إدارة المنتجات', 'slug' => 'products.manage', 'group_key' => 'catalog'],
            ['name' => 'إدارة الطلبات', 'slug' => 'orders.manage', 'group_key' => 'orders'],
            ['name' => 'إدارة التوصيل', 'slug' => 'delivery.manage', 'group_key' => 'delivery'],
            ['name' => 'إدارة المخزون', 'slug' => 'inventory.manage', 'group_key' => 'inventory'],
            ['name' => 'إدارة المالية', 'slug' => 'finance.manage', 'group_key' => 'finance'],
            ['name' => 'إدارة المدن والمناطق', 'slug' => 'locations.manage', 'group_key' => 'locations'],
            ['name' => 'إدارة المحتوى', 'slug' => 'content.manage', 'group_key' => 'content'],
            ['name' => 'إدارة التسعير والعمولات', 'slug' => 'pricing.manage', 'group_key' => 'pricing'],
            ['name' => 'عرض التقارير', 'slug' => 'reports.view', 'group_key' => 'reports'],
            ['name' => 'إدارة المهام', 'slug' => 'tasks.manage', 'group_key' => 'tasks'],
            ['name' => 'إدارة الإعدادات', 'slug' => 'settings.manage', 'group_key' => 'settings'],
            ['name' => 'إدارة الأدوار', 'slug' => 'roles.manage', 'group_key' => 'access'],
            ['name' => 'عرض السجلات', 'slug' => 'logs.view', 'group_key' => 'observability'],
            ['name' => 'إدارة الإشعارات', 'slug' => 'notifications.manage', 'group_key' => 'notifications'],
        ];

        $groupLabels = (array) config('operations.security.permission_group_labels', []);
        $groupOrder = array_values((array) config('operations.security.permission_group_order', []));
        $groupOrderLookup = [];

        foreach ($groupOrder as $index => $groupKey) {
            if (is_string($groupKey) && trim($groupKey) !== '') {
                $groupOrderLookup[$groupKey] = $index + 1;
            }
        }

        $usedGroupKeys = [];

        foreach ($defaults as $permission) {
            $groupKey = (string) ($permission['group_key'] ?? 'general');
            $usedGroupKeys[$groupKey] = true;

            Permission::query()->updateOrCreate(
                ['slug' => strtolower(trim((string) $permission['slug']))],
                [
                    'name' => $permission['name'],
                    'group_key' => $groupKey,
                ]
            );
        }

        foreach (array_keys($usedGroupKeys) as $groupKey) {
            PermissionGroup::query()->updateOrCreate(
                ['group_key' => $groupKey],
                [
                    'name' => (string) ($groupLabels[$groupKey] ?? str_replace('_', ' ', $groupKey)),
                    'display_order' => (int) ($groupOrderLookup[$groupKey] ?? 999),
                    'is_active' => true,
                ]
            );
        }

        $superAdminRole = Role::query()->firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'مدير عام', 'parent_role_id' => null, 'hierarchy_level' => 0]
        );

        if ($superAdminRole->hierarchy_level !== 0) {
            $superAdminRole->update(['hierarchy_level' => 0]);
        }

        $superAdminRole->permissions()->sync(Permission::query()->pluck('id')->all());

        $roleBlueprints = [
            [
                'name' => 'مدير العمليات',
                'slug' => 'operations-manager',
                'parent_slug' => null,
                'permissions' => [
                    'dashboard.view',
                    'orders.manage',
                    'delivery.manage',
                    'inventory.manage',
                    'tasks.manage',
                    'reports.view',
                    'notifications.manage',
                ],
            ],
            [
                'name' => 'مسؤول المالية',
                'slug' => 'finance-manager',
                'parent_slug' => null,
                'permissions' => [
                    'dashboard.view',
                    'finance.manage',
                    'reports.view',
                    'logs.view',
                    'notifications.manage',
                ],
            ],
            [
                'name' => 'مسؤول الكتالوج والمحتوى',
                'slug' => 'catalog-content-manager',
                'parent_slug' => null,
                'permissions' => [
                    'dashboard.view',
                    'products.manage',
                    'content.manage',
                    'pricing.manage',
                    'reports.view',
                ],
            ],
            [
                'name' => 'مسؤول خدمة المستخدمين',
                'slug' => 'user-support-manager',
                'parent_slug' => null,
                'permissions' => [
                    'dashboard.view',
                    'users.manage',
                    'users.verify',
                    'organizations.manage',
                    'notifications.manage',
                ],
            ],
            [
                'name' => 'مراقب النظام',
                'slug' => 'system-auditor',
                'parent_slug' => null,
                'permissions' => [
                    'dashboard.view',
                    'reports.view',
                    'logs.view',
                ],
            ],
            [
                'name' => 'مسؤول الأدوار والصلاحيات',
                'slug' => 'access-control-manager',
                'parent_slug' => null,
                'permissions' => [
                    'dashboard.view',
                    'roles.manage',
                    'settings.manage',
                    'users.manage',
                    'logs.view',
                ],
            ],
        ];

        $rolesBySlug = [
            'super-admin' => $superAdminRole,
        ];

        foreach ($roleBlueprints as $blueprint) {
            $parentRole = null;
            if (is_string($blueprint['parent_slug']) && isset($rolesBySlug[$blueprint['parent_slug']])) {
                $parentRole = $rolesBySlug[$blueprint['parent_slug']];
            }

            $hierarchyLevel = $parentRole ? ((int) $parentRole->hierarchy_level + 1) : 0;

            $role = Role::query()->updateOrCreate(
                ['slug' => $blueprint['slug']],
                [
                    'name' => $blueprint['name'],
                    'parent_role_id' => $parentRole?->id,
                    'hierarchy_level' => $hierarchyLevel,
                ]
            );

            $permissionIds = Permission::query()
                ->whereIn('slug', $blueprint['permissions'])
                ->pluck('id')
                ->all();

            $role->permissions()->sync($permissionIds);
            $rolesBySlug[$blueprint['slug']] = $role;
        }

        $adminBlueprints = [
            [
                'name' => 'أدمن العمليات',
                'phone' => '770450011',
                'status' => 'active',
                'password' => '123456',
                'roles' => ['operations-manager'],
            ],
            [
                'name' => 'أدمن المالية',
                'phone' => '770450012',
                'status' => 'active',
                'password' => '123456',
                'roles' => ['finance-manager'],
            ],
            [
                'name' => 'أدمن المحتوى',
                'phone' => '770450013',
                'status' => 'active',
                'password' => '123456',
                'roles' => ['catalog-content-manager'],
            ],
            [
                'name' => 'أدمن الصلاحيات',
                'phone' => '770450014',
                'status' => 'active',
                'password' => '123456',
                'roles' => ['access-control-manager'],
            ],
        ];

        foreach ($adminBlueprints as $blueprint) {
            $admin = Admin::query()->updateOrCreate(
                ['phone' => $blueprint['phone']],
                [
                    'name' => $blueprint['name'],
                    'status' => $blueprint['status'],
                    'password' => $blueprint['password'],
                ]
            );

            $roleIds = Role::query()
                ->whereIn('slug', $blueprint['roles'])
                ->pluck('id')
                ->all();

            if ($roleIds !== []) {
                $admin->roles()->sync($roleIds);
            }
        }

        $firstAdmin = Admin::query()->orderBy('id')->first();
        if ($firstAdmin && ! $firstAdmin->roles()->exists()) {
            $firstAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }

        app(PermissionCacheService::class)->bumpVersion();
    }
}
