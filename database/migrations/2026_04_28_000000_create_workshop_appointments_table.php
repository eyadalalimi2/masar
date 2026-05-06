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
        Schema::create('workshop_appointments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('workshop_id');
            $table->unsignedBigInteger('service_id')->nullable()->index('workshop_appointments_service_id_foreign');
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('vehicle_details')->nullable();
            $table->dateTime('appointment_at');
            $table->unsignedInteger('estimated_minutes')->default(30);
            $table->string('status', 64)->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['workshop_id', 'appointment_at']);
            $table->index(['workshop_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshop_appointments');
    }
};
