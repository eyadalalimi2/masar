<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const MAP_TABLE = '__account_migration_map';

    public function up(): void
    {
        if (! Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->string('account_type', 40)->index();
                $table->string('owner_type', 120)->index();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->decimal('balance', 14, 2)->default(0);
                $table->string('currency', 3)->default('YER');
                $table->string('status', 64)->default('active')->index();

                // Legacy auth/profile fields kept to preserve existing login flows.
                $table->string('name')->nullable();
                $table->string('phone', 30)->nullable()->unique();
                $table->string('password')->nullable();
                $table->text('fcm_token')->nullable();
                $table->rememberToken();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(['account_type', 'owner_type', 'owner_id'], 'accounts_type_owner_unique');
            });
        }

        $this->dropLegacyAccountForeignKeys();

        $this->createMapTable();

        DB::transaction(function (): void {
            $this->seedMapFromExistingAccounts(
                sourceTable: 'branch_accounts',
                accountType: 'branch',
                ownerIdColumn: 'branch_id',
                phoneColumn: 'phone',
                nameColumn: 'name',
                balanceColumn: null
            );

            $this->seedMapFromExistingAccounts(
                sourceTable: 'customer_accounts',
                accountType: 'customer',
                ownerIdColumn: 'customer_id',
                phoneColumn: null,
                nameColumn: 'customer_name',
                balanceColumn: 'balance'
            );

            $this->seedMapFromExistingAccounts(
                sourceTable: 'distributor_accounts',
                accountType: 'distributor',
                ownerIdColumn: 'distributor_id',
                phoneColumn: 'phone',
                nameColumn: 'name',
                balanceColumn: null
            );

            $this->seedMapFromExistingAccounts(
                sourceTable: 'pos_accounts',
                accountType: 'pos',
                ownerIdColumn: 'customer_id',
                phoneColumn: 'phone',
                nameColumn: 'name',
                balanceColumn: null
            );

            $this->seedMapFromExistingAccounts(
                sourceTable: 'workshop_accounts',
                accountType: 'workshop',
                ownerIdColumn: 'customer_id',
                phoneColumn: 'phone',
                nameColumn: 'name',
                balanceColumn: null
            );

            $this->migrateSourceTable(
                sourceTable: 'branch_accounts',
                accountType: 'branch',
                ownerType: 'App\\Models\\Distribution\\Branch',
                ownerIdColumn: 'branch_id',
                nameColumn: 'name',
                phoneColumn: 'phone',
                passwordColumn: 'password',
                statusColumn: 'status',
                balanceColumn: null,
                fcmTokenColumn: 'fcm_token'
            );

            $this->migrateSourceTable(
                sourceTable: 'customer_accounts',
                accountType: 'customer',
                ownerType: 'App\\Models\\Customer\\Customer',
                ownerIdColumn: 'customer_id',
                nameColumn: 'customer_name',
                phoneColumn: null,
                passwordColumn: null,
                statusColumn: null,
                balanceColumn: 'balance',
                fcmTokenColumn: null
            );

            $this->migrateSourceTable(
                sourceTable: 'distributor_accounts',
                accountType: 'distributor',
                ownerType: 'App\\Models\\Distribution\\Distributor',
                ownerIdColumn: 'distributor_id',
                nameColumn: 'name',
                phoneColumn: 'phone',
                passwordColumn: 'password',
                statusColumn: 'status',
                balanceColumn: null,
                fcmTokenColumn: null
            );

            $this->migrateSourceTable(
                sourceTable: 'pos_accounts',
                accountType: 'pos',
                ownerType: 'App\\Models\\Customer\\Customer',
                ownerIdColumn: 'customer_id',
                nameColumn: 'name',
                phoneColumn: 'phone',
                passwordColumn: 'password',
                statusColumn: 'status',
                balanceColumn: null,
                fcmTokenColumn: null
            );

            $this->migrateSourceTable(
                sourceTable: 'workshop_accounts',
                accountType: 'workshop',
                ownerType: 'App\\Models\\Customer\\Customer',
                ownerIdColumn: 'customer_id',
                nameColumn: 'name',
                phoneColumn: 'phone',
                passwordColumn: 'password',
                statusColumn: 'status',
                balanceColumn: null,
                fcmTokenColumn: null
            );

            $this->remapReferences();
        });

        $this->dropMapTable();

        $this->ensureAllReferencedAccountsExist();

        $this->addAccountsForeignKeys();

        Schema::dropIfExists('branch_accounts');
        Schema::dropIfExists('customer_accounts');
        Schema::dropIfExists('distributor_accounts');
        Schema::dropIfExists('pos_accounts');
        Schema::dropIfExists('workshop_accounts');
    }

    public function down(): void
    {
        throw new RuntimeException('This migration is irreversible. Restore from backup if rollback is required.');
    }

    private function createMapTable(): void
    {
        Schema::dropIfExists(self::MAP_TABLE);

        Schema::create(self::MAP_TABLE, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('source_table', 64)->index();
            $table->unsignedBigInteger('source_id')->index();
            $table->unsignedBigInteger('account_id')->index();
            $table->timestamps();

            $table->unique(['source_table', 'source_id'], 'account_migration_source_unique');
        });
    }

    private function dropMapTable(): void
    {
        Schema::dropIfExists(self::MAP_TABLE);
    }

    private function migrateSourceTable(
        string $sourceTable,
        string $accountType,
        string $ownerType,
        ?string $ownerIdColumn,
        ?string $nameColumn,
        ?string $phoneColumn,
        ?string $passwordColumn,
        ?string $statusColumn,
        ?string $balanceColumn,
        ?string $fcmTokenColumn,
    ): void {
        if (! Schema::hasTable($sourceTable)) {
            return;
        }

        DB::table($sourceTable . ' as src')
            ->leftJoin(self::MAP_TABLE . ' as map', function ($join) use ($sourceTable): void {
                $join->on('map.source_id', '=', 'src.id')
                    ->where('map.source_table', '=', $sourceTable);
            })
            ->whereNull('map.id')
            ->select('src.*')
            ->orderBy('src.id')
            ->chunkById(500, function ($rows) use (
                $sourceTable,
                $accountType,
                $ownerType,
                $ownerIdColumn,
                $nameColumn,
                $phoneColumn,
                $passwordColumn,
                $statusColumn,
                $balanceColumn,
                $fcmTokenColumn
            ): void {
                foreach ($rows as $row) {
                    $ownerId = $ownerIdColumn !== null ? ($row->{$ownerIdColumn} ?? null) : null;
                    $name = $nameColumn !== null ? ($row->{$nameColumn} ?? null) : null;
                    $phone = $phoneColumn !== null ? ($row->{$phoneColumn} ?? null) : null;
                    $password = $passwordColumn !== null ? ($row->{$passwordColumn} ?? null) : null;
                    $status = $statusColumn !== null ? ($row->{$statusColumn} ?? 'active') : 'active';
                    $balance = $balanceColumn !== null ? (float) ($row->{$balanceColumn} ?? 0) : 0.0;
                    $fcmToken = $fcmTokenColumn !== null ? ($row->{$fcmTokenColumn} ?? null) : null;

                    $accountId = DB::table('accounts')->insertGetId([
                        'uuid' => (string) Str::uuid(),
                        'account_type' => $accountType,
                        'owner_type' => $ownerType,
                        'owner_id' => $ownerId,
                        'balance' => $balance,
                        'currency' => 'YER',
                        'status' => in_array($status, ['active', 'inactive'], true) ? $status : 'active',
                        'name' => is_string($name) ? trim($name) : null,
                        'phone' => is_string($phone) && trim($phone) !== '' ? trim($phone) : null,
                        'password' => is_string($password) && trim($password) !== '' ? $password : null,
                        'fcm_token' => is_string($fcmToken) && trim($fcmToken) !== '' ? $fcmToken : null,
                        'remember_token' => $row->remember_token ?? null,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                        'deleted_at' => $row->deleted_at ?? null,
                    ]);

                    DB::table(self::MAP_TABLE)->insert([
                        'source_table' => $sourceTable,
                        'source_id' => (int) $row->id,
                        'account_id' => (int) $accountId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }, 'src.id', 'id');
    }

    private function seedMapFromExistingAccounts(
        string $sourceTable,
        string $accountType,
        ?string $ownerIdColumn,
        ?string $phoneColumn,
        ?string $nameColumn,
        ?string $balanceColumn,
    ): void {
        if (! Schema::hasTable($sourceTable) || ! Schema::hasTable('accounts')) {
            return;
        }

        DB::table($sourceTable)
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($sourceTable, $accountType, $ownerIdColumn, $phoneColumn, $nameColumn, $balanceColumn): void {
                foreach ($rows as $row) {
                    $alreadyMapped = DB::table(self::MAP_TABLE)
                        ->where('source_table', $sourceTable)
                        ->where('source_id', (int) $row->id)
                        ->exists();

                    if ($alreadyMapped) {
                        continue;
                    }

                    $query = DB::table('accounts')->where('account_type', $accountType);

                    $ownerId = $ownerIdColumn !== null ? ($row->{$ownerIdColumn} ?? null) : null;
                    if ($ownerId !== null) {
                        $query->where('owner_id', (int) $ownerId);
                    } elseif ($phoneColumn !== null && ! empty($row->{$phoneColumn})) {
                        $query->where('phone', (string) $row->{$phoneColumn});
                    } elseif ($nameColumn !== null && ! empty($row->{$nameColumn})) {
                        $query->where('name', (string) $row->{$nameColumn});

                        if ($balanceColumn !== null && isset($row->{$balanceColumn})) {
                            $query->where('balance', (float) $row->{$balanceColumn});
                        }
                    } else {
                        continue;
                    }

                    $accountId = $query->value('id');

                    if ($accountId === null) {
                        continue;
                    }

                    DB::table(self::MAP_TABLE)->insert([
                        'source_table' => $sourceTable,
                        'source_id' => (int) $row->id,
                        'account_id' => (int) $accountId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }, 'id');
    }

    private function remapReferences(): void
    {
        $this->remapColumn('transactions', 'customer_account_id', 'customer_accounts');
        $this->remapColumn('pos_sales', 'pos_account_id', 'pos_accounts');
        $this->remapColumn('pos_local_products', 'pos_account_id', 'pos_accounts');
        $this->remapColumn('pos_customers', 'pos_account_id', 'pos_accounts');
        $this->remapColumn('workshop_services', 'workshop_id', 'workshop_accounts');
        $this->remapColumn('workshop_appointments', 'workshop_id', 'workshop_accounts');
        $this->remapColumn('workshop_service_orders', 'workshop_id', 'workshop_accounts');
        $this->remapColumn('workshop_purchase_orders', 'workshop_id', 'workshop_accounts');

        $this->remapPortalPermissions('branch', 'branch_accounts');
        $this->remapPortalPermissions('distributor', 'distributor_accounts');
        $this->remapPortalPermissions('pos', 'pos_accounts');
        $this->remapPortalPermissions('workshop', 'workshop_accounts');

        $this->remapWebAlerts('branch_account', 'branch_accounts');
        $this->remapWebAlerts('distributor_account', 'distributor_accounts');
        $this->remapWebAlerts('pos_account', 'pos_accounts');
        $this->remapWebAlerts('workshop_account', 'workshop_accounts');
        $this->remapWebAlerts('customer_account', 'customer_accounts');
    }

    private function remapColumn(string $table, string $column, string $sourceTable): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::statement(
            "UPDATE {$table} t
            INNER JOIN " . self::MAP_TABLE . " m
                ON m.source_table = ?
               AND m.source_id = t.{$column}
            SET t.{$column} = m.account_id",
            [$sourceTable]
        );
    }

    private function remapPortalPermissions(string $guardName, string $sourceTable): void
    {
        if (! Schema::hasTable('portal_account_permissions')) {
            return;
        }

        DB::statement(
            'UPDATE portal_account_permissions p
            INNER JOIN ' . self::MAP_TABLE . ' m
                ON m.source_table = ?
               AND m.source_id = p.account_id
            SET p.account_id = m.account_id
            WHERE p.guard_name = ?',
            [$sourceTable, $guardName]
        );
    }

    private function remapWebAlerts(string $recipientType, string $sourceTable): void
    {
        if (! Schema::hasTable('web_alerts')) {
            return;
        }

        DB::statement(
            'UPDATE web_alerts w
            INNER JOIN ' . self::MAP_TABLE . ' m
                ON m.source_table = ?
               AND m.source_id = w.recipient_id
            SET w.recipient_id = m.account_id
            WHERE w.recipient_type = ?',
            [$sourceTable, $recipientType]
        );
    }

    private function dropLegacyAccountForeignKeys(): void
    {
        $this->dropForeignKey('transactions', 'transactions_customer_account_id_foreign');
        $this->dropForeignKey('pos_sales', 'pos_sales_pos_account_id_foreign');
        $this->dropForeignKey('pos_local_products', 'pos_local_products_pos_account_id_foreign');
        $this->dropForeignKey('workshop_services', 'workshop_services_workshop_id_foreign');
        $this->dropForeignKey('workshop_appointments', 'workshop_appointments_workshop_id_foreign');
        $this->dropForeignKey('workshop_service_orders', 'workshop_service_orders_workshop_id_foreign');
        $this->dropForeignKey('workshop_purchase_orders', 'workshop_purchase_orders_workshop_id_foreign');
        $this->dropForeignKey('pos_customers', 'pos_customers_pos_account_id_foreign');
    }

    private function addAccountsForeignKeys(): void
    {
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'customer_account_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreign('customer_account_id', 'transactions_customer_account_id_foreign')
                    ->references('id')
                    ->on('accounts')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');
            });
        }

        if (Schema::hasTable('pos_sales') && Schema::hasColumn('pos_sales', 'pos_account_id')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                $table->foreign('pos_account_id', 'pos_sales_pos_account_id_foreign')
                    ->references('id')
                    ->on('accounts')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');
            });
        }

        if (Schema::hasTable('pos_local_products') && Schema::hasColumn('pos_local_products', 'pos_account_id')) {
            Schema::table('pos_local_products', function (Blueprint $table) {
                $table->foreign('pos_account_id', 'pos_local_products_pos_account_id_foreign')
                    ->references('id')
                    ->on('accounts')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');
            });
        }

        if (Schema::hasTable('pos_customers') && Schema::hasColumn('pos_customers', 'pos_account_id')) {
            Schema::table('pos_customers', function (Blueprint $table) {
                $table->foreign('pos_account_id', 'pos_customers_pos_account_id_foreign')
                    ->references('id')
                    ->on('accounts')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');
            });
        }

        if (Schema::hasTable('workshop_services') && Schema::hasColumn('workshop_services', 'workshop_id')) {
            Schema::table('workshop_services', function (Blueprint $table) {
                $table->foreign('workshop_id', 'workshop_services_workshop_id_foreign')
                    ->references('id')
                    ->on('accounts')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');
            });
        }

        if (Schema::hasTable('workshop_appointments') && Schema::hasColumn('workshop_appointments', 'workshop_id')) {
            Schema::table('workshop_appointments', function (Blueprint $table) {
                $table->foreign('workshop_id', 'workshop_appointments_workshop_id_foreign')
                    ->references('id')
                    ->on('accounts')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');
            });
        }

        if (Schema::hasTable('workshop_service_orders') && Schema::hasColumn('workshop_service_orders', 'workshop_id')) {
            Schema::table('workshop_service_orders', function (Blueprint $table) {
                $table->foreign('workshop_id', 'workshop_service_orders_workshop_id_foreign')
                    ->references('id')
                    ->on('accounts')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');
            });
        }

        if (Schema::hasTable('workshop_purchase_orders') && Schema::hasColumn('workshop_purchase_orders', 'workshop_id')) {
            Schema::table('workshop_purchase_orders', function (Blueprint $table) {
                $table->foreign('workshop_id', 'workshop_purchase_orders_workshop_id_foreign')
                    ->references('id')
                    ->on('accounts')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');
            });
        }
    }

    private function ensureAllReferencedAccountsExist(): void
    {
        $this->createMissingAccountsFromReference('transactions', 'customer_account_id', 'customer');
        $this->createMissingAccountsFromReference('pos_sales', 'pos_account_id', 'pos');
        $this->createMissingAccountsFromReference('pos_local_products', 'pos_account_id', 'pos');
        $this->createMissingAccountsFromReference('pos_customers', 'pos_account_id', 'pos');
        $this->createMissingAccountsFromReference('workshop_services', 'workshop_id', 'workshop');
        $this->createMissingAccountsFromReference('workshop_appointments', 'workshop_id', 'workshop');
        $this->createMissingAccountsFromReference('workshop_service_orders', 'workshop_id', 'workshop');
        $this->createMissingAccountsFromReference('workshop_purchase_orders', 'workshop_id', 'workshop');
    }

    private function createMissingAccountsFromReference(string $table, string $column, string $accountType): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $missingIds = DB::table($table . ' as t')
            ->leftJoin('accounts as a', 'a.id', '=', 't.' . $column)
            ->whereNotNull('t.' . $column)
            ->whereNull('a.id')
            ->distinct()
            ->pluck('t.' . $column)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->values();

        foreach ($missingIds as $id) {
            DB::table('accounts')->updateOrInsert(
                ['id' => $id],
                [
                    'uuid' => (string) Str::uuid(),
                    'account_type' => $accountType,
                    'owner_type' => 'App\\Models\\Customer\\Customer',
                    'owner_id' => null,
                    'balance' => 0,
                    'currency' => 'YER',
                    'status' => 'inactive',
                    'name' => 'legacy-orphan-' . $accountType . '-' . $id,
                    'phone' => null,
                    'password' => null,
                    'fcm_token' => null,
                    'remember_token' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ]
            );
        }
    }

    private function dropForeignKey(string $table, string $foreignKeyName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $table) use ($foreignKeyName): void {
                $table->dropForeign($foreignKeyName);
            });
        } catch (Throwable) {
            // Ignore when FK is absent to keep migration idempotent across environments.
        }
    }
};
