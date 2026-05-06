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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('product_id')->index('inventory_movements_product_id_foreign');
            $table->unsignedBigInteger('product_unit_id');
            $table->unsignedBigInteger('branch_id')->nullable()->index('inventory_movements_branch_id_foreign');
            $table->unsignedBigInteger('agent_id')->nullable()->index('inventory_movements_agent_id_foreign');
            $table->string('movement_type', 64);
            $table->decimal('quantity', 14, 3);
            $table->decimal('stock_before', 14, 3);
            $table->decimal('stock_after', 14, 3);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['product_unit_id', 'created_at']);
            $table->index(['supplier_id', 'movement_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
