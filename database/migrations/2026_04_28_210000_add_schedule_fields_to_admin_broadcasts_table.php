<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_broadcasts', function (Blueprint $table) {
            $table->timestamp('scheduled_for')->nullable()->after('is_active');
            $table->timestamp('dispatched_at')->nullable()->after('scheduled_for');
            $table->unsignedBigInteger('created_by_admin_id')->nullable()->after('dispatched_at');

            $table->index(['is_active', 'scheduled_for', 'dispatched_at'], 'admin_broadcasts_dispatch_idx');
        });
    }

    public function down(): void
    {
        Schema::table('admin_broadcasts', function (Blueprint $table) {
            $table->dropIndex('admin_broadcasts_dispatch_idx');
            $table->dropColumn(['scheduled_for', 'dispatched_at', 'created_by_admin_id']);
        });
    }
};
