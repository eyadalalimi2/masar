<?php

namespace App\Services\Distribution;

use App\Models\Catalog\InventoryMovement;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductUnit;
use App\Models\Distribution\Branch;
use App\Models\Distribution\BranchProductStock;
use App\Models\Distribution\BranchStockMovement;
use App\Models\Orders\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BranchInventoryService
{
    public function syncInboundFromAgent(Branch $branch): int
    {
        $movements = InventoryMovement::query()
            ->where('branch_id', $branch->id)
            ->where('movement_type', 'out')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('branch_stock_movements')
                    ->whereColumn('branch_stock_movements.inventory_movement_id', 'inventory_movements.id');
            })
            ->orderBy('id')
            ->get();

        $count = 0;
        foreach ($movements as $movement) {
            $productUnit = ProductUnit::query()->with('product')->find($movement->product_unit_id);
            if (! $productUnit || (int) $productUnit->product?->supplier_id !== (int) $branch->supplier_id) {
                continue;
            }

            DB::transaction(function () use ($branch, $productUnit, $movement): void {
                $stock = $this->findOrCreateStock($branch, $productUnit);
                $before = (float) $stock->quantity;
                $after = $before + (float) $movement->quantity;

                $stock->quantity = $after;
                if ($stock->selling_price === null) {
                    $stock->selling_price = (float) $productUnit->retail_price;
                }
                $stock->save();

                BranchStockMovement::create([
                    'branch_id' => $branch->id,
                    'product_id' => $productUnit->product_id,
                    'product_unit_id' => $productUnit->id,
                    'inventory_movement_id' => $movement->id,
                    'movement_type' => 'inbound',
                    'quantity' => (float) $movement->quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'note' => 'استلام من الوكيل: ' . ($movement->note ?? ''),
                    'created_at' => $movement->created_at,
                    'updated_at' => $movement->updated_at,
                ]);
            });

            $count++;
        }

        return $count;
    }

    public function updateStock(Branch $branch, ProductUnit $productUnit, float $quantity, ?string $note = null): BranchProductStock
    {
        if ($quantity < 0) {
            throw new \InvalidArgumentException('الكمية لا يمكن أن تكون أقل من صفر.');
        }

        return DB::transaction(function () use ($branch, $productUnit, $quantity, $note) {
            $this->assertBranchProductOwnership($branch, $productUnit);
            $stock = $this->findOrCreateStock($branch, $productUnit);

            $before = (float) $stock->quantity;
            $stock->quantity = $quantity;
            if ($stock->selling_price === null) {
                $stock->selling_price = (float) $productUnit->retail_price;
            }
            $stock->save();

            BranchStockMovement::create([
                'branch_id' => $branch->id,
                'product_id' => $productUnit->product_id,
                'product_unit_id' => $productUnit->id,
                'movement_type' => 'adjustment',
                'quantity' => abs($quantity - $before),
                'stock_before' => $before,
                'stock_after' => $quantity,
                'note' => $note,
            ]);

            return $stock->fresh(['product', 'productUnit.unit']);
        });
    }

    public function updateSellingPrice(Branch $branch, ProductUnit $productUnit, float $sellingPrice): BranchProductStock
    {
        if ($sellingPrice < 0) {
            throw new \InvalidArgumentException('سعر البيع لا يمكن أن يكون أقل من صفر.');
        }

        return DB::transaction(function () use ($branch, $productUnit, $sellingPrice) {
            $this->assertBranchProductOwnership($branch, $productUnit);
            $stock = $this->findOrCreateStock($branch, $productUnit);
            $stock->selling_price = $sellingPrice;
            $stock->save();

            return $stock->fresh(['product', 'productUnit.unit']);
        });
    }

    public function ensureOrderStockAvailable(Branch $branch, Order $order): void
    {
        $order->loadMissing('items.productUnit');

        foreach ($order->items as $item) {
            $productUnitId = (int) $item->product_unit_id;
            $requiredQty = (float) $item->quantity;

            $stock = BranchProductStock::query()
                ->where('branch_id', $branch->id)
                ->where('product_unit_id', $productUnitId)
                ->first();

            $available = (float) ($stock?->quantity ?? 0);
            if ($available < $requiredQty) {
                $productName = (string) ($item->product?->name ?? 'المنتج');
                throw new \RuntimeException('لا يوجد مخزون كافٍ للمنتج: ' . $productName);
            }
        }
    }

    public function deductOrderStock(Branch $branch, Order $order): void
    {
        DB::transaction(function () use ($branch, $order): void {
            $order->loadMissing('items.product', 'items.productUnit');

            foreach ($order->items as $item) {
                $productUnit = ProductUnit::query()->with('product')->findOrFail((int) $item->product_unit_id);
                $this->assertBranchProductOwnership($branch, $productUnit);

                $existingSale = BranchStockMovement::query()
                    ->where('branch_id', $branch->id)
                    ->where('order_id', $order->id)
                    ->where('product_unit_id', $productUnit->id)
                    ->where('movement_type', 'sale')
                    ->lockForUpdate()
                    ->first();

                if ($existingSale) {
                    continue;
                }

                $stock = BranchProductStock::query()
                    ->where('branch_id', $branch->id)
                    ->where('product_unit_id', $productUnit->id)
                    ->lockForUpdate()
                    ->first();

                if (! $stock) {
                    $stock = $this->findOrCreateStock($branch, $productUnit);
                    $stock = BranchProductStock::query()
                        ->whereKey($stock->id)
                        ->lockForUpdate()
                        ->firstOrFail();
                }

                $before = (float) $stock->quantity;
                $qty = (float) $item->quantity;

                if ($before < $qty) {
                    $productName = (string) ($item->product?->name ?? 'المنتج');
                    throw new \RuntimeException('لا يوجد مخزون كافٍ للمنتج: ' . $productName);
                }

                $after = $before - $qty;
                $stock->quantity = $after;
                $stock->save();

                BranchStockMovement::create([
                    'branch_id' => $branch->id,
                    'product_id' => $productUnit->product_id,
                    'product_unit_id' => $productUnit->id,
                    'order_id' => $order->id,
                    'distributor_id' => $order->distributor_id,
                    'movement_type' => 'sale',
                    'quantity' => $qty,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'note' => 'خصم تلقائي للطلب #' . $order->id,
                ]);
            }
        });
    }

    public function getInventoryOverview(Branch $branch): Collection
    {
        return BranchProductStock::query()
            ->with(['product:id,name,model,status', 'productUnit:id,unit_id,wholesale_price,retail_price', 'productUnit.unit:id,name'])
            ->where('branch_id', $branch->id)
            ->orderByDesc('quantity')
            ->get();
    }

    public function getStockMovements(Branch $branch, int $limit = 50): Collection
    {
        return BranchStockMovement::query()
            ->with(['product:id,name,model', 'productUnit:id,unit_id', 'productUnit.unit:id,name', 'order:id,status', 'distributor:id,name'])
            ->where('branch_id', $branch->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    private function assertBranchProductOwnership(Branch $branch, ProductUnit $productUnit): void
    {
        $product = $productUnit->product;
        if (! $product || (int) $product->supplier_id !== (int) $branch->supplier_id) {
            throw new \InvalidArgumentException('هذا المنتج لا يتبع وكيل الفرع الحالي.');
        }
    }

    private function findOrCreateStock(Branch $branch, ProductUnit $productUnit): BranchProductStock
    {
        $stock = BranchProductStock::query()->firstOrCreate(
            [
                'branch_id' => $branch->id,
                'product_unit_id' => $productUnit->id,
            ],
            [
                'product_id' => $productUnit->product_id,
                'quantity' => 0,
                'selling_price' => (float) $productUnit->retail_price,
                'is_active' => true,
            ]
        );

        if ((int) $stock->product_id !== (int) $productUnit->product_id) {
            $stock->product_id = $productUnit->product_id;
            $stock->save();
        }

        return $stock;
    }
}
