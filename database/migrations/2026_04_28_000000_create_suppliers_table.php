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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('logo')->nullable();
            $table->string('agent_image')->nullable();
            $table->string('branch_manager_image')->nullable();
            $table->string('branch_manager_password')->nullable();
            $table->string('owner_name');
            $table->string('branch_manager_name')->nullable();
            $table->string('business_name');
            $table->string('commercial_reg_number')->default('');
            $table->string('commercial_reg_image')->nullable();
            $table->string('license_number')->default('');
            $table->string('license_image')->nullable();
            $table->string('national_id_number')->default('');
            $table->string('national_id_image')->nullable();
            $table->string('phone');
            $table->string('whatsapp');
            $table->text('address')->default('');
            $table->string('gps_location');
            $table->string('email')->nullable();
            $table->text('working_hours');
            $table->string('status', 64)->default('active');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by_user_id')->nullable()->index('suppliers_verified_by_user_id_foreign');
            $table->timestamp('verification_requested_at')->nullable();
            $table->unsignedBigInteger('verification_requested_by_user_id')->nullable()->index('suppliers_verification_requested_by_user_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
