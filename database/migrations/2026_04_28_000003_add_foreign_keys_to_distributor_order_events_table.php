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
        Schema::table('distributor_order_events', function (Blueprint $table) {
            $table->foreign(['distributor_id'])->references(['id'])->on('distributors')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['order_id'])->references(['id'])->on('orders')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distributor_order_events', function (Blueprint $table) {
            $table->dropForeign('distributor_order_events_distributor_id_foreign');
            $table->dropForeign('distributor_order_events_order_id_foreign');
        });
    }
};
