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
        Schema::create('branch_product_stocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('product_id')->index('branch_product_stocks_product_id_foreign');
            $table->unsignedBigInteger('product_unit_id')->index('branch_product_stocks_product_unit_id_foreign');
            $table->decimal('quantity', 14, 3)->default(0);
            $table->decimal('selling_price', 12)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['branch_id', 'product_id']);
            $table->unique(['branch_id', 'product_unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_product_stocks');
    }
};
