<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumer_loyalty_points', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('consumer_id');
            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->integer('points');
            $table->string('direction', 64)->default('credit');
            $table->string('note', 255)->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->timestamps();

            $table->index(['consumer_id', 'direction']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumer_loyalty_points');
    }
};
