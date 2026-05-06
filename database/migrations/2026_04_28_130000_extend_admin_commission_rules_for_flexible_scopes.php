<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_commission_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_commission_rules', 'entity_type')) {
                $table->string('entity_type', 30)->default('global')->after('name')->index();
            }

            if (! Schema::hasColumn('admin_commission_rules', 'entity_id')) {
                $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type')->index();
            }

            if (! Schema::hasColumn('admin_commission_rules', 'region_key')) {
                $table->string('region_key', 120)->nullable()->after('entity_id')->index();
            }

            if (! Schema::hasColumn('admin_commission_rules', 'priority')) {
                $table->unsignedInteger('priority')->default(100)->after('fixed_fee')->index();
            }

            if (! Schema::hasColumn('admin_commission_rules', 'effective_from')) {
                $table->timestamp('effective_from')->nullable()->after('priority')->index();
            }

            if (! Schema::hasColumn('admin_commission_rules', 'effective_to')) {
                $table->timestamp('effective_to')->nullable()->after('effective_from')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('admin_commission_rules', function (Blueprint $table) {
            $table->dropColumn([
                'entity_type',
                'entity_id',
                'region_key',
                'priority',
                'effective_from',
                'effective_to',
            ]);
        });
    }
};
