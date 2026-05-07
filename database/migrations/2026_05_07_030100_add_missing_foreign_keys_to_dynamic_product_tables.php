<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->cleanupOrphans();

        if (Schema::hasTable('product_attribute_values') && ! $this->hasForeign('product_attribute_values', 'pav_attr_fk')) {
            Schema::table('product_attribute_values', function (Blueprint $table): void {
                $table->foreign('product_attribute_id', 'pav_attr_fk')
                    ->references('id')
                    ->on('product_attributes')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('product_configurations') && ! $this->hasForeign('product_configurations', 'pc_product_fk')) {
            Schema::table('product_configurations', function (Blueprint $table): void {
                $table->foreign('product_id', 'pc_product_fk')
                    ->references('id')
                    ->on('products')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('product_configuration_attribute_values')) {
            if (! $this->hasForeign('product_configuration_attribute_values', 'pcav_cfg_fk')) {
                Schema::table('product_configuration_attribute_values', function (Blueprint $table): void {
                    $table->foreign('product_configuration_id', 'pcav_cfg_fk')
                        ->references('id')
                        ->on('product_configurations')
                        ->cascadeOnDelete();
                });
            }

            if (! $this->hasForeign('product_configuration_attribute_values', 'pcav_attr_val_fk')) {
                Schema::table('product_configuration_attribute_values', function (Blueprint $table): void {
                    $table->foreign('product_attribute_value_id', 'pcav_attr_val_fk')
                        ->references('id')
                        ->on('product_attribute_values')
                        ->cascadeOnDelete();
                });
            }
        }

        if (Schema::hasTable('product_configuration_units')) {
            if (! $this->hasForeign('product_configuration_units', 'pcu_cfg_fk')) {
                Schema::table('product_configuration_units', function (Blueprint $table): void {
                    $table->foreign('product_configuration_id', 'pcu_cfg_fk')
                        ->references('id')
                        ->on('product_configurations')
                        ->cascadeOnDelete();
                });
            }

            if (! $this->hasForeign('product_configuration_units', 'pcu_unit_fk')) {
                Schema::table('product_configuration_units', function (Blueprint $table): void {
                    $table->foreign('unit_id', 'pcu_unit_fk')
                        ->references('id')
                        ->on('units')
                        ->restrictOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        $drops = [
            ['table' => 'product_configuration_units', 'fk' => 'pcu_unit_fk'],
            ['table' => 'product_configuration_units', 'fk' => 'pcu_cfg_fk'],
            ['table' => 'product_configuration_attribute_values', 'fk' => 'pcav_attr_val_fk'],
            ['table' => 'product_configuration_attribute_values', 'fk' => 'pcav_cfg_fk'],
            ['table' => 'product_configurations', 'fk' => 'pc_product_fk'],
            ['table' => 'product_attribute_values', 'fk' => 'pav_attr_fk'],
        ];

        foreach ($drops as $drop) {
            if (! Schema::hasTable($drop['table']) || ! $this->hasForeign($drop['table'], $drop['fk'])) {
                continue;
            }

            Schema::table($drop['table'], function (Blueprint $table) use ($drop): void {
                $table->dropForeign($drop['fk']);
            });
        }
    }

    private function cleanupOrphans(): void
    {
        if (Schema::hasTable('product_attribute_values') && Schema::hasTable('product_attributes')) {
            DB::table('product_attribute_values as pav')
                ->leftJoin('product_attributes as pa', 'pa.id', '=', 'pav.product_attribute_id')
                ->whereNull('pa.id')
                ->delete();
        }

        if (Schema::hasTable('product_configurations') && Schema::hasTable('products')) {
            DB::table('product_configurations as pc')
                ->leftJoin('products as p', 'p.id', '=', 'pc.product_id')
                ->whereNull('p.id')
                ->delete();
        }

        if (Schema::hasTable('product_configuration_attribute_values')) {
            if (Schema::hasTable('product_configurations')) {
                DB::table('product_configuration_attribute_values as pcav')
                    ->leftJoin('product_configurations as pc', 'pc.id', '=', 'pcav.product_configuration_id')
                    ->whereNull('pc.id')
                    ->delete();
            }

            if (Schema::hasTable('product_attribute_values')) {
                DB::table('product_configuration_attribute_values as pcav')
                    ->leftJoin('product_attribute_values as pav', 'pav.id', '=', 'pcav.product_attribute_value_id')
                    ->whereNull('pav.id')
                    ->delete();
            }
        }

        if (Schema::hasTable('product_configuration_units')) {
            if (Schema::hasTable('product_configurations')) {
                DB::table('product_configuration_units as pcu')
                    ->leftJoin('product_configurations as pc', 'pc.id', '=', 'pcu.product_configuration_id')
                    ->whereNull('pc.id')
                    ->delete();
            }

            if (Schema::hasTable('units')) {
                DB::table('product_configuration_units as pcu')
                    ->leftJoin('units as u', 'u.id', '=', 'pcu.unit_id')
                    ->whereNull('u.id')
                    ->delete();
            }
        }
    }

    private function hasForeign(string $table, string $constraint): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('constraint_name', $constraint)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
