<?php

namespace App\Services\Catalog;

use App\Models\Catalog\ProductVariant;
use App\Models\Catalog\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $payload = $this->preparePayload($data);
            $product = Product::create($payload);
            $this->syncUnits($product, $data['units'] ?? []);
            $this->syncVariants($product, $data['variants'] ?? []);

            return $product->fresh(['category', 'supplier', 'productUnits.unit', 'productVariants.variantValue.type']);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $payload = $this->preparePayload($data, $product);
            $product->update($payload);
            $this->syncUnits($product, $data['units'] ?? []);
            $this->syncVariants($product, $data['variants'] ?? []);

            return $product->fresh(['category', 'supplier', 'productUnits.unit', 'productVariants.variantValue.type']);
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

            $product->productVariants()->delete();
            $product->productUnits()->delete();
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
        $product->productUnits()->delete();

        foreach ($units as $row) {
            $unitId = (int) ($row['unit_id'] ?? 0);

            if ($unitId <= 0) {
                continue;
            }

            $product->productUnits()->create([
                'unit_id' => $unitId,
                'wholesale_price' => (float) $row['wholesale_price'],
                'retail_price' => (float) ($row['retail_price'] ?? $row['wholesale_price']),
                'conversion_factor' => isset($row['conversion_factor']) && $row['conversion_factor'] !== ''
                    ? (float) $row['conversion_factor']
                    : 1,
            ]);
        }
    }

    private function syncVariants(Product $product, array $variants): void
    {
        $product->productVariants()->delete();

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
}
