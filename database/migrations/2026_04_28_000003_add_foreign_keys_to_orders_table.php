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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign(['branch_id'])->references(['id'])->on('branches')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['consumer_id'])->references(['id'])->on('consumers')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['customer_id'])->references(['id'])->on('customers')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['distributor_id'])->references(['id'])->on('distributors')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['supplier_id'])->references(['id'])->on('suppliers')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('orders_branch_id_foreign');
            $table->dropForeign('orders_consumer_id_foreign');
            $table->dropForeign('orders_customer_id_foreign');
            $table->dropForeign('orders_distributor_id_foreign');
            $table->dropForeign('orders_supplier_id_foreign');
        });
    }
};
