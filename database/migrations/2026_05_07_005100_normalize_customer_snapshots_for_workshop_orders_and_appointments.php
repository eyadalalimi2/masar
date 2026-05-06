<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normalization strategy for workshop domain:
     * - Keep customer fields as immutable snapshots for historical integrity.
     * - Rename generic customer_* columns to snapshot_customer_* in workshop orders/appointments.
     * - Preserve all existing values in-place using column rename (no data rewrite).
     */
    public function up(): void
    {
        if (Schema::hasTable('workshop_service_orders')) {
            if (Schema::hasColumn('workshop_service_orders', 'customer_name') && ! Schema::hasColumn('workshop_service_orders', 'snapshot_customer_name')) {
                DB::statement('ALTER TABLE workshop_service_orders CHANGE customer_name snapshot_customer_name VARCHAR(255) NOT NULL');
            }

            if (Schema::hasColumn('workshop_service_orders', 'customer_phone') && ! Schema::hasColumn('workshop_service_orders', 'snapshot_customer_phone')) {
                DB::statement('ALTER TABLE workshop_service_orders CHANGE customer_phone snapshot_customer_phone VARCHAR(255) NOT NULL');
            }
        }

        if (Schema::hasTable('workshop_appointments')) {
            if (Schema::hasColumn('workshop_appointments', 'customer_name') && ! Schema::hasColumn('workshop_appointments', 'snapshot_customer_name')) {
                DB::statement('ALTER TABLE workshop_appointments CHANGE customer_name snapshot_customer_name VARCHAR(255) NOT NULL');
            }

            if (Schema::hasColumn('workshop_appointments', 'customer_phone') && ! Schema::hasColumn('workshop_appointments', 'snapshot_customer_phone')) {
                DB::statement('ALTER TABLE workshop_appointments CHANGE customer_phone snapshot_customer_phone VARCHAR(255) NOT NULL');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('workshop_service_orders')) {
            if (Schema::hasColumn('workshop_service_orders', 'snapshot_customer_name') && ! Schema::hasColumn('workshop_service_orders', 'customer_name')) {
                DB::statement('ALTER TABLE workshop_service_orders CHANGE snapshot_customer_name customer_name VARCHAR(255) NOT NULL');
            }

            if (Schema::hasColumn('workshop_service_orders', 'snapshot_customer_phone') && ! Schema::hasColumn('workshop_service_orders', 'customer_phone')) {
                DB::statement('ALTER TABLE workshop_service_orders CHANGE snapshot_customer_phone customer_phone VARCHAR(255) NOT NULL');
            }
        }

        if (Schema::hasTable('workshop_appointments')) {
            if (Schema::hasColumn('workshop_appointments', 'snapshot_customer_name') && ! Schema::hasColumn('workshop_appointments', 'customer_name')) {
                DB::statement('ALTER TABLE workshop_appointments CHANGE snapshot_customer_name customer_name VARCHAR(255) NOT NULL');
            }

            if (Schema::hasColumn('workshop_appointments', 'snapshot_customer_phone') && ! Schema::hasColumn('workshop_appointments', 'customer_phone')) {
                DB::statement('ALTER TABLE workshop_appointments CHANGE snapshot_customer_phone customer_phone VARCHAR(255) NOT NULL');
            }
        }
    }
};
