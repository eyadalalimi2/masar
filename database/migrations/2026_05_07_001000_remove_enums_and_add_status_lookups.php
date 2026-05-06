<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createLookupTables();
        $this->seedLookupTables();
        $this->convertAllEnumsToVarchar();
    }

    public function down(): void
    {
        // Intentionally keep VARCHAR columns for forward compatibility.
        Schema::dropIfExists('order_statuses');
        Schema::dropIfExists('payment_statuses');
        Schema::dropIfExists('account_statuses');
    }

    private function createLookupTables(): void
    {
        if (! Schema::hasTable('order_statuses')) {
            Schema::create('order_statuses', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 64)->unique();
                $table->string('name', 120);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('payment_statuses')) {
            Schema::create('payment_statuses', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 64)->unique();
                $table->string('name', 120);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('account_statuses')) {
            Schema::create('account_statuses', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 64)->unique();
                $table->string('name', 120);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    private function seedLookupTables(): void
    {
        $now = now();

        DB::table('order_statuses')->upsert([
            ['code' => 'pending', 'name' => 'Pending', 'sort_order' => 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'approved', 'name' => 'Approved', 'sort_order' => 20, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'assigned', 'name' => 'Assigned', 'sort_order' => 30, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'accepted', 'name' => 'Accepted', 'sort_order' => 35, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'picked_up', 'name' => 'Picked Up', 'sort_order' => 40, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'out_for_delivery', 'name' => 'Out For Delivery', 'sort_order' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'delivered', 'name' => 'Delivered', 'sort_order' => 60, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'cancelled', 'name' => 'Cancelled', 'sort_order' => 70, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ], ['code'], ['name', 'sort_order', 'is_active', 'updated_at']);

        DB::table('payment_statuses')->upsert([
            ['code' => 'paid', 'name' => 'Paid', 'sort_order' => 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'partial', 'name' => 'Partial', 'sort_order' => 20, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'unpaid', 'name' => 'Unpaid', 'sort_order' => 30, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ], ['code'], ['name', 'sort_order', 'is_active', 'updated_at']);

        DB::table('account_statuses')->upsert([
            ['code' => 'active', 'name' => 'Active', 'sort_order' => 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'inactive', 'name' => 'Inactive', 'sort_order' => 20, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ], ['code'], ['name', 'sort_order', 'is_active', 'updated_at']);
    }

    private function convertAllEnumsToVarchar(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $database = DB::getDatabaseName();
        $enumColumns = DB::table('information_schema.COLUMNS')
            ->select(['TABLE_NAME', 'COLUMN_NAME', 'IS_NULLABLE', 'COLUMN_DEFAULT'])
            ->where('TABLE_SCHEMA', $database)
            ->where('DATA_TYPE', 'enum')
            ->get();

        foreach ($enumColumns as $col) {
            $table = (string) $col->TABLE_NAME;
            $column = (string) $col->COLUMN_NAME;
            $nullable = ((string) $col->IS_NULLABLE) === 'YES';
            $default = $col->COLUMN_DEFAULT;

            $sql = sprintf(
                'ALTER TABLE `%s` MODIFY `%s` VARCHAR(64) %s',
                str_replace('`', '``', $table),
                str_replace('`', '``', $column),
                $nullable ? 'NULL' : 'NOT NULL'
            );

            if ($default !== null) {
                $safeDefault = str_replace("'", "''", (string) $default);
                $sql .= " DEFAULT '" . $safeDefault . "'";
            }

            DB::statement($sql);
        }
    }
};
