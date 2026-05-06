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
        Schema::table('consumer_ratings', function (Blueprint $table) {
            $table->foreign(['consumer_id'])->references(['id'])->on('consumers')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['order_id'])->references(['id'])->on('orders')->onUpdate('restrict')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumer_ratings', function (Blueprint $table) {
            $table->dropForeign('consumer_ratings_consumer_id_foreign');
            $table->dropForeign('consumer_ratings_order_id_foreign');
        });
    }
};
