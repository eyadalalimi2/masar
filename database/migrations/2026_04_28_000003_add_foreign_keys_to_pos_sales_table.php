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
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->foreign(['order_id'])->references(['id'])->on('orders')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['pos_account_id'])->references(['id'])->on('pos_accounts')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['pos_local_product_id'])->references(['id'])->on('pos_local_products')->onUpdate('restrict')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropForeign('pos_sales_order_id_foreign');
            $table->dropForeign('pos_sales_pos_account_id_foreign');
            $table->dropForeign('pos_sales_pos_local_product_id_foreign');
        });
    }
};
