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
        Schema::table('branch_stock_movements', function (Blueprint $table) {
            $table->foreign(['branch_id'])->references(['id'])->on('branches')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['distributor_id'])->references(['id'])->on('distributors')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['inventory_movement_id'])->references(['id'])->on('inventory_movements')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['order_id'])->references(['id'])->on('orders')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['product_id'])->references(['id'])->on('products')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['product_unit_id'])->references(['id'])->on('product_units')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_stock_movements', function (Blueprint $table) {
            $table->dropForeign('branch_stock_movements_branch_id_foreign');
            $table->dropForeign('branch_stock_movements_distributor_id_foreign');
            $table->dropForeign('branch_stock_movements_inventory_movement_id_foreign');
            $table->dropForeign('branch_stock_movements_order_id_foreign');
            $table->dropForeign('branch_stock_movements_product_id_foreign');
            $table->dropForeign('branch_stock_movements_product_unit_id_foreign');
        });
    }
};
