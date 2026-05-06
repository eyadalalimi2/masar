<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshop_services', function (Blueprint $table) {
            if (! Schema::hasColumn('workshop_services', 'is_package')) {
                $table->boolean('is_package')->default(false)->after('requires_products');
            }

            if (! Schema::hasColumn('workshop_services', 'package_items')) {
                $table->text('package_items')->nullable()->after('is_package');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workshop_services', function (Blueprint $table) {
            $table->dropColumn(['is_package', 'package_items']);
        });
    }
};
