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
        Schema::create('admin_locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('city');
            $table->string('zone');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['city', 'zone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_locations');
    }
};
