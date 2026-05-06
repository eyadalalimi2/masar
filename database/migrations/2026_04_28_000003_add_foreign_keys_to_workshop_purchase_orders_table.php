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
        Schema::table('workshop_purchase_orders', function (Blueprint $table) {
            $table->foreign(['supplier_branch_id'])->references(['id'])->on('branches')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['workshop_id'])->references(['id'])->on('workshop_accounts')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workshop_purchase_orders', function (Blueprint $table) {
            $table->dropForeign('workshop_purchase_orders_supplier_branch_id_foreign');
            $table->dropForeign('workshop_purchase_orders_workshop_id_foreign');
        });
    }
};
