<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('event_type', 80)->index();
            $table->string('table_name', 120)->index();
            $table->unsignedBigInteger('record_id')->nullable()->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device', 120)->nullable()->index();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['table_name', 'record_id', 'created_at'], 'audit_logs_table_record_created_idx');
            $table->index(['event_type', 'created_at'], 'audit_logs_event_created_idx');
            $table->index(['user_id', 'event_type', 'created_at'], 'audit_logs_user_event_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
