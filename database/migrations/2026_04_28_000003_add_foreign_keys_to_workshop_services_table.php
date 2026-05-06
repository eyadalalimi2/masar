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
        Schema::table('workshop_services', function (Blueprint $table) {
            $table->foreign(['workshop_id'])->references(['id'])->on('workshop_accounts')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workshop_services', function (Blueprint $table) {
            $table->dropForeign('workshop_services_workshop_id_foreign');
        });
    }
};
