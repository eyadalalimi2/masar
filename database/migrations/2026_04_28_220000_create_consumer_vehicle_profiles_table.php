<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumer_vehicle_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('consumer_id');
            $table->string('nickname', 80)->nullable();
            $table->string('plate_number', 80)->nullable();
            $table->string('brand', 80)->nullable();
            $table->string('model', 80)->nullable();
            $table->unsignedSmallInteger('production_year')->nullable();
            $table->unsignedInteger('last_odometer_km')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['consumer_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumer_vehicle_profiles');
    }
};
