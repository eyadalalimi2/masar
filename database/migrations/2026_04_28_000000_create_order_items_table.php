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
        Schema::create('order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id')->index('order_items_order_id_foreign');
            $table->unsignedBigInteger('product_id')->index('order_items_product_id_foreign');
            $table->unsignedBigInteger('product_unit_id')->nullable()->index('order_items_product_unit_id_foreign');
            $table->unsignedBigInteger('product_variant_id')->nullable()->index('order_items_product_variant_id_foreign');
            $table->integer('quantity');
            $table->decimal('unit_price', 12);
            $table->decimal('total', 12);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
