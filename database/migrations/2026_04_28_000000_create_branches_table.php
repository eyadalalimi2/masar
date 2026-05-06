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
        Schema::create('branches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('supplier_id')->index('branches_supplier_id_foreign');
            $table->string('name');
            $table->string('phone');
            $table->string('branch_manager_name')->nullable();
            $table->string('branch_manager_image')->nullable();
            $table->string('branch_manager_password')->nullable();
            $table->text('address');
            $table->string('gps_location')->nullable();
            $table->text('working_hours')->nullable();
            $table->string('status', 64)->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
