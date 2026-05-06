<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->renameOrderCreatorColumn();
        $this->normalizeOrderStatusHistoryTimestamp();
    }

    public function down(): void
    {
        $this->restoreOrderCreatorColumn();
        $this->restoreOrderStatusHistoryTimestamp();
    }

    private function renameOrderCreatorColumn(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'created_by')) {
            return;
        }

        DB::statement('ALTER TABLE `orders` CHANGE `created_by` `created_by_agent_id` BIGINT UNSIGNED NOT NULL');

        if ($this->hasIndex('orders', 'orders_created_by_foreign')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropIndex('orders_created_by_foreign');
            });
        }

        if (! $this->hasIndex('orders', 'orders_created_by_agent_id_idx')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->index('created_by_agent_id', 'orders_created_by_agent_id_idx');
            });
        }
    }

    private function normalizeOrderStatusHistoryTimestamp(): void
    {
        if (! Schema::hasTable('order_status_histories') || ! Schema::hasColumn('order_status_histories', 'changed_at')) {
            return;
        }

        if (Schema::hasColumn('order_status_histories', 'created_at')) {
            DB::statement('UPDATE `order_status_histories` SET `created_at` = COALESCE(`changed_at`, `created_at`)');
        }

        Schema::table('order_status_histories', function (Blueprint $table): void {
            $table->dropColumn('changed_at');
        });
    }

    private function restoreOrderCreatorColumn(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'created_by_agent_id')) {
            return;
        }

        DB::statement('ALTER TABLE `orders` CHANGE `created_by_agent_id` `created_by` BIGINT UNSIGNED NOT NULL');

        if ($this->hasIndex('orders', 'orders_created_by_agent_id_idx')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropIndex('orders_created_by_agent_id_idx');
            });
        }

        if (! $this->hasIndex('orders', 'orders_created_by_foreign')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->index('created_by', 'orders_created_by_foreign');
            });
        }
    }

    private function restoreOrderStatusHistoryTimestamp(): void
    {
        if (! Schema::hasTable('order_status_histories') || Schema::hasColumn('order_status_histories', 'changed_at')) {
            return;
        }

        Schema::table('order_status_histories', function (Blueprint $table): void {
            $table->timestamp('changed_at')->nullable()->after('note');
        });

        if (Schema::hasColumn('order_status_histories', 'created_at')) {
            DB::statement('UPDATE `order_status_histories` SET `changed_at` = `created_at` WHERE `changed_at` IS NULL');
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        $rows = DB::select(sprintf('SHOW INDEX FROM `%s`', str_replace('`', '``', $table)));

        foreach ($rows as $row) {
            if ((string) $row->Key_name === $indexName) {
                return true;
            }
        }

        return false;
    }
};
