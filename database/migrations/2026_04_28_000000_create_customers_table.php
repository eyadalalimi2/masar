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
        Schema::create('customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type', 64);
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('password')->nullable();
            $table->string('whatsapp')->nullable();
            $table->text('address');
            $table->string('gps_location')->nullable();
            $table->text('working_hours')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('owner_image')->nullable();
            $table->string('logo')->nullable();
            $table->longText('store_images')->nullable();
            $table->string('national_id_number')->nullable();
            $table->string('national_id_image')->nullable();
            $table->string('commercial_reg_number')->nullable();
            $table->string('commercial_reg_image')->nullable();
            $table->string('license_number')->nullable();
            $table->string('license_image')->nullable();
            $table->string('type', 64)->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
