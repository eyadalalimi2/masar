<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_permissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('admin_permissions', 'group_key')) {
                $table->string('group_key', 64)->default('general')->after('slug')->index();
            }
        });

        Schema::table('admin_roles', function (Blueprint $table): void {
            if (! Schema::hasColumn('admin_roles', 'parent_role_id')) {
                $table->unsignedBigInteger('parent_role_id')->nullable()->after('slug')->index();
            }

            if (! Schema::hasColumn('admin_roles', 'hierarchy_level')) {
                $table->unsignedSmallInteger('hierarchy_level')->default(0)->after('parent_role_id')->index();
            }
        });

        if (! $this->foreignKeyExists('admin_roles', 'admin_roles_parent_role_id_foreign')) {
            Schema::table('admin_roles', function (Blueprint $table): void {
                $table->foreign('parent_role_id', 'admin_roles_parent_role_id_foreign')
                    ->references('id')
                    ->on('admin_roles')
                    ->nullOnDelete();
            });
        }

        if (! $this->indexExists('admin_permission_role', 'admin_permission_role_permission_role_idx')) {
            Schema::table('admin_permission_role', function (Blueprint $table): void {
                $table->index(['permission_id', 'role_id'], 'admin_permission_role_permission_role_idx');
            });
        }

        if (! $this->indexExists('admin_role_admin', 'admin_role_admin_role_admin_idx')) {
            Schema::table('admin_role_admin', function (Blueprint $table): void {
                $table->index(['role_id', 'admin_id'], 'admin_role_admin_role_admin_idx');
            });
        }

        if (! $this->indexExists('portal_account_permissions', 'pap_guard_account_granted_idx')) {
            Schema::table('portal_account_permissions', function (Blueprint $table): void {
                $table->index(['guard_name', 'account_id', 'is_granted'], 'pap_guard_account_granted_idx');
            });
        }

        $this->normalizePermissionGroups();
        $this->normalizeRoleHierarchyLevels();
    }

    public function down(): void
    {
        if ($this->indexExists('portal_account_permissions', 'pap_guard_account_granted_idx')) {
            Schema::table('portal_account_permissions', function (Blueprint $table): void {
                $table->dropIndex('pap_guard_account_granted_idx');
            });
        }

        if ($this->indexExists('admin_role_admin', 'admin_role_admin_role_admin_idx')) {
            Schema::table('admin_role_admin', function (Blueprint $table): void {
                $table->dropIndex('admin_role_admin_role_admin_idx');
            });
        }

        if ($this->indexExists('admin_permission_role', 'admin_permission_role_permission_role_idx')) {
            Schema::table('admin_permission_role', function (Blueprint $table): void {
                $table->dropIndex('admin_permission_role_permission_role_idx');
            });
        }

        if ($this->foreignKeyExists('admin_roles', 'admin_roles_parent_role_id_foreign')) {
            Schema::table('admin_roles', function (Blueprint $table): void {
                $table->dropForeign('admin_roles_parent_role_id_foreign');
            });
        }

        Schema::table('admin_roles', function (Blueprint $table): void {
            if (Schema::hasColumn('admin_roles', 'hierarchy_level')) {
                $table->dropColumn('hierarchy_level');
            }

            if (Schema::hasColumn('admin_roles', 'parent_role_id')) {
                $table->dropColumn('parent_role_id');
            }
        });

        Schema::table('admin_permissions', function (Blueprint $table): void {
            if (Schema::hasColumn('admin_permissions', 'group_key')) {
                $table->dropColumn('group_key');
            }
        });
    }

    private function normalizePermissionGroups(): void
    {
        if (! Schema::hasTable('admin_permissions') || ! Schema::hasColumn('admin_permissions', 'group_key')) {
            return;
        }

        $map = [
            'dashboard.view' => 'dashboard',
            'users.manage' => 'users',
            'users.verify' => 'users',
            'organizations.manage' => 'organizations',
            'products.manage' => 'catalog',
            'orders.manage' => 'orders',
            'delivery.manage' => 'delivery',
            'inventory.manage' => 'inventory',
            'finance.manage' => 'finance',
            'locations.manage' => 'locations',
            'content.manage' => 'content',
            'pricing.manage' => 'pricing',
            'reports.view' => 'reports',
            'tasks.manage' => 'tasks',
            'settings.manage' => 'settings',
            'roles.manage' => 'access',
            'logs.view' => 'observability',
            'notifications.manage' => 'notifications',
        ];

        foreach ($map as $slug => $groupKey) {
            DB::table('admin_permissions')
                ->where('slug', $slug)
                ->update(['group_key' => $groupKey]);
        }
    }

    private function normalizeRoleHierarchyLevels(): void
    {
        if (! Schema::hasTable('admin_roles') || ! Schema::hasColumn('admin_roles', 'hierarchy_level')) {
            return;
        }

        DB::table('admin_roles')->whereNull('parent_role_id')->update(['hierarchy_level' => 0]);

        for ($i = 1; $i <= 20; $i++) {
            $updated = DB::update(
                'UPDATE admin_roles r
                 JOIN admin_roles p ON p.id = r.parent_role_id
                 SET r.hierarchy_level = p.hierarchy_level + 1
                 WHERE r.parent_role_id IS NOT NULL
                   AND r.hierarchy_level <> p.hierarchy_level + 1'
            );

            if ((int) $updated === 0) {
                break;
            }
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $dbName = DB::getDatabaseName();
        if (! is_string($dbName) || trim($dbName) === '') {
            return false;
        }

        $index = DB::table('information_schema.statistics')
            ->where('table_schema', $dbName)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->value('index_name');

        return is_string($index) && $index !== '';
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $dbName = DB::getDatabaseName();
        if (! is_string($dbName) || trim($dbName) === '') {
            return false;
        }

        $constraint = DB::table('information_schema.table_constraints')
            ->where('constraint_schema', $dbName)
            ->where('table_name', $table)
            ->where('constraint_name', $constraintName)
            ->where('constraint_type', 'FOREIGN KEY')
            ->value('constraint_name');

        return is_string($constraint) && $constraint !== '';
    }
};
