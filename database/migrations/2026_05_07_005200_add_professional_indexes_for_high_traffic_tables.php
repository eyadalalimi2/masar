<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addOrdersIndexes();
        $this->addProductsIndexes();
        $this->addTransactionsIndexes();
        $this->addInventoryMovementIndexes();
        $this->addDistributorLocationLogIndexes();
    }

    public function down(): void
    {
        $this->dropIndexIfExists('orders', 'orders_supplier_status_created_at_idx');
        $this->dropIndexIfExists('orders', 'orders_buyer_status_created_at_idx');
        $this->dropIndexIfExists('orders', 'orders_distributor_stage_status_updated_at_idx');
        $this->dropIndexIfExists('orders', 'orders_status_updated_at_idx');

        $this->dropIndexIfExists('products', 'products_supplier_status_idx');
        $this->dropIndexIfExists('products', 'products_sku_unique');
        $this->dropIndexIfExists('products', 'products_sku_idx');
        $this->dropIndexIfExists('products', 'products_barcode_unique');
        $this->dropIndexIfExists('products', 'products_barcode_idx');

        $this->dropIndexIfExists('transactions', 'transactions_customer_account_created_at_idx');

        $this->dropIndexIfExists('inventory_movements', 'inventory_movements_branch_type_id_idx');
        $this->dropIndexIfExists('inventory_movements', 'inventory_movements_supplier_created_at_idx');
    }

    private function addOrdersIndexes(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        $this->addIndexIfMissing(
            'orders',
            ['supplier_id', 'status', 'created_at'],
            'orders_supplier_status_created_at_idx'
        );

        $this->addIndexIfMissing(
            'orders',
            ['buyer_type', 'buyer_id', 'status', 'created_at'],
            'orders_buyer_status_created_at_idx'
        );

        $this->addIndexIfMissing(
            'orders',
            ['distributor_id', 'distributor_stage', 'status', 'updated_at'],
            'orders_distributor_stage_status_updated_at_idx'
        );

        $this->addIndexIfMissing(
            'orders',
            ['status', 'updated_at'],
            'orders_status_updated_at_idx'
        );
    }

    private function addProductsIndexes(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        $this->addIndexIfMissing(
            'products',
            ['supplier_id', 'status'],
            'products_supplier_status_idx'
        );

        $this->addConditionalCodeIndex('products', 'sku', 'products_sku_unique', 'products_sku_idx');
        $this->addConditionalCodeIndex('products', 'barcode', 'products_barcode_unique', 'products_barcode_idx');
    }

    private function addTransactionsIndexes(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        $this->addIndexIfMissing(
            'transactions',
            ['customer_account_id', 'created_at'],
            'transactions_customer_account_created_at_idx'
        );
    }

    private function addInventoryMovementIndexes(): void
    {
        if (! Schema::hasTable('inventory_movements')) {
            return;
        }

        $this->addIndexIfMissing(
            'inventory_movements',
            ['branch_id', 'movement_type', 'id'],
            'inventory_movements_branch_type_id_idx'
        );

        $this->addIndexIfMissing(
            'inventory_movements',
            ['supplier_id', 'created_at'],
            'inventory_movements_supplier_created_at_idx'
        );
    }

    private function addDistributorLocationLogIndexes(): void
    {
        if (! Schema::hasTable('distributor_location_logs')) {
            return;
        }

        // Already covered in current schema by composite indexes:
        // distributor_id + created_at and order_id + created_at.
    }

    private function addConditionalCodeIndex(string $table, string $column, string $uniqueName, string $indexName): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        if ($this->hasIndexByName($table, $uniqueName) || $this->hasIndexByName($table, $indexName)) {
            return;
        }

        if ($this->hasIndexByColumns($table, [$column])) {
            return;
        }

        $hasDuplicates = DB::table($table)
            ->select($column)
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();

        Schema::table($table, function (Blueprint $blueprint) use ($column, $uniqueName, $indexName, $hasDuplicates): void {
            if ($hasDuplicates) {
                $blueprint->index([$column], $indexName);

                return;
            }

            $blueprint->unique([$column], $uniqueName);
        });
    }

    private function addIndexIfMissing(string $table, array $columns, string $indexName): void
    {
        if ($this->hasIndexByName($table, $indexName)) {
            return;
        }

        if ($this->hasIndexByColumns($table, $columns)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName): void {
            $blueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (! $this->hasIndexByName($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName): void {
            $blueprint->dropIndex($indexName);
        });
    }

    private function hasIndexByName(string $table, string $indexName): bool
    {
        return array_key_exists($indexName, $this->getTableIndexes($table));
    }

    private function hasIndexByColumns(string $table, array $columns): bool
    {
        foreach ($this->getTableIndexes($table) as $indexColumns) {
            if ($indexColumns === $columns) {
                return true;
            }
        }

        return false;
    }

    private function getTableIndexes(string $table): array
    {
        static $cache = [];

        if (array_key_exists($table, $cache)) {
            return $cache[$table];
        }

        if (! Schema::hasTable($table)) {
            $cache[$table] = [];

            return $cache[$table];
        }

        $rows = DB::select(sprintf('SHOW INDEX FROM `%s`', str_replace('`', '``', $table)));
        $indexes = [];

        foreach ($rows as $row) {
            $key = (string) $row->Key_name;
            $seq = (int) $row->Seq_in_index;
            $column = (string) $row->Column_name;
            $indexes[$key][$seq] = $column;
        }

        foreach ($indexes as $key => $columns) {
            ksort($columns);
            $indexes[$key] = array_values($columns);
        }

        $cache[$table] = $indexes;

        return $cache[$table];
    }
};
