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
        Schema::table('customers', function (Blueprint $table): void {
            $table->boolean('is_verified')->default(false)->after('status');
            $table->timestamp('verified_at')->nullable()->after('is_verified');
            $table->unsignedBigInteger('verified_by_user_id')->nullable()->index('customers_verified_by_user_id_foreign')->after('verified_at');
            $table->timestamp('verification_requested_at')->nullable()->after('verified_by_user_id');
            $table->unsignedBigInteger('verification_requested_by_user_id')->nullable()->index('customers_verification_requested_by_user_id_foreign')->after('verification_requested_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex('customers_verified_by_user_id_foreign');
            $table->dropIndex('customers_verification_requested_by_user_id_foreign');
            $table->dropColumn([
                'is_verified',
                'verified_at',
                'verified_by_user_id',
                'verification_requested_at',
                'verification_requested_by_user_id',
            ]);
        });
    }
};
