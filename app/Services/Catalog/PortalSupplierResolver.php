<?php

namespace App\Services\Catalog;

use App\Models\Customer\Workshop;
use App\Models\Pos;
use Illuminate\Support\Facades\DB;

class PortalSupplierResolver
{
    public function resolveForPos(Pos $pos): ?int
    {
        $fromLocalProducts = (int) (DB::table('pos_local_products as plp')
            ->join('branches as b', 'b.id', '=', 'plp.branch_id')
            ->where('plp.pos_account_id', (int) $pos->id)
            ->orderByDesc('plp.id')
            ->value('b.supplier_id') ?? 0);

        if ($fromLocalProducts > 0) {
            return $fromLocalProducts;
        }

        return $this->resolveFromOrders((int) ($pos->customer_id ?? 0));
    }

    public function resolveForWorkshop(Workshop $workshop): ?int
    {
        $fromPurchaseOrders = (int) (DB::table('workshop_purchase_orders as wpo')
            ->join('branches as b', 'b.id', '=', 'wpo.supplier_branch_id')
            ->where('wpo.workshop_id', (int) $workshop->id)
            ->orderByDesc('wpo.id')
            ->value('b.supplier_id') ?? 0);

        if ($fromPurchaseOrders > 0) {
            return $fromPurchaseOrders;
        }

        return $this->resolveFromOrders((int) ($workshop->customer_id ?? 0));
    }

    private function resolveFromOrders(int $customerId): ?int
    {
        if ($customerId <= 0) {
            return null;
        }

        $supplierId = (int) (DB::table('orders')
            ->where('customer_id', $customerId)
            ->whereNotNull('supplier_id')
            ->orderByDesc('id')
            ->value('supplier_id') ?? 0);

        return $supplierId > 0 ? $supplierId : null;
    }
}
