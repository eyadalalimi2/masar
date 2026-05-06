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
        Schema::create('product_variant_units', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedBigInteger('unit_id')->index('product_variant_units_unit_id_foreign');
            $table->decimal('wholesale_price', 12);
            $table->decimal('retail_price', 12);
            $table->timestamps();

            $table->unique(['product_variant_id', 'unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_units');
    }
};
