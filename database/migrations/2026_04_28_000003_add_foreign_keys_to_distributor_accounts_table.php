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
        Schema::table('distributor_accounts', function (Blueprint $table) {
            $table->foreign(['distributor_id'])->references(['id'])->on('distributors')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distributor_accounts', function (Blueprint $table) {
            $table->dropForeign('distributor_accounts_distributor_id_foreign');
        });
    }
};
