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
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('supplier_id')->index('products_supplier_id_foreign');
            $table->unsignedBigInteger('category_id')->index('products_category_id_foreign');
            $table->string('name');
            $table->string('model')->default('');
            $table->unsignedSmallInteger('production_year_from')->nullable();
            $table->unsignedSmallInteger('production_year_to')->nullable();
            $table->json('car_models')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('status', 64)->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
