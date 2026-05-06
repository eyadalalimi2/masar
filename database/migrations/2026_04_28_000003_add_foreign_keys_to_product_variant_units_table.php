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
        Schema::table('product_variant_units', function (Blueprint $table) {
            $table->foreign(['product_variant_id'])->references(['id'])->on('product_variants')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['unit_id'])->references(['id'])->on('units')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variant_units', function (Blueprint $table) {
            $table->dropForeign('product_variant_units_product_variant_id_foreign');
            $table->dropForeign('product_variant_units_unit_id_foreign');
        });
    }
};
