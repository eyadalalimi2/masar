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
        Schema::create('workshop_services', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('workshop_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12)->default(0);
            $table->unsignedInteger('duration_minutes')->default(30);
            $table->boolean('requires_products')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['workshop_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshop_services');
    }
};
