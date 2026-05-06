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
    private array $tablesMissingBothTimestamps = [
        'cache',
        'cache_locks',
        'failed_jobs',
        'migrations',
        'sessions',
    ];

    /**
     * @var array<int, string>
     */
    private array $tablesMissingUpdatedAtOnly = [
        'jobs',
        'job_batches',
        'password_reset_tokens',
    ];

    /**
     * @var array<int, string>
     */
    private array $sensitiveTablesMissingDeletedAt = [
        'agents',
        'branch_product_stocks',
        'branch_stock_movements',
        'consumers',
        'distributor_location_logs',
        'distributor_order_events',
        'order_items',
        'order_status_histories',
        'pos_customers',
        'pos_local_products',
        'pos_sales',
        'product_units',
        'product_variants',
        'product_variant_units',
        'web_alerts',
        'workshop_appointments',
        'workshop_purchase_orders',
        'workshop_purchase_order_items',
        'workshop_services',
        'workshop_service_orders',
    ];

    public function up(): void
    {
        foreach ($this->tablesMissingBothTimestamps as $tableName) {
            $this->ensureCreatedAtAndUpdatedAt($tableName);
        }

        foreach ($this->tablesMissingUpdatedAtOnly as $tableName) {
            $this->ensureUpdatedAt($tableName);
        }

        foreach ($this->sensitiveTablesMissingDeletedAt as $tableName) {
            $this->ensureDeletedAt($tableName);
            $this->ensureDeletedAtIndex($tableName);
        }
    }

    public function down(): void
    {
        foreach ($this->sensitiveTablesMissingDeletedAt as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'deleted_at')) {
                continue;
            }

            $indexName = $this->deletedAtIndexName($tableName);
            $hasIndex = $this->hasIndex($tableName, $indexName);
            Schema::table($tableName, function (Blueprint $table) use ($indexName, $hasIndex): void {
                if ($hasIndex) {
                    $table->dropIndex($indexName);
                }

                $table->dropSoftDeletes();
            });
        }

        foreach ($this->tablesMissingUpdatedAtOnly as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'updated_at')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('updated_at');
            });
        }

        foreach ($this->tablesMissingBothTimestamps as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (Schema::hasColumn($tableName, 'created_at')) {
                    $table->dropColumn('created_at');
                }

                if (Schema::hasColumn($tableName, 'updated_at')) {
                    $table->dropColumn('updated_at');
                }
            });
        }
    }

    private function ensureCreatedAtAndUpdatedAt(string $tableName): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        $needsCreatedAt = ! Schema::hasColumn($tableName, 'created_at');
        $needsUpdatedAt = ! Schema::hasColumn($tableName, 'updated_at');

        if (! $needsCreatedAt && ! $needsUpdatedAt) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($needsCreatedAt, $needsUpdatedAt): void {
            if ($needsCreatedAt) {
                $table->timestamp('created_at')->nullable();
            }

            if ($needsUpdatedAt) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    private function ensureUpdatedAt(string $tableName): void
    {
        if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'updated_at')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->timestamp('updated_at')->nullable();
        });
    }

    private function ensureDeletedAt(string $tableName): void
    {
        if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'deleted_at')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    private function ensureDeletedAtIndex(string $tableName): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'deleted_at')) {
            return;
        }

        $indexName = $this->deletedAtIndexName($tableName);
        if ($this->hasIndex($tableName, $indexName)) {
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

    private function hasIndex(string $tableName, string $indexName): bool
    {
        if (! Schema::hasTable($tableName)) {
            return false;
        }

        $rows = DB::select(sprintf('SHOW INDEX FROM `%s`', str_replace('`', '``', $tableName)));

        foreach ($rows as $row) {
            if ((string) $row->Key_name === $indexName) {
                return true;
            }
        }

        return false;
    }
};
