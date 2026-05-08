<?php

namespace App\Services\Catalog;

use App\Models\Catalog\ProductAttribute;
use App\Models\Catalog\ProductAttributeValue;
use App\Models\Catalog\ProductConfiguration;
use App\Models\Catalog\ProductVariant;
use App\Models\Catalog\VariantType;
use App\Models\Catalog\VariantValue;
use App\Models\Catalog\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $payload = $this->preparePayload($data);
            $product = Product::create($payload);
            $this->syncUnits($product, $data['units'] ?? []);
            $this->syncVariants($product, $data['variants'] ?? []);
            $this->syncDynamicModel($product, $data);

            return $product->fresh([
                'category',
                'supplier',
                'productUnits.unit',
                'productVariants.variantValue.type',
                'productConfigurations.attributeValues.attribute',
                'productConfigurations.units.unit',
            ]);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $payload = $this->preparePayload($data, $product);
            $product->update($payload);
            $this->syncUnits($product, $data['units'] ?? []);
            $this->syncVariants($product, $data['variants'] ?? []);
            $this->syncDynamicModel($product, $data);

            return $product->fresh([
                'category',
                'supplier',
                'productUnits.unit',
                'productVariants.variantValue.type',
                'productConfigurations.attributeValues.attribute',
                'productConfigurations.units.unit',
            ]);
        });
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function restore(Product $product): void
    {
        if ($product->trashed()) {
            $product->restore();
        }
    }

    public function forceDelete(Product $product): void
    {
        DB::transaction(function () use ($product) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            $product->productConfigurations()->withTrashed()->forceDelete();
            $product->productVariants()->withTrashed()->forceDelete();
            $product->productUnits()->withTrashed()->forceDelete();
            $product->forceDelete();
        });
    }

    public function toggleStatus(Product $product): Product
    {
        $product->status = $product->status === Product::STATUS_ACTIVE ? Product::STATUS_INACTIVE : Product::STATUS_ACTIVE;
        $product->save();

        return $product;
    }

    private function preparePayload(array $data, ?Product $product = null): array
    {
        $carModels = collect((array) ($data['car_models'] ?? []))
            ->filter(static fn($value) => $value !== null && $value !== '')
            ->map(static fn($value) => (int) $value)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $payload = [
            'supplier_id' => $data['supplier_id'],
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'model' => $data['model'],
            'car_models' => count($carModels) > 0 ? $carModels : null,
            'production_year_from' => count($carModels) > 0 ? min($carModels) : null,
            'production_year_to' => count($carModels) > 0 ? max($carModels) : null,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? Product::STATUS_ACTIVE,
        ];

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            if ($product?->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            $payload['image'] = $data['image']->store('catalog/products', 'public');
        }

        return $payload;
    }

    private function syncUnits(Product $product, array $units): void
    {
        $product->productUnits()->withTrashed()->forceDelete();

        foreach ($units as $row) {
            $unitId = (int) ($row['unit_id'] ?? 0);

            if ($unitId <= 0) {
                continue;
            }

            $product->productUnits()->create([
                'unit_id' => $unitId,
                'wholesale_price' => (float) $row['wholesale_price'],
                'retail_price' => (float) ($row['retail_price'] ?? $row['wholesale_price']),
                'stock_quantity' => isset($row['stock_quantity']) && $row['stock_quantity'] !== ''
                    ? (float) $row['stock_quantity']
                    : 0,
                'conversion_factor' => isset($row['conversion_factor']) && $row['conversion_factor'] !== ''
                    ? (float) $row['conversion_factor']
                    : 1,
            ]);
        }
    }

    private function syncVariants(Product $product, array $variants): void
    {
        $product->productVariants()->withTrashed()->forceDelete();

        if (count($variants) === 0) {
            return;
        }

        $units = $product->productUnits()->get();

        foreach ($variants as $row) {
            $variantValueId = (int) ($row['variant_value_id'] ?? 0);

            if ($variantValueId <= 0) {
                continue;
            }

            $productVariant = ProductVariant::create([
                'product_id' => $product->id,
                'variant_value_id' => $variantValueId,
            ]);

            foreach ($units as $unit) {
                $productVariant->variantUnits()->create([
                    'unit_id' => $unit->unit_id,
                    'wholesale_price' => $unit->wholesale_price,
                    'retail_price' => $unit->retail_price,
                ]);
            }
        }
    }

    private function syncDynamicModel(Product $product, array $data): void
    {
        $dynamicConfigurations = $this->normalizeConfigurationsFromPayload($data);
        if ($dynamicConfigurations === []) {
            $dynamicConfigurations = $this->normalizeConfigurationsFromLegacyVariants($data['variants'] ?? []);
        }

        if ($dynamicConfigurations === []) {
            $this->syncDefaultConfiguration($product, $data['units'] ?? []);

            return;
        }

        $product->productConfigurations()->withTrashed()->forceDelete();

        foreach ($dynamicConfigurations as $index => $configuration) {
            $attributeValueIds = collect((array) ($configuration['attribute_value_ids'] ?? []))
                ->map(fn($id) => (int) $id)
                ->filter(fn($id) => $id > 0)
                ->unique()
                ->sort()
                ->values()
                ->all();

            $configurationKey = count($attributeValueIds) > 0
                ? implode('-', $attributeValueIds)
                : ('default-' . $index);

            $config = $product->productConfigurations()->create([
                'name' => isset($configuration['name']) ? trim((string) $configuration['name']) : null,
                'configuration_key' => $configurationKey,
                'sku' => $this->nullableTrim($configuration['sku'] ?? null),
                'barcode' => $this->nullableTrim($configuration['barcode'] ?? null),
                'is_default' => (bool) ($configuration['is_default'] ?? ($index === 0)),
                'status' => (string) ($configuration['status'] ?? Product::STATUS_ACTIVE),
            ]);

            if ($attributeValueIds !== []) {
                $config->attributeValues()->sync($attributeValueIds);
            }

            $unitRows = (array) ($configuration['units'] ?? []);
            foreach ($unitRows as $unitRow) {
                $unitId = (int) ($unitRow['unit_id'] ?? 0);
                if ($unitId <= 0) {
                    continue;
                }

                $config->units()->create([
                    'unit_id' => $unitId,
                    'wholesale_price' => (float) ($unitRow['wholesale_price'] ?? 0),
                    'retail_price' => (float) ($unitRow['retail_price'] ?? ($unitRow['wholesale_price'] ?? 0)),
                    'conversion_factor' => isset($unitRow['conversion_factor']) && $unitRow['conversion_factor'] !== ''
                        ? (float) $unitRow['conversion_factor']
                        : 1,
                    'stock_quantity' => isset($unitRow['stock_quantity']) && $unitRow['stock_quantity'] !== ''
                        ? (float) $unitRow['stock_quantity']
                        : 0,
                    'low_stock_threshold' => isset($unitRow['low_stock_threshold']) && $unitRow['low_stock_threshold'] !== ''
                        ? (float) $unitRow['low_stock_threshold']
                        : 0,
                ]);
            }
        }
    }

    private function syncDefaultConfiguration(Product $product, array $units): void
    {
        $product->productConfigurations()->withTrashed()->forceDelete();

        $configuration = $product->productConfigurations()->create([
            'name' => null,
            'configuration_key' => 'default',
            'sku' => null,
            'barcode' => null,
            'is_default' => true,
            'status' => Product::STATUS_ACTIVE,
        ]);

        foreach ($units as $unitRow) {
            $unitId = (int) ($unitRow['unit_id'] ?? 0);
            if ($unitId <= 0) {
                continue;
            }

            $configuration->units()->create([
                'unit_id' => $unitId,
                'wholesale_price' => (float) ($unitRow['wholesale_price'] ?? 0),
                'retail_price' => (float) ($unitRow['retail_price'] ?? ($unitRow['wholesale_price'] ?? 0)),
                'conversion_factor' => isset($unitRow['conversion_factor']) && $unitRow['conversion_factor'] !== ''
                    ? (float) $unitRow['conversion_factor']
                    : 1,
                'stock_quantity' => isset($unitRow['stock_quantity']) && $unitRow['stock_quantity'] !== ''
                    ? (float) $unitRow['stock_quantity']
                    : 0,
                'low_stock_threshold' => isset($unitRow['low_stock_threshold']) && $unitRow['low_stock_threshold'] !== ''
                    ? (float) $unitRow['low_stock_threshold']
                    : 0,
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeConfigurationsFromPayload(array $data): array
    {
        $configurations = (array) ($data['configurations'] ?? []);
        if ($configurations === []) {
            return [];
        }

        return collect($configurations)
            ->filter(fn($cfg) => is_array($cfg))
            ->map(fn($cfg) => [
                'name' => $cfg['name'] ?? null,
                'sku' => $cfg['sku'] ?? null,
                'barcode' => $cfg['barcode'] ?? null,
                'is_default' => (bool) ($cfg['is_default'] ?? false),
                'status' => $cfg['status'] ?? Product::STATUS_ACTIVE,
                'attribute_value_ids' => array_values(array_unique(array_map('intval', (array) ($cfg['attribute_value_ids'] ?? [])))),
                'units' => (array) ($cfg['units'] ?? []),
            ])
            ->values()
            ->all();
    }

    /**
     * @param array<int, mixed> $legacyVariants
     * @return array<int, array<string, mixed>>
     */
    private function normalizeConfigurationsFromLegacyVariants(array $legacyVariants): array
    {
        $configs = [];

        foreach ($legacyVariants as $variant) {
            if (! is_array($variant)) {
                continue;
            }

            $typeId = (int) ($variant['variant_type_id'] ?? 0);
            $valueId = (int) ($variant['variant_value_id'] ?? 0);
            if ($typeId <= 0 || $valueId <= 0) {
                continue;
            }

            $attribute = $this->upsertAttributeFromLegacyType($typeId);
            if (! $attribute) {
                continue;
            }

            $attributeValue = $this->upsertAttributeValueFromLegacyValue($attribute->id, $valueId);
            if (! $attributeValue) {
                continue;
            }

            $configs[] = [
                'name' => null,
                'sku' => null,
                'barcode' => null,
                'is_default' => false,
                'status' => Product::STATUS_ACTIVE,
                'attribute_value_ids' => [$attributeValue->id],
                'units' => (array) [],
            ];
        }

        return $configs;
    }

    private function upsertAttributeFromLegacyType(int $legacyTypeId): ?ProductAttribute
    {
        $legacyType = VariantType::query()->find($legacyTypeId);
        if (! $legacyType) {
            return null;
        }

        $name = trim((string) $legacyType->name);
        if ($name === '') {
            return null;
        }

        $slug = $this->slugify($name);

        return ProductAttribute::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'is_filterable' => true,
                'is_variation' => true,
                'is_required' => false,
                'sort_order' => 0,
                'status' => Product::STATUS_ACTIVE,
            ]
        );
    }

    private function upsertAttributeValueFromLegacyValue(int $attributeId, int $legacyValueId): ?ProductAttributeValue
    {
        $legacyValue = VariantValue::query()->find($legacyValueId);
        if (! $legacyValue) {
            return null;
        }

        $value = trim((string) $legacyValue->value);
        if ($value === '') {
            return null;
        }

        $normalized = mb_strtolower($value);

        return ProductAttributeValue::query()->firstOrCreate(
            [
                'product_attribute_id' => $attributeId,
                'normalized_value' => $normalized,
            ],
            [
                'value' => $value,
                'slug' => $this->slugify($value),
                'sort_order' => 0,
                'status' => Product::STATUS_ACTIVE,
            ]
        );
    }

    private function slugify(string $text): string
    {
        $slug = Str::of($text)->lower()->slug('-')->toString();

        return $slug !== '' ? $slug : 'attribute-' . substr(sha1($text), 0, 10);
    }

    private function nullableTrim(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
