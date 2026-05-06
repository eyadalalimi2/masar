<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workshop_service_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('consumer_id')->nullable()->after('customer_phone')->index();
            $table->foreign('consumer_id')
                ->references('id')
                ->on('consumers')
                ->nullOnDelete();
        });

        DB::statement("\n            UPDATE workshop_service_orders wso\n            JOIN consumers c ON c.phone = wso.customer_phone\n            SET wso.consumer_id = c.id\n            WHERE wso.consumer_id IS NULL\n        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workshop_service_orders', function (Blueprint $table) {
            $table->dropForeign(['consumer_id']);
            $table->dropColumn('consumer_id');
        });
    }
};
