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
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('supplier_id')->index('orders_supplier_id_foreign');
            $table->unsignedBigInteger('branch_id')->nullable()->index('orders_branch_id_foreign');
            $table->unsignedBigInteger('distributor_id')->nullable()->index('orders_distributor_id_foreign');
            $table->string('customer_type', 64);
            $table->unsignedBigInteger('customer_id')->nullable()->index('orders_customer_id_foreign');
            $table->unsignedBigInteger('consumer_id')->nullable()->index('orders_consumer_id_foreign');
            $table->string('customer_type', 64)->nullable();
            $table->unsignedBigInteger('seller_id')->nullable();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->text('customer_address');
            $table->decimal('total_price', 12)->default(0);
            $table->string('customer_type', 64)->default('pending');
            $table->string('distributor_stage', 30)->nullable()->index();
            $table->unsignedBigInteger('created_by')->index('orders_created_by_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
