<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('actor_type', 50)->index();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('guard', 50)->nullable()->index();
            $table->string('action')->index();
            $table->string('route_name')->nullable()->index();
            $table->string('method', 10)->nullable()->index();
            $table->string('path');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'actor_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_trails');
    }
};
