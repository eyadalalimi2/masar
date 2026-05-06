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
        Schema::create('distributors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('supplier_id')->index('distributors_supplier_id_foreign');
            $table->unsignedBigInteger('branch_id')->nullable()->index('distributors_branch_id_foreign');
            $table->string('name');
            $table->string('phone');
            $table->string('password');
            $table->string('image')->nullable();
            $table->string('vehicle_type')->nullable();
            $table->text('distribution_points')->nullable();
            $table->string('status', 64)->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributors');
    }
};
