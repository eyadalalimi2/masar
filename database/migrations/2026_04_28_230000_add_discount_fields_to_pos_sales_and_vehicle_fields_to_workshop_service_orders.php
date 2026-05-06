<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            if (! Schema::hasColumn('pos_sales', 'gross_amount')) {
                $table->decimal('gross_amount', 12)->default(0)->after('unit_price');
            }

            if (! Schema::hasColumn('pos_sales', 'discount_type')) {
                $table->string('discount_type', 20)->nullable()->after('gross_amount');
            }

            if (! Schema::hasColumn('pos_sales', 'discount_value')) {
                $table->decimal('discount_value', 12, 2)->default(0)->after('discount_type');
            }

            if (! Schema::hasColumn('pos_sales', 'discount_amount')) {
                $table->decimal('discount_amount', 12)->default(0)->after('discount_value');
            }

            if (! Schema::hasColumn('pos_sales', 'campaign_code')) {
                $table->string('campaign_code', 80)->nullable()->after('discount_amount');
            }
        });

        Schema::table('workshop_service_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('workshop_service_orders', 'vehicle_plate_number')) {
                $table->string('vehicle_plate_number', 80)->nullable()->after('customer_phone');
            }

            if (! Schema::hasColumn('workshop_service_orders', 'vehicle_brand')) {
                $table->string('vehicle_brand', 80)->nullable()->after('vehicle_plate_number');
            }

            if (! Schema::hasColumn('workshop_service_orders', 'vehicle_model')) {
                $table->string('vehicle_model', 80)->nullable()->after('vehicle_brand');
            }

            if (! Schema::hasColumn('workshop_service_orders', 'vehicle_production_year')) {
                $table->unsignedSmallInteger('vehicle_production_year')->nullable()->after('vehicle_model');
            }

            if (! Schema::hasColumn('workshop_service_orders', 'odometer_km')) {
                $table->unsignedInteger('odometer_km')->nullable()->after('vehicle_production_year');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            $columns = [
                'gross_amount',
                'discount_type',
                'discount_value',
                'discount_amount',
                'campaign_code',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('pos_sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('workshop_service_orders', function (Blueprint $table) {
            $columns = [
                'vehicle_plate_number',
                'vehicle_brand',
                'vehicle_model',
                'vehicle_production_year',
                'odometer_km',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('workshop_service_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
