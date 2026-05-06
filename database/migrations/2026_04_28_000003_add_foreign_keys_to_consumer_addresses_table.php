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
        Schema::table('consumer_addresses', function (Blueprint $table) {
            $table->foreign(['consumer_id'])->references(['id'])->on('consumers')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumer_addresses', function (Blueprint $table) {
            $table->dropForeign('consumer_addresses_consumer_id_foreign');
        });
    }
};
