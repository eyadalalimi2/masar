<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_stock_movements', function (Blueprint $table) {
            $table->unique(
                ['branch_id', 'order_id', 'product_unit_id', 'movement_type'],
                'bsm_unique_order_item_movement'
            );
        });
    }

    public function down(): void
    {
        Schema::table('branch_stock_movements', function (Blueprint $table) {
            $table->dropUnique('bsm_unique_order_item_movement');
        });
    }
};
