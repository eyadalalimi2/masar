<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createNewTables();
        $this->migrateLegacyData();
    }

    public function down(): void
    {
        Schema::dropIfExists('product_configuration_units');
        Schema::dropIfExists('product_configuration_attribute_values');
        Schema::dropIfExists('product_configurations');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');
    }

    private function createNewTables(): void
    {
        if (! Schema::hasTable('product_attributes')) {
            Schema::create('product_attributes', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('name', 120);
                $table->string('slug', 140)->unique();
                $table->boolean('is_filterable')->default(true)->index();
                $table->boolean('is_variation')->default(true)->index();
                $table->boolean('is_required')->default(false);
                $table->unsignedSmallInteger('sort_order')->default(0)->index();
                $table->string('status', 32)->default('active')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_attribute_values')) {
            Schema::create('product_attribute_values', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('product_attribute_id')->index();
                $table->string('value', 160);
                $table->string('normalized_value', 180);
                $table->string('slug', 200)->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0)->index();
                $table->string('status', 32)->default('active')->index();
                $table->timestamps();

                $table->unique(['product_attribute_id', 'normalized_value'], 'pav_attribute_normalized_unique');
            });
        }

        if (! Schema::hasTable('product_configurations')) {
            Schema::create('product_configurations', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('product_id')->index();
                $table->string('name', 255)->nullable();
                $table->string('configuration_key', 255)->index();
                $table->string('sku', 120)->nullable()->index();
                $table->string('barcode', 120)->nullable()->index();
                $table->boolean('is_default')->default(false)->index();
                $table->string('status', 32)->default('active')->index();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['product_id', 'configuration_key'], 'product_configurations_product_key_unique');
            });
        }

        if (! Schema::hasTable('product_configuration_attribute_values')) {
            Schema::create('product_configuration_attribute_values', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('product_configuration_id')->index('pcav_configuration_id_idx');
                $table->unsignedBigInteger('product_attribute_value_id')->index('pcav_attribute_value_id_idx');
                $table->timestamps();

                $table->unique(
                    ['product_configuration_id', 'product_attribute_value_id'],
                    'pcav_configuration_attribute_value_unique'
                );
            });
        }

        if (! Schema::hasTable('product_configuration_units')) {
            Schema::create('product_configuration_units', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('product_configuration_id')->index();
                $table->unsignedBigInteger('unit_id')->index();
                $table->decimal('wholesale_price', 12)->default(0);
                $table->decimal('retail_price', 12)->default(0);
                $table->decimal('conversion_factor', 12, 4)->default(1);
                $table->decimal('stock_quantity', 14, 3)->default(0);
                $table->decimal('low_stock_threshold', 14, 3)->default(0);
                $table->timestamps();

                $table->unique(['product_configuration_id', 'unit_id'], 'pcu_configuration_unit_unique');
            });
        }
    }

    private function migrateLegacyData(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        // 1) Migrate variant types -> product attributes
        $variantTypeToAttribute = [];
        if (Schema::hasTable('variant_types')) {
            $variantTypes = DB::table('variant_types')->select('id', 'name')->orderBy('id')->get();

            foreach ($variantTypes as $type) {
                $name = trim((string) $type->name);
                if ($name === '') {
                    continue;
                }

                $attributeId = $this->upsertAttribute($name);
                $variantTypeToAttribute[(int) $type->id] = $attributeId;
            }
        }

        // 2) Migrate variant values -> product attribute values
        $variantValueToAttributeValue = [];
        if (Schema::hasTable('variant_values')) {
            $variantValues = DB::table('variant_values')
                ->select('id', 'variant_type_id', 'value')
                ->orderBy('id')
                ->get();

            foreach ($variantValues as $value) {
                $attributeId = $variantTypeToAttribute[(int) $value->variant_type_id] ?? null;
                if (! $attributeId) {
                    continue;
                }

                $attributeValueId = $this->upsertAttributeValue($attributeId, (string) $value->value);
                $variantValueToAttributeValue[(int) $value->id] = $attributeValueId;
            }
        }

        $products = DB::table('products')->select('id', 'name')->orderBy('id')->get();

        foreach ($products as $product) {
            $productId = (int) $product->id;

            $legacyVariants = Schema::hasTable('product_variants')
                ? DB::table('product_variants')
                ->select('id', 'variant_value_id')
                ->where('product_id', $productId)
                ->orderBy('id')
                ->get()
                : collect();

            if ($legacyVariants->isEmpty()) {
                $defaultConfigurationId = $this->upsertConfiguration(
                    productId: $productId,
                    configurationKey: 'default',
                    name: null,
                    isDefault: true
                );

                $this->copyBaseUnitsToConfiguration($productId, $defaultConfigurationId);
                continue;
            }

            foreach ($legacyVariants as $legacyVariant) {
                $variantId = (int) $legacyVariant->id;
                $legacyVariantValueId = (int) $legacyVariant->variant_value_id;
                $attributeValueId = $variantValueToAttributeValue[$legacyVariantValueId] ?? null;

                $configKey = 'legacy_variant_' . $variantId;
                $configurationId = $this->upsertConfiguration(
                    productId: $productId,
                    configurationKey: $configKey,
                    name: null,
                    isDefault: false
                );

                if ($attributeValueId) {
                    DB::table('product_configuration_attribute_values')->updateOrInsert(
                        [
                            'product_configuration_id' => $configurationId,
                            'product_attribute_value_id' => $attributeValueId,
                        ],
                        [
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }

                $this->copyLegacyVariantUnitsToConfiguration($variantId, $productId, $configurationId);
            }
        }
    }

    private function upsertAttribute(string $name): int
    {
        $slug = $this->slugify($name);

        $existing = DB::table('product_attributes')->where('slug', $slug)->value('id');
        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('product_attributes')->insertGetId([
            'name' => $name,
            'slug' => $slug,
            'is_filterable' => true,
            'is_variation' => true,
            'is_required' => false,
            'sort_order' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function upsertAttributeValue(int $attributeId, string $value): int
    {
        $normalized = mb_strtolower(trim($value));
        $slug = $this->slugify($value);

        $existing = DB::table('product_attribute_values')
            ->where('product_attribute_id', $attributeId)
            ->where('normalized_value', $normalized)
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('product_attribute_values')->insertGetId([
            'product_attribute_id' => $attributeId,
            'value' => trim($value),
            'normalized_value' => $normalized,
            'slug' => $slug,
            'sort_order' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function upsertConfiguration(int $productId, string $configurationKey, ?string $name, bool $isDefault): int
    {
        $existing = DB::table('product_configurations')
            ->where('product_id', $productId)
            ->where('configuration_key', $configurationKey)
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('product_configurations')->insertGetId([
            'product_id' => $productId,
            'name' => $name,
            'configuration_key' => $configurationKey,
            'sku' => null,
            'barcode' => null,
            'is_default' => $isDefault,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function copyLegacyVariantUnitsToConfiguration(int $variantId, int $productId, int $configurationId): void
    {
        if (Schema::hasTable('product_variant_units')) {
            $variantUnits = DB::table('product_variant_units')
                ->select('unit_id', 'wholesale_price', 'retail_price')
                ->where('product_variant_id', $variantId)
                ->get();

            if ($variantUnits->isNotEmpty()) {
                foreach ($variantUnits as $unit) {
                    DB::table('product_configuration_units')->updateOrInsert(
                        [
                            'product_configuration_id' => $configurationId,
                            'unit_id' => (int) $unit->unit_id,
                        ],
                        [
                            'wholesale_price' => (float) $unit->wholesale_price,
                            'retail_price' => (float) $unit->retail_price,
                            'conversion_factor' => 1,
                            'stock_quantity' => 0,
                            'low_stock_threshold' => 0,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }

                return;
            }
        }

        $this->copyBaseUnitsToConfiguration($productId, $configurationId);
    }

    private function copyBaseUnitsToConfiguration(int $productId, int $configurationId): void
    {
        if (! Schema::hasTable('product_units')) {
            return;
        }

        $units = DB::table('product_units')
            ->select('unit_id', 'wholesale_price', 'retail_price', 'conversion_factor', 'stock_quantity', 'low_stock_threshold')
            ->where('product_id', $productId)
            ->get();

        foreach ($units as $unit) {
            DB::table('product_configuration_units')->updateOrInsert(
                [
                    'product_configuration_id' => $configurationId,
                    'unit_id' => (int) $unit->unit_id,
                ],
                [
                    'wholesale_price' => (float) $unit->wholesale_price,
                    'retail_price' => (float) $unit->retail_price,
                    'conversion_factor' => (float) ($unit->conversion_factor ?? 1),
                    'stock_quantity' => (float) ($unit->stock_quantity ?? 0),
                    'low_stock_threshold' => (float) ($unit->low_stock_threshold ?? 0),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    private function slugify(string $text): string
    {
        $normalized = trim(mb_strtolower($text));
        $normalized = preg_replace('/[^\p{L}\p{N}]+/u', '-', $normalized) ?? '';
        $normalized = trim($normalized, '-');

        return $normalized !== '' ? $normalized : 'attr-' . substr(sha1($text), 0, 10);
    }
};
