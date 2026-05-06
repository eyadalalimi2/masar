<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, array{table:string,name:string,expr:string}>
     */
    private array $checks = [
        ['table' => 'accounts', 'name' => 'chk_accounts_balance_nonneg', 'expr' => 'balance >= 0'],

        ['table' => 'transactions', 'name' => 'chk_transactions_amount_pos', 'expr' => 'amount > 0'],
        ['table' => 'payments', 'name' => 'chk_payments_amount_nonneg', 'expr' => 'amount >= 0'],
        ['table' => 'order_payments', 'name' => 'chk_order_payments_amount_nonneg', 'expr' => 'amount >= 0'],
        ['table' => 'workshop_order_payments', 'name' => 'chk_workshop_order_pay_amount_nonneg', 'expr' => 'amount >= 0'],

        ['table' => 'orders', 'name' => 'chk_orders_total_price_nonneg', 'expr' => 'total_price >= 0'],
        ['table' => 'orders', 'name' => 'chk_orders_comm_percent_nonneg', 'expr' => 'commission_percent >= 0'],
        ['table' => 'orders', 'name' => 'chk_orders_comm_value_nonneg', 'expr' => 'commission_value >= 0'],
        ['table' => 'orders', 'name' => 'chk_orders_service_fee_nonneg', 'expr' => 'platform_service_fee >= 0'],
        ['table' => 'orders', 'name' => 'chk_orders_fixed_fee_nonneg', 'expr' => 'platform_fixed_fee >= 0'],
        ['table' => 'orders', 'name' => 'chk_orders_payable_total_nonneg', 'expr' => 'payable_total >= 0'],

        ['table' => 'order_items', 'name' => 'chk_order_items_qty_pos', 'expr' => 'quantity > 0'],
        ['table' => 'order_items', 'name' => 'chk_order_items_unit_price_nonneg', 'expr' => 'unit_price >= 0'],
        ['table' => 'order_items', 'name' => 'chk_order_items_total_nonneg', 'expr' => 'total >= 0'],

        ['table' => 'product_units', 'name' => 'chk_product_units_wholesale_nonneg', 'expr' => 'wholesale_price >= 0'],
        ['table' => 'product_units', 'name' => 'chk_product_units_retail_nonneg', 'expr' => 'retail_price >= 0'],
        ['table' => 'product_units', 'name' => 'chk_product_units_conv_factor_pos', 'expr' => 'conversion_factor > 0'],
        ['table' => 'product_units', 'name' => 'chk_product_units_stock_nonneg', 'expr' => 'stock_quantity >= 0'],
        ['table' => 'product_units', 'name' => 'chk_product_units_low_stock_nonneg', 'expr' => 'low_stock_threshold >= 0'],

        ['table' => 'inventory_movements', 'name' => 'chk_inventory_movements_qty_pos', 'expr' => 'quantity > 0'],
        ['table' => 'inventory_movements', 'name' => 'chk_inventory_movements_before_nonneg', 'expr' => 'stock_before >= 0'],
        ['table' => 'inventory_movements', 'name' => 'chk_inventory_movements_after_nonneg', 'expr' => 'stock_after >= 0'],

        ['table' => 'branch_product_stocks', 'name' => 'chk_branch_product_stock_qty_nonneg', 'expr' => 'quantity >= 0'],
        ['table' => 'branch_product_stocks', 'name' => 'chk_branch_product_stock_price_nonneg', 'expr' => 'selling_price IS NULL OR selling_price >= 0'],

        ['table' => 'branch_stock_movements', 'name' => 'chk_branch_stock_moves_qty_pos', 'expr' => 'quantity > 0'],
        ['table' => 'branch_stock_movements', 'name' => 'chk_branch_stock_moves_before_nonneg', 'expr' => 'stock_before >= 0'],
        ['table' => 'branch_stock_movements', 'name' => 'chk_branch_stock_moves_after_nonneg', 'expr' => 'stock_after >= 0'],

        ['table' => 'branch_replenishment_requests', 'name' => 'chk_branch_replen_req_qty_pos', 'expr' => 'requested_quantity > 0'],

        ['table' => 'pos_local_products', 'name' => 'chk_pos_local_products_purchase_nonneg', 'expr' => 'purchase_price >= 0'],
        ['table' => 'pos_local_products', 'name' => 'chk_pos_local_products_selling_nonneg', 'expr' => 'selling_price >= 0'],
        ['table' => 'pos_local_products', 'name' => 'chk_pos_local_products_qty_nonneg', 'expr' => 'local_quantity >= 0'],

        ['table' => 'pos_sales', 'name' => 'chk_pos_sales_qty_pos', 'expr' => 'quantity > 0'],
        ['table' => 'pos_sales', 'name' => 'chk_pos_sales_unit_price_nonneg', 'expr' => 'unit_price >= 0'],
        ['table' => 'pos_sales', 'name' => 'chk_pos_sales_gross_nonneg', 'expr' => 'gross_amount >= 0'],
        ['table' => 'pos_sales', 'name' => 'chk_pos_sales_disc_val_nonneg', 'expr' => 'discount_value >= 0'],
        ['table' => 'pos_sales', 'name' => 'chk_pos_sales_disc_amt_nonneg', 'expr' => 'discount_amount >= 0'],
        ['table' => 'pos_sales', 'name' => 'chk_pos_sales_total_nonneg', 'expr' => 'total_amount >= 0'],

        ['table' => 'workshop_purchase_orders', 'name' => 'chk_workshop_po_total_nonneg', 'expr' => 'total_amount >= 0'],
        ['table' => 'workshop_purchase_order_items', 'name' => 'chk_workshop_poi_qty_pos', 'expr' => 'quantity > 0'],
        ['table' => 'workshop_purchase_order_items', 'name' => 'chk_workshop_poi_unit_nonneg', 'expr' => 'unit_price >= 0'],
        ['table' => 'workshop_purchase_order_items', 'name' => 'chk_workshop_poi_line_nonneg', 'expr' => 'line_total >= 0'],

        ['table' => 'workshop_service_orders', 'name' => 'chk_workshop_so_service_fee_nonneg', 'expr' => 'service_fee >= 0'],
        ['table' => 'workshop_service_orders', 'name' => 'chk_workshop_so_products_total_nonneg', 'expr' => 'products_total >= 0'],
        ['table' => 'workshop_service_orders', 'name' => 'chk_workshop_so_total_nonneg', 'expr' => 'total_amount >= 0'],
        ['table' => 'workshop_service_orders', 'name' => 'chk_workshop_so_comm_percent_nonneg', 'expr' => 'commission_percent >= 0'],
        ['table' => 'workshop_service_orders', 'name' => 'chk_workshop_so_comm_value_nonneg', 'expr' => 'commission_value >= 0'],
        ['table' => 'workshop_service_orders', 'name' => 'chk_workshop_so_service_fee2_nonneg', 'expr' => 'platform_service_fee >= 0'],
        ['table' => 'workshop_service_orders', 'name' => 'chk_workshop_so_fixed_fee_nonneg', 'expr' => 'platform_fixed_fee >= 0'],
        ['table' => 'workshop_service_orders', 'name' => 'chk_workshop_so_payable_nonneg', 'expr' => 'payable_total >= 0'],
    ];

    public function up(): void
    {
        $this->normalizeInvalidValues();

        foreach ($this->checks as $check) {
            $this->addCheckIfPossible($check['table'], $check['name'], $check['expr']);
        }
    }

    public function down(): void
    {
        foreach ($this->checks as $check) {
            $this->dropCheckIfPossible($check['table'], $check['name']);
        }
    }

    private function normalizeInvalidValues(): void
    {
        $this->safeUpdate('accounts', 'balance < 0', 'balance = 0');

        $this->safeUpdate('transactions', 'amount <= 0', 'amount = 0.01');
        $this->safeUpdate('payments', 'amount < 0', 'amount = 0');
        $this->safeUpdate('order_payments', 'amount < 0', 'amount = 0');
        $this->safeUpdate('workshop_order_payments', 'amount < 0', 'amount = 0');

        $this->safeUpdate('orders', 'total_price < 0', 'total_price = 0');
        $this->safeUpdate('orders', 'commission_percent < 0', 'commission_percent = 0');
        $this->safeUpdate('orders', 'commission_value < 0', 'commission_value = 0');
        $this->safeUpdate('orders', 'platform_service_fee < 0', 'platform_service_fee = 0');
        $this->safeUpdate('orders', 'platform_fixed_fee < 0', 'platform_fixed_fee = 0');
        $this->safeUpdate('orders', 'payable_total < 0', 'payable_total = 0');

        $this->safeUpdate('order_items', 'quantity <= 0', 'quantity = 1');
        $this->safeUpdate('order_items', 'unit_price < 0', 'unit_price = 0');
        $this->safeUpdate('order_items', 'total < 0', 'total = 0');

        $this->safeUpdate('product_units', 'wholesale_price < 0', 'wholesale_price = 0');
        $this->safeUpdate('product_units', 'retail_price < 0', 'retail_price = 0');
        $this->safeUpdate('product_units', 'conversion_factor <= 0', 'conversion_factor = 1');
        $this->safeUpdate('product_units', 'stock_quantity < 0', 'stock_quantity = 0');
        $this->safeUpdate('product_units', 'low_stock_threshold < 0', 'low_stock_threshold = 0');

        $this->safeUpdate('inventory_movements', 'quantity <= 0', 'quantity = 0.001');
        $this->safeUpdate('inventory_movements', 'stock_before < 0', 'stock_before = 0');
        $this->safeUpdate('inventory_movements', 'stock_after < 0', 'stock_after = 0');

        $this->safeUpdate('branch_product_stocks', 'quantity < 0', 'quantity = 0');
        $this->safeUpdate('branch_product_stocks', 'selling_price < 0', 'selling_price = 0');

        $this->safeUpdate('branch_stock_movements', 'quantity <= 0', 'quantity = 0.001');
        $this->safeUpdate('branch_stock_movements', 'stock_before < 0', 'stock_before = 0');
        $this->safeUpdate('branch_stock_movements', 'stock_after < 0', 'stock_after = 0');

        $this->safeUpdate('branch_replenishment_requests', 'requested_quantity <= 0', 'requested_quantity = 0.001');

        $this->safeUpdate('pos_local_products', 'purchase_price < 0', 'purchase_price = 0');
        $this->safeUpdate('pos_local_products', 'selling_price < 0', 'selling_price = 0');
        $this->safeUpdate('pos_local_products', 'local_quantity < 0', 'local_quantity = 0');

        $this->safeUpdate('pos_sales', 'quantity <= 0', 'quantity = 0.001');
        $this->safeUpdate('pos_sales', 'unit_price < 0', 'unit_price = 0');
        $this->safeUpdate('pos_sales', 'gross_amount < 0', 'gross_amount = 0');
        $this->safeUpdate('pos_sales', 'discount_value < 0', 'discount_value = 0');
        $this->safeUpdate('pos_sales', 'discount_amount < 0', 'discount_amount = 0');
        $this->safeUpdate('pos_sales', 'total_amount < 0', 'total_amount = 0');

        $this->safeUpdate('workshop_purchase_orders', 'total_amount < 0', 'total_amount = 0');
        $this->safeUpdate('workshop_purchase_order_items', 'quantity <= 0', 'quantity = 0.001');
        $this->safeUpdate('workshop_purchase_order_items', 'unit_price < 0', 'unit_price = 0');
        $this->safeUpdate('workshop_purchase_order_items', 'line_total < 0', 'line_total = 0');

        $this->safeUpdate('workshop_service_orders', 'service_fee < 0', 'service_fee = 0');
        $this->safeUpdate('workshop_service_orders', 'products_total < 0', 'products_total = 0');
        $this->safeUpdate('workshop_service_orders', 'total_amount < 0', 'total_amount = 0');
        $this->safeUpdate('workshop_service_orders', 'commission_percent < 0', 'commission_percent = 0');
        $this->safeUpdate('workshop_service_orders', 'commission_value < 0', 'commission_value = 0');
        $this->safeUpdate('workshop_service_orders', 'platform_service_fee < 0', 'platform_service_fee = 0');
        $this->safeUpdate('workshop_service_orders', 'platform_fixed_fee < 0', 'platform_fixed_fee = 0');
        $this->safeUpdate('workshop_service_orders', 'payable_total < 0', 'payable_total = 0');
    }

    private function safeUpdate(string $table, string $whereClause, string $setClause): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        try {
            DB::statement('UPDATE `' . str_replace('`', '``', $table) . '` SET ' . $setClause . ' WHERE ' . $whereClause);
        } catch (\Throwable) {
            // Skip only failing table/column combinations for cross-environment resilience.
        }
    }

    private function addCheckIfPossible(string $table, string $constraintName, string $expression): void
    {
        if (! Schema::hasTable($table) || $this->constraintExists($table, $constraintName)) {
            return;
        }

        try {
            DB::statement(sprintf(
                'ALTER TABLE `%s` ADD CONSTRAINT `%s` CHECK (%s)',
                str_replace('`', '``', $table),
                str_replace('`', '``', $constraintName),
                $expression
            ));
        } catch (\Throwable) {
            // Some engines/versions ignore or reject CHECK constraints; fail-safe behavior by design.
        }
    }

    private function dropCheckIfPossible(string $table, string $constraintName): void
    {
        if (! Schema::hasTable($table) || ! $this->constraintExists($table, $constraintName)) {
            return;
        }

        try {
            DB::statement(sprintf(
                'ALTER TABLE `%s` DROP CHECK `%s`',
                str_replace('`', '``', $table),
                str_replace('`', '``', $constraintName)
            ));
        } catch (\Throwable) {
            try {
                DB::statement(sprintf(
                    'ALTER TABLE `%s` DROP CONSTRAINT `%s`',
                    str_replace('`', '``', $table),
                    str_replace('`', '``', $constraintName)
                ));
            } catch (\Throwable) {
                // Ignore on engines that do not support dropping this way.
            }
        }
    }

    private function constraintExists(string $table, string $constraintName): bool
    {
        try {
            $dbName = DB::getDatabaseName();

            if (! is_string($dbName) || trim($dbName) === '') {
                return false;
            }

            $value = DB::table('information_schema.table_constraints')
                ->where('constraint_schema', $dbName)
                ->where('table_name', $table)
                ->where('constraint_name', $constraintName)
                ->value('constraint_name');

            return is_string($value) && $value !== '';
        } catch (\Throwable) {
            return false;
        }
    }
};
