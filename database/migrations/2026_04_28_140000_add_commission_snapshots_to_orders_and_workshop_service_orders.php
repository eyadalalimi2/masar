<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'commission_rule_id')) {
                $table->unsignedBigInteger('commission_rule_id')->nullable()->after('total_price')->index();
            }

            if (! Schema::hasColumn('orders', 'commission_percent')) {
                $table->decimal('commission_percent', 7, 2)->default(0)->after('commission_rule_id');
            }

            if (! Schema::hasColumn('orders', 'commission_value')) {
                $table->decimal('commission_value', 12)->default(0)->after('commission_percent');
            }

            if (! Schema::hasColumn('orders', 'platform_service_fee')) {
                $table->decimal('platform_service_fee', 12)->default(0)->after('commission_value');
            }

            if (! Schema::hasColumn('orders', 'platform_fixed_fee')) {
                $table->decimal('platform_fixed_fee', 12)->default(0)->after('platform_service_fee');
            }

            if (! Schema::hasColumn('orders', 'payable_total')) {
                $table->decimal('payable_total', 12)->default(0)->after('platform_fixed_fee');
            }
        });

        Schema::table('workshop_service_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('workshop_service_orders', 'commission_rule_id')) {
                $table->unsignedBigInteger('commission_rule_id')->nullable()->after('total_amount')->index();
            }

            if (! Schema::hasColumn('workshop_service_orders', 'commission_percent')) {
                $table->decimal('commission_percent', 7, 2)->default(0)->after('commission_rule_id');
            }

            if (! Schema::hasColumn('workshop_service_orders', 'commission_value')) {
                $table->decimal('commission_value', 12)->default(0)->after('commission_percent');
            }

            if (! Schema::hasColumn('workshop_service_orders', 'platform_service_fee')) {
                $table->decimal('platform_service_fee', 12)->default(0)->after('commission_value');
            }

            if (! Schema::hasColumn('workshop_service_orders', 'platform_fixed_fee')) {
                $table->decimal('platform_fixed_fee', 12)->default(0)->after('platform_service_fee');
            }

            if (! Schema::hasColumn('workshop_service_orders', 'payable_total')) {
                $table->decimal('payable_total', 12)->default(0)->after('platform_fixed_fee');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'commission_rule_id',
                'commission_percent',
                'commission_value',
                'platform_service_fee',
                'platform_fixed_fee',
                'payable_total',
            ]);
        });

        Schema::table('workshop_service_orders', function (Blueprint $table) {
            $table->dropColumn([
                'commission_rule_id',
                'commission_percent',
                'commission_value',
                'platform_service_fee',
                'platform_fixed_fee',
                'payable_total',
            ]);
        });
    }
};
