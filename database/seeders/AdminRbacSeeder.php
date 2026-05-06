<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use Illuminate\Database\Seeder;

class AdminRbacSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'عرض لوحة المعلومات', 'slug' => 'dashboard.view'],
            ['name' => 'إدارة المستخدمين', 'slug' => 'users.manage'],
            ['name' => 'التحقق من المستخدمين', 'slug' => 'users.verify'],
            ['name' => 'إدارة الكيانات', 'slug' => 'organizations.manage'],
            ['name' => 'إدارة المنتجات', 'slug' => 'products.manage'],
            ['name' => 'إدارة الطلبات', 'slug' => 'orders.manage'],
            ['name' => 'إدارة التوصيل', 'slug' => 'delivery.manage'],
            ['name' => 'إدارة المخزون', 'slug' => 'inventory.manage'],
            ['name' => 'إدارة المالية', 'slug' => 'finance.manage'],
            ['name' => 'إدارة المدن والمناطق', 'slug' => 'locations.manage'],
            ['name' => 'إدارة المحتوى', 'slug' => 'content.manage'],
            ['name' => 'إدارة التسعير والعمولات', 'slug' => 'pricing.manage'],
            ['name' => 'عرض التقارير', 'slug' => 'reports.view'],
            ['name' => 'إدارة المهام', 'slug' => 'tasks.manage'],
            ['name' => 'إدارة الإعدادات', 'slug' => 'settings.manage'],
            ['name' => 'إدارة الأدوار', 'slug' => 'roles.manage'],
            ['name' => 'عرض السجلات', 'slug' => 'logs.view'],
            ['name' => 'إدارة الإشعارات', 'slug' => 'notifications.manage'],
        ];

        foreach ($defaults as $permission) {
            Permission::query()->updateOrCreate(
                ['slug' => $permission['slug']],
                ['name' => $permission['name']]
            );
        }

        $superAdminRole = Role::query()->firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'مدير عام']
        );

        $superAdminRole->permissions()->sync(Permission::query()->pluck('id')->all());

        $firstAdmin = Admin::query()->orderBy('id')->first();
        if ($firstAdmin && ! $firstAdmin->roles()->exists()) {
            $firstAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }
    }
}
