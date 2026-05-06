<?php

namespace App\Services\Catalog;

use App\Models\Catalog\Product;
use App\Models\Catalog\ProductVariant;
use App\Models\Catalog\VariantType;
use App\Models\Catalog\VariantValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VariantService
{
    public function createType(array $data): VariantType
    {
        return VariantType::create($data);
    }

    public function updateType(VariantType $type, array $data): VariantType
    {
        $type->update($data);

        return $type;
    }

    public function deleteType(VariantType $type): void
    {
        $type->delete();
    }

    public function createValue(array $data): VariantValue
    {
        return VariantValue::create($data);
    }

    public function updateValue(VariantValue $value, array $data): VariantValue
    {
        $value->update($data);

        return $value;
    }

    public function deleteValue(VariantValue $value): void
    {
        $value->delete();
    }

    public function syncProductVariants(Product $product, array $variants, array $units): void
    {
        DB::transaction(function () use ($product, $variants, $units) {
            $product->productVariants()->delete();

            foreach ($variants as $variantRow) {
                $variantValueId = (int) ($variantRow['variant_value_id'] ?? 0);

                if ($variantValueId <= 0) {
                    continue;
                }

                $productVariant = ProductVariant::create([
                    'product_id' => $product->id,
                    'variant_value_id' => $variantValueId,
                ]);

                foreach ($units as $unitRow) {
                    $unitId = (int) ($unitRow['unit_id'] ?? 0);

                    if ($unitId <= 0) {
                        continue;
                    }

                    $productVariant->variantUnits()->create([
                        'unit_id' => $unitId,
                        'wholesale_price' => (float) $unitRow['wholesale_price'],
                        'retail_price' => (float) $unitRow['retail_price'],
                    ]);
                }
            }
        });
    }

    public function variantsFormData(): Collection
    {
        return VariantType::query()
            ->with('values:id,variant_type_id,value')
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}






