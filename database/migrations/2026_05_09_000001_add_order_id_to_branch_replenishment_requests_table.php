<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_replenishment_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->after('id');
            $table->index('order_id');
            $table->foreign('order_id')->references('id')->on('branch_replenishment_orders')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('branch_replenishment_requests', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropIndex(['order_id']);
            $table->dropColumn('order_id');
        });
    }
};
