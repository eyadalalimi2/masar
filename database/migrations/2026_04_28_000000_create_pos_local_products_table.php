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
        Schema::create('pos_local_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pos_account_id');
            $table->unsignedBigInteger('branch_id')->index('pos_local_products_branch_id_foreign');
            $table->unsignedBigInteger('product_id')->index('pos_local_products_product_id_foreign');
            $table->unsignedBigInteger('product_unit_id')->index('pos_local_products_product_unit_id_foreign');
            $table->decimal('purchase_price', 12);
            $table->decimal('selling_price', 12);
            $table->decimal('local_quantity', 14, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['pos_account_id', 'is_active']);
            $table->unique(['pos_account_id', 'branch_id', 'product_unit_id'], 'pos_local_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_local_products');
    }
};
