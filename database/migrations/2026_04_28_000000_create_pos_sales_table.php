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
        Schema::create('pos_sales', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pos_account_id');
            $table->unsignedBigInteger('pos_local_product_id')->nullable()->index('pos_sales_pos_local_product_id_foreign');
            $table->unsignedBigInteger('order_id')->nullable()->index('pos_sales_order_id_foreign');
            $table->string('product_name');
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('sale_channel', 64)->default('offline');
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_price', 12);
            $table->decimal('total_amount', 12);
            $table->decimal('profit_amount', 12)->default(0);
            $table->text('note')->nullable();
            $table->timestamp('sold_at')->useCurrent();
            $table->timestamps();

            $table->index(['pos_account_id', 'sale_channel']);
            $table->index(['pos_account_id', 'sold_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_sales');
    }
};
