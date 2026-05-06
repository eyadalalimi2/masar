<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workshop_purchase_order_items', function (Blueprint $table) {
            $table->foreign(['branch_product_stock_id'])->references(['id'])->on('branch_product_stocks')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['product_id'])->references(['id'])->on('products')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['product_unit_id'])->references(['id'])->on('product_units')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['purchase_order_id'])->references(['id'])->on('workshop_purchase_orders')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workshop_purchase_order_items', function (Blueprint $table) {
            $table->dropForeign('workshop_purchase_order_items_branch_product_stock_id_foreign');
            $table->dropForeign('workshop_purchase_order_items_product_id_foreign');
            $table->dropForeign('workshop_purchase_order_items_product_unit_id_foreign');
            $table->dropForeign('workshop_purchase_order_items_purchase_order_id_foreign');
        });
    }
};
