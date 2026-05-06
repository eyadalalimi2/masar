<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = [
        'products',
        'orders',
        'customers',
        'suppliers',
        'distributors',
        'branches',
        'payments',
        'transactions',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            if (! Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->softDeletes();
                });
            }

            $this->ensureDeletedAtIndex($tableName);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'deleted_at')) {
                continue;
            }

            $indexName = $this->deletedAtIndexName($tableName);

            Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
                try {
                    $table->dropIndex($indexName);
                } catch (\Throwable) {
                    // Ignore when index does not exist.
                }

                $table->dropSoftDeletes();
            });
        }
    }

    private function ensureDeletedAtIndex(string $tableName): void
    {
        $indexName = $this->deletedAtIndexName($tableName);

        $exists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('INDEX_NAME', $indexName)
            ->exists();

        if ($exists) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
            $table->index('deleted_at', $indexName);
        });
    }

    private function deletedAtIndexName(string $tableName): string
    {
        return $tableName . '_deleted_at_idx';
    }
};
