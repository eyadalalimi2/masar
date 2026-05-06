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
        Schema::create('workshop_service_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('workshop_id');
            $table->unsignedBigInteger('service_id')->nullable()->index('workshop_service_orders_service_id_foreign');
            $table->unsignedBigInteger('appointment_id')->nullable()->index('workshop_service_orders_appointment_id_foreign');
            $table->string('order_number')->unique();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->decimal('service_fee', 12)->default(0);
            $table->decimal('products_total', 12)->default(0);
            $table->decimal('total_amount', 12)->default(0);
            $table->string('status', 64)->default('requested');
            $table->text('notes')->nullable();
            $table->json('used_products')->nullable();
            $table->timestamps();

            $table->index(['workshop_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshop_service_orders');
    }
};
