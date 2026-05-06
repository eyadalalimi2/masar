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
        Schema::create('branch_stock_movements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('product_id')->index('branch_stock_movements_product_id_foreign');
            $table->unsignedBigInteger('product_unit_id')->index('branch_stock_movements_product_unit_id_foreign');
            $table->unsignedBigInteger('inventory_movement_id')->nullable()->index('branch_stock_movements_inventory_movement_id_foreign');
            $table->unsignedBigInteger('order_id')->nullable()->index('branch_stock_movements_order_id_foreign');
            $table->unsignedBigInteger('distributor_id')->nullable()->index('branch_stock_movements_distributor_id_foreign');
            $table->string('movement_type', 64);
            $table->decimal('quantity', 14, 3);
            $table->decimal('stock_before', 14, 3);
            $table->decimal('stock_after', 14, 3);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'created_at']);
            $table->unique(['branch_id', 'inventory_movement_id']);
            $table->index(['branch_id', 'movement_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_stock_movements');
    }
};
