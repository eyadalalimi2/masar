<?php

namespace App\Services\Distribution;

use App\Models\Catalog\InventoryMovement;
use App\Models\Catalog\ProductUnit;
use App\Models\Distribution\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function getSupplierInventory(int $supplierId, int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = ProductUnit::query()
            ->with([
                'product:id,supplier_id,category_id,name,model,image,status',
                'product.category:id,name',
                'unit:id,name',
            ])
            ->whereHas('product', function ($query) use ($supplierId) {
                $query->where('supplier_id', $supplierId);
            })
            ->orderByDesc('stock_quantity');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->whereHas('product', function ($productQuery) use ($search) {
                    $productQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('model', 'like', '%' . $search . '%');
                })->orWhereHas('unit', function ($unitQuery) use ($search) {
                    $unitQuery->where('name', 'like', '%' . $search . '%');
                });
            });
        }

        $categoryId = (int) ($filters['category_id'] ?? 0);
        if ($categoryId > 0) {
            $query->whereHas('product', fn($productQuery) => $productQuery->where('category_id', $categoryId));
        }

        $unitId = (int) ($filters['unit_id'] ?? 0);
        if ($unitId > 0) {
            $query->where('unit_id', $unitId);
        }

        $stockStatus = trim((string) ($filters['stock_status'] ?? ''));
        if ($stockStatus === 'low') {
            $query->where('low_stock_threshold', '>', 0)
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
        } elseif ($stockStatus === 'normal') {
            $query->where(function ($inner) {
                $inner->where('low_stock_threshold', '<=', 0)
                    ->orWhereColumn('stock_quantity', '>', 'low_stock_threshold');
            });
        }

        $stockFrom = $filters['stock_from'] ?? null;
        if ($stockFrom !== null && is_numeric($stockFrom)) {
            $query->where('stock_quantity', '>=', (float) $stockFrom);
        }

        $stockTo = $filters['stock_to'] ?? null;
        if ($stockTo !== null && is_numeric($stockTo)) {
            $query->where('stock_quantity', '<=', (float) $stockTo);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function getRecentMovements(int $supplierId, int $limit = 30)
    {
        return InventoryMovement::query()
            ->with([
                'product:id,name,model',
                'productUnit:id,product_id,unit_id',
                'productUnit.unit:id,name',
                'branch:id,name',
            ])
            ->where('supplier_id', $supplierId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function addStock(int $supplierId, int $agentId, ProductUnit $productUnit, float $quantity, ?float $lowStockThreshold = null, ?string $note = null): ProductUnit
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('الكمية يجب أن تكون أكبر من صفر.');
        }

        return DB::transaction(function () use ($supplierId, $agentId, $productUnit, $quantity, $lowStockThreshold, $note) {
            $this->assertSupplierOwnership($supplierId, $productUnit);

            $before = (float) $productUnit->stock_quantity;
            $after = $before + $quantity;

            $productUnit->stock_quantity = $after;
            if ($lowStockThreshold !== null) {
                $productUnit->low_stock_threshold = max(0, $lowStockThreshold);
            }
            $productUnit->save();

            InventoryMovement::create([
                'supplier_id' => $supplierId,
                'product_id' => $productUnit->product_id,
                'product_unit_id' => $productUnit->id,
                'agent_id' => $agentId,
                'movement_type' => 'in',
                'quantity' => $quantity,
                'stock_before' => $before,
                'stock_after' => $after,
                'note' => $note,
            ]);

            return $productUnit->fresh(['product', 'unit']);
        });
    }

    public function adjustStock(int $supplierId, int $agentId, ProductUnit $productUnit, float $newQuantity, ?float $lowStockThreshold = null, ?string $note = null): ProductUnit
    {
        if ($newQuantity < 0) {
            throw new \InvalidArgumentException('الكمية الجديدة لا يمكن أن تكون أقل من صفر.');
        }

        return DB::transaction(function () use ($supplierId, $agentId, $productUnit, $newQuantity, $lowStockThreshold, $note) {
            $this->assertSupplierOwnership($supplierId, $productUnit);

            $before = (float) $productUnit->stock_quantity;
            $after = $newQuantity;
            $movementQuantity = abs($after - $before);

            $productUnit->stock_quantity = $after;
            if ($lowStockThreshold !== null) {
                $productUnit->low_stock_threshold = max(0, $lowStockThreshold);
            }
            $productUnit->save();

            InventoryMovement::create([
                'supplier_id' => $supplierId,
                'product_id' => $productUnit->product_id,
                'product_unit_id' => $productUnit->id,
                'agent_id' => $agentId,
                'movement_type' => 'adjustment',
                'quantity' => $movementQuantity,
                'stock_before' => $before,
                'stock_after' => $after,
                'note' => $note,
            ]);

            return $productUnit->fresh(['product', 'unit']);
        });
    }

    public function distributeToBranch(int $supplierId, int $agentId, ProductUnit $productUnit, Branch $branch, float $quantity, ?string $note = null): ProductUnit
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('كمية التوزيع يجب أن تكون أكبر من صفر.');
        }

        return DB::transaction(function () use ($supplierId, $agentId, $productUnit, $branch, $quantity, $note) {
            $this->assertSupplierOwnership($supplierId, $productUnit);

            if ((int) $branch->supplier_id !== $supplierId) {
                throw new \InvalidArgumentException('لا يمكن التوزيع إلى فرع خارج نطاق الوكيل.');
            }

            $before = (float) $productUnit->stock_quantity;
            if ($quantity > $before) {
                throw new \InvalidArgumentException('الكمية المطلوبة أكبر من المخزون المتاح.');
            }

            $after = $before - $quantity;
            $productUnit->stock_quantity = $after;
            $productUnit->save();

            InventoryMovement::create([
                'supplier_id' => $supplierId,
                'product_id' => $productUnit->product_id,
                'product_unit_id' => $productUnit->id,
                'branch_id' => $branch->id,
                'agent_id' => $agentId,
                'movement_type' => 'out',
                'quantity' => $quantity,
                'stock_before' => $before,
                'stock_after' => $after,
                'note' => $note,
            ]);

            return $productUnit->fresh(['product', 'unit']);
        });
    }

    private function assertSupplierOwnership(int $supplierId, ProductUnit $productUnit): void
    {
        if ((int) $productUnit->product?->supplier_id !== $supplierId) {
            throw new \InvalidArgumentException('الوحدة المحددة لا تتبع للوكيل الحالي.');
        }
    }
}
