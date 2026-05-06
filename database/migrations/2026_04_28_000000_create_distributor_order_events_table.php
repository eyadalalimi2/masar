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
        Schema::create('distributor_order_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('distributor_id');
            $table->unsignedBigInteger('order_id');
            $table->string('stage', 30);
            $table->text('note')->nullable();
            $table->string('delivery_proof_image')->nullable();
            $table->string('delivery_signature', 255)->nullable();
            $table->timestamp('proof_captured_at')->nullable();
            $table->unsignedInteger('route_sequence')->nullable();
            $table->string('event_source', 20)->default('live');
            $table->timestamps();

            $table->index(['distributor_id', 'stage']);
            $table->index(['order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributor_order_events');
    }
};
