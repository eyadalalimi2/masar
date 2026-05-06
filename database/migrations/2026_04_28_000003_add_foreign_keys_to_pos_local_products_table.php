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
        Schema::table('pos_local_products', function (Blueprint $table) {
            $table->foreign(['branch_id'])->references(['id'])->on('branches')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['pos_account_id'])->references(['id'])->on('pos_accounts')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['product_id'])->references(['id'])->on('products')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['product_unit_id'])->references(['id'])->on('product_units')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_local_products', function (Blueprint $table) {
            $table->dropForeign('pos_local_products_branch_id_foreign');
            $table->dropForeign('pos_local_products_pos_account_id_foreign');
            $table->dropForeign('pos_local_products_product_id_foreign');
            $table->dropForeign('pos_local_products_product_unit_id_foreign');
        });
    }
};
