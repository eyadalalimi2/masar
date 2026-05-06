<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consumer_vehicle_profiles', function (Blueprint $table) {
            $table->foreign('consumer_id', 'fk_consumer_vehicle_profiles_consumer_id')
                ->references('id')
                ->on('consumers')
                ->onDelete('cascade');
        });

        Schema::table('consumer_loyalty_points', function (Blueprint $table) {
            $table->foreign('consumer_id', 'fk_consumer_loyalty_points_consumer_id')
                ->references('id')
                ->on('consumers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('consumer_loyalty_points', function (Blueprint $table) {
            $table->dropForeign('fk_consumer_loyalty_points_consumer_id');
        });

        Schema::table('consumer_vehicle_profiles', function (Blueprint $table) {
            $table->dropForeign('fk_consumer_vehicle_profiles_consumer_id');
        });
    }
};
