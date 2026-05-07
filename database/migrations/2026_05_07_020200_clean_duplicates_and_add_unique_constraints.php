<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /** @var \App\Services\Data\UniqueConstraintMaintenanceService $service */
        $service = app('App\\Services\\Data\\UniqueConstraintMaintenanceService');

        // Requirement: cleanup duplicate data before adding unique constraints.
        $service->cleanupDuplicates(false);
        $service->ensureUniqueConstraints();
    }

    public function down(): void
    {
        $drops = [
            ['table' => 'suppliers', 'index' => 'suppliers_phone_unique'],
            ['table' => 'suppliers', 'index' => 'suppliers_email_unique'],
            ['table' => 'branches', 'index' => 'branches_phone_unique'],
            ['table' => 'distributors', 'index' => 'distributors_phone_unique'],
            ['table' => 'products', 'index' => 'products_sku_unique'],
            ['table' => 'products', 'index' => 'products_barcode_unique'],
        ];

        foreach ($drops as $drop) {
            if (! Schema::hasTable($drop['table'])) {
                continue;
            }

            $exists = \Illuminate\Support\Facades\DB::table('information_schema.statistics')
                ->where('table_schema', \Illuminate\Support\Facades\DB::getDatabaseName())
                ->where('table_name', $drop['table'])
                ->where('index_name', $drop['index'])
                ->exists();

            if (! $exists) {
                continue;
            }

            $quotedTable = '`' . str_replace('`', '``', $drop['table']) . '`';
            $quotedIndex = '`' . str_replace('`', '``', $drop['index']) . '`';
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE {$quotedTable} DROP INDEX {$quotedIndex}");
        }
    }
};
