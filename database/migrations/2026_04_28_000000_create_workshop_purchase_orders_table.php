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
        Schema::create('workshop_purchase_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('workshop_id');
            $table->unsignedBigInteger('supplier_branch_id')->nullable()->index('workshop_purchase_orders_supplier_branch_id_foreign');
            $table->string('order_number')->unique();
            $table->string('supplier_branch_name');
            $table->decimal('total_amount', 12)->default(0);
            $table->string('status', 64)->default('pending');
            $table->timestamp('stock_deducted_at')->nullable();
            $table->timestamp('stock_restored_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['workshop_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshop_purchase_orders');
    }
};
