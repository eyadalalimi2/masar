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
        Schema::create('branch_replenishment_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('product_id')->index('branch_replenishment_requests_product_id_foreign');
            $table->unsignedBigInteger('product_unit_id')->index('branch_replenishment_requests_product_unit_id_foreign');
            $table->decimal('requested_quantity', 14, 3);
            $table->string('status', 64)->default('pending');
            $table->text('note')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['supplier_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_replenishment_requests');
    }
};
