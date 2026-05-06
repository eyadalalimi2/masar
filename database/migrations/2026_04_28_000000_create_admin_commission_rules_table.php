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
        Schema::create('admin_commission_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->decimal('commission_percent', 5)->default(0);
            $table->decimal('service_fee', 12)->default(0);
            $table->decimal('fixed_fee', 12)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_commission_rules');
    }
};
