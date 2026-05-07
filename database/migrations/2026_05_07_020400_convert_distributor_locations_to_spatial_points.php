<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->convertLiveTable();
        $this->convertArchiveTable();
    }

    public function down(): void
    {
        $this->restoreArchiveTableColumns();
        $this->restoreLiveTableColumns();
    }

    private function convertLiveTable(): void
    {
        if (! Schema::hasTable('distributor_location_logs')) {
            return;
        }

        if (! Schema::hasColumn('distributor_location_logs', 'location')) {
            DB::statement('ALTER TABLE distributor_location_logs ADD COLUMN location POINT NULL AFTER order_id');
        }

        if (Schema::hasColumn('distributor_location_logs', 'latitude') && Schema::hasColumn('distributor_location_logs', 'longitude')) {
            DB::statement("\n                UPDATE distributor_location_logs\n                SET location = ST_GeomFromText(CONCAT('POINT(', longitude, ' ', latitude, ')'))\n                WHERE location IS NULL\n            ");
        }

        DB::statement('ALTER TABLE distributor_location_logs MODIFY location POINT NOT NULL');

        if (! $this->indexExists('distributor_location_logs', 'dll_location_spatial_idx')) {
            DB::statement('ALTER TABLE distributor_location_logs ADD SPATIAL INDEX dll_location_spatial_idx (location)');
        }

        Schema::table('distributor_location_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('distributor_location_logs', 'latitude')) {
                $table->dropColumn('latitude');
            }

            if (Schema::hasColumn('distributor_location_logs', 'longitude')) {
                $table->dropColumn('longitude');
            }
        });
    }

    private function convertArchiveTable(): void
    {
        if (! Schema::hasTable('distributor_location_logs_archive')) {
            return;
        }

        if (! Schema::hasColumn('distributor_location_logs_archive', 'location')) {
            DB::statement('ALTER TABLE distributor_location_logs_archive ADD COLUMN location POINT NULL AFTER order_id');
        }

        if (Schema::hasColumn('distributor_location_logs_archive', 'latitude') && Schema::hasColumn('distributor_location_logs_archive', 'longitude')) {
            DB::statement("\n                UPDATE distributor_location_logs_archive\n                SET location = ST_GeomFromText(CONCAT('POINT(', longitude, ' ', latitude, ')'))\n                WHERE location IS NULL\n            ");
        }

        DB::statement('ALTER TABLE distributor_location_logs_archive MODIFY location POINT NOT NULL');

        if (! $this->indexExists('distributor_location_logs_archive', 'dlla_location_spatial_idx')) {
            DB::statement('ALTER TABLE distributor_location_logs_archive ADD SPATIAL INDEX dlla_location_spatial_idx (location)');
        }

        Schema::table('distributor_location_logs_archive', function (Blueprint $table): void {
            if (Schema::hasColumn('distributor_location_logs_archive', 'latitude')) {
                $table->dropColumn('latitude');
            }

            if (Schema::hasColumn('distributor_location_logs_archive', 'longitude')) {
                $table->dropColumn('longitude');
            }
        });
    }

    private function restoreLiveTableColumns(): void
    {
        if (! Schema::hasTable('distributor_location_logs')) {
            return;
        }

        Schema::table('distributor_location_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('distributor_location_logs', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('order_id');
            }

            if (! Schema::hasColumn('distributor_location_logs', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });

        if (Schema::hasColumn('distributor_location_logs', 'location')) {
            DB::statement('UPDATE distributor_location_logs SET latitude = ST_Y(location), longitude = ST_X(location) WHERE location IS NOT NULL');
        }

        if ($this->indexExists('distributor_location_logs', 'dll_location_spatial_idx')) {
            DB::statement('ALTER TABLE distributor_location_logs DROP INDEX dll_location_spatial_idx');
        }

        Schema::table('distributor_location_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('distributor_location_logs', 'location')) {
                $table->dropColumn('location');
            }
        });

        DB::statement('ALTER TABLE distributor_location_logs MODIFY latitude DECIMAL(10,7) NOT NULL, MODIFY longitude DECIMAL(10,7) NOT NULL');
    }

    private function restoreArchiveTableColumns(): void
    {
        if (! Schema::hasTable('distributor_location_logs_archive')) {
            return;
        }

        Schema::table('distributor_location_logs_archive', function (Blueprint $table): void {
            if (! Schema::hasColumn('distributor_location_logs_archive', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('order_id');
            }

            if (! Schema::hasColumn('distributor_location_logs_archive', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });

        if (Schema::hasColumn('distributor_location_logs_archive', 'location')) {
            DB::statement('UPDATE distributor_location_logs_archive SET latitude = ST_Y(location), longitude = ST_X(location) WHERE location IS NOT NULL');
        }

        if ($this->indexExists('distributor_location_logs_archive', 'dlla_location_spatial_idx')) {
            DB::statement('ALTER TABLE distributor_location_logs_archive DROP INDEX dlla_location_spatial_idx');
        }

        Schema::table('distributor_location_logs_archive', function (Blueprint $table): void {
            if (Schema::hasColumn('distributor_location_logs_archive', 'location')) {
                $table->dropColumn('location');
            }
        });

        DB::statement('ALTER TABLE distributor_location_logs_archive MODIFY latitude DECIMAL(10,7) NOT NULL, MODIFY longitude DECIMAL(10,7) NOT NULL');
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
