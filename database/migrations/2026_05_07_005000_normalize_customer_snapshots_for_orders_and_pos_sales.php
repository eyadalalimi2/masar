<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normalization strategy:
     * - Keep customer denormalized values only as immutable historical snapshots.
     * - Rename duplicated order/invoice customer columns to snapshot_* names.
     * - Preserve data exactly during rename to protect historical integrity.
     */
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            if (Schema::hasColumn('orders', 'customer_name') && ! Schema::hasColumn('orders', 'snapshot_customer_name')) {
                DB::statement('ALTER TABLE orders CHANGE customer_name snapshot_customer_name VARCHAR(255) NOT NULL');
            }

            if (Schema::hasColumn('orders', 'customer_phone') && ! Schema::hasColumn('orders', 'snapshot_customer_phone')) {
                DB::statement('ALTER TABLE orders CHANGE customer_phone snapshot_customer_phone VARCHAR(255) NOT NULL');
            }

            if (Schema::hasColumn('orders', 'customer_address') && ! Schema::hasColumn('orders', 'snapshot_customer_address')) {
                DB::statement('ALTER TABLE orders CHANGE customer_address snapshot_customer_address TEXT NOT NULL');
            }
        }

        if (Schema::hasTable('pos_sales')) {
            if (Schema::hasColumn('pos_sales', 'customer_name') && ! Schema::hasColumn('pos_sales', 'snapshot_customer_name')) {
                DB::statement('ALTER TABLE pos_sales CHANGE customer_name snapshot_customer_name VARCHAR(255) NULL');
            }

            if (Schema::hasColumn('pos_sales', 'customer_phone') && ! Schema::hasColumn('pos_sales', 'snapshot_customer_phone')) {
                DB::statement('ALTER TABLE pos_sales CHANGE customer_phone snapshot_customer_phone VARCHAR(255) NULL');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            if (Schema::hasColumn('orders', 'snapshot_customer_name') && ! Schema::hasColumn('orders', 'customer_name')) {
                DB::statement('ALTER TABLE orders CHANGE snapshot_customer_name customer_name VARCHAR(255) NOT NULL');
            }

            if (Schema::hasColumn('orders', 'snapshot_customer_phone') && ! Schema::hasColumn('orders', 'customer_phone')) {
                DB::statement('ALTER TABLE orders CHANGE snapshot_customer_phone customer_phone VARCHAR(255) NOT NULL');
            }

            if (Schema::hasColumn('orders', 'snapshot_customer_address') && ! Schema::hasColumn('orders', 'customer_address')) {
                DB::statement('ALTER TABLE orders CHANGE snapshot_customer_address customer_address TEXT NOT NULL');
            }
        }

        if (Schema::hasTable('pos_sales')) {
            if (Schema::hasColumn('pos_sales', 'snapshot_customer_name') && ! Schema::hasColumn('pos_sales', 'customer_name')) {
                DB::statement('ALTER TABLE pos_sales CHANGE snapshot_customer_name customer_name VARCHAR(255) NULL');
            }

            if (Schema::hasColumn('pos_sales', 'snapshot_customer_phone') && ! Schema::hasColumn('pos_sales', 'customer_phone')) {
                DB::statement('ALTER TABLE pos_sales CHANGE snapshot_customer_phone customer_phone VARCHAR(255) NULL');
            }
        }
    }
};
