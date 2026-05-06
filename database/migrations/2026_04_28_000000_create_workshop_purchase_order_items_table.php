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
        Schema::create('workshop_purchase_order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('purchase_order_id')->index();
            $table->unsignedBigInteger('branch_product_stock_id')->nullable()->index('workshop_purchase_order_items_branch_product_stock_id_foreign');
            $table->unsignedBigInteger('product_id')->nullable()->index('workshop_purchase_order_items_product_id_foreign');
            $table->unsignedBigInteger('product_unit_id')->nullable()->index('workshop_purchase_order_items_product_unit_id_foreign');
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 12);
            $table->decimal('line_total', 12);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshop_purchase_order_items');
    }
};
