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
        Schema::table('branch_product_stocks', function (Blueprint $table) {
            $table->foreign(['branch_id'])->references(['id'])->on('branches')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['product_id'])->references(['id'])->on('products')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['product_unit_id'])->references(['id'])->on('product_units')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_product_stocks', function (Blueprint $table) {
            $table->dropForeign('branch_product_stocks_branch_id_foreign');
            $table->dropForeign('branch_product_stocks_product_id_foreign');
            $table->dropForeign('branch_product_stocks_product_unit_id_foreign');
        });
    }
};
