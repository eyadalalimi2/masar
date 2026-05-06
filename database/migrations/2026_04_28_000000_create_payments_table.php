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
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id')->index('payments_order_id_foreign');
            $table->unsignedBigInteger('supplier_id')->index('payments_supplier_id_foreign');
            $table->unsignedBigInteger('distributor_id')->nullable()->index('payments_distributor_id_foreign');
            $table->decimal('amount', 12);
            $table->string('payment_type', 64);
            $table->string('payment_type', 64);
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
