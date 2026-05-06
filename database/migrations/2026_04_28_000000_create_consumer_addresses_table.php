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
        Schema::create('consumer_addresses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('consumer_id');
            $table->string('label')->default('المنزل');
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->text('address_line');
            $table->string('gps_location')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['consumer_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumer_addresses');
    }
};
