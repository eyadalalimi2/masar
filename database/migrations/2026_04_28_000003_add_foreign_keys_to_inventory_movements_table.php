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
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreign(['agent_id'])->references(['id'])->on('agents')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['branch_id'])->references(['id'])->on('branches')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['product_id'])->references(['id'])->on('products')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['product_unit_id'])->references(['id'])->on('product_units')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['supplier_id'])->references(['id'])->on('suppliers')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign('inventory_movements_agent_id_foreign');
            $table->dropForeign('inventory_movements_branch_id_foreign');
            $table->dropForeign('inventory_movements_product_id_foreign');
            $table->dropForeign('inventory_movements_product_unit_id_foreign');
            $table->dropForeign('inventory_movements_supplier_id_foreign');
        });
    }
};
