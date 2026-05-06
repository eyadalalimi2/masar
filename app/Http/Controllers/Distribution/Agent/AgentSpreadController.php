<?php

namespace App\Http\Controllers\Distribution\Agent;

use App\Http\Controllers\Controller;
use App\Models\Customer\Workshop;
use App\Models\Distribution\Branch;
use App\Models\Distribution\BranchProductStock;
use App\Models\Catalog\Product;
use App\Models\Pos;
use App\Models\PosLocalProduct;
use App\Models\Workshop\WorkshopPurchaseOrderItem;
use App\Models\Workshop\WorkshopServiceOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AgentSpreadController extends Controller
{
    public function index(Request $request): View
    {
        $supplierId = (int) (Auth::guard('agent')->user()->supplier_id ?? 0);
        $productQuery = trim((string) $request->query('product_query', ''));

        $matchedProducts = Product::query()
            ->where('supplier_id', $supplierId)
            ->when($productQuery !== '', function ($query) use ($productQuery) {
                $query->where(function ($subQuery) use ($productQuery) {
                    $subQuery
                        ->where('name', 'like', '%' . $productQuery . '%')
                        ->orWhere('model', 'like', '%' . $productQuery . '%');

                    if (is_numeric($productQuery)) {
                        $subQuery->orWhere('id', (int) $productQuery);
                    }
                });
            })
            ->get(['id', 'name']);

        $matchedProductIds = $matchedProducts->pluck('id');
        $showAllPoints = $productQuery === '';

        $branchStockStats = BranchProductStock::query()
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->whereHas('product', fn($query) => $query->where('supplier_id', $supplierId))
            ->when(
                $productQuery !== '',
                fn($query) => $query->whereIn('product_id', $matchedProductIds->all())
            )
            ->selectRaw('branch_id, COUNT(DISTINCT product_id) as products_count')
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');

        $branches = Branch::query()
            ->where('supplier_id', $supplierId)
            ->where('status', 'active')
            ->get(['id', 'name', 'address', 'gps_location']);

        $posStockStats = PosLocalProduct::query()
            ->where('is_active', true)
            ->where('local_quantity', '>', 0)
            ->whereHas('product', fn($query) => $query->where('supplier_id', $supplierId))
            ->when(
                $productQuery !== '',
                fn($query) => $query->whereIn('product_id', $matchedProductIds->all())
            )
            ->selectRaw('pos_account_id, COUNT(DISTINCT product_id) as products_count')
            ->groupBy('pos_account_id')
            ->get()
            ->keyBy('pos_account_id');

        $commercialStores = Pos::query()
            ->with('customer:id,address,gps_location')
            ->where('status', 'active')
            ->get(['id', 'name', 'customer_id']);

        $workshopStockStats = WorkshopPurchaseOrderItem::query()
            ->join('workshop_purchase_orders as purchase_orders', 'purchase_orders.id', '=', 'workshop_purchase_order_items.purchase_order_id')
            ->join('products', 'products.id', '=', 'workshop_purchase_order_items.product_id')
            ->where('products.supplier_id', $supplierId)
            ->when(
                $productQuery !== '',
                fn($query) => $query->whereIn('workshop_purchase_order_items.product_id', $matchedProductIds->all())
            )
            ->where('purchase_orders.status', 'received')
            ->selectRaw('purchase_orders.workshop_id as workshop_id, COUNT(DISTINCT workshop_purchase_order_items.product_id) as products_count, COALESCE(SUM(workshop_purchase_order_items.quantity), 0) as stock_quantity')
            ->groupBy('purchase_orders.workshop_id')
            ->get()
            ->keyBy('workshop_id');

        $workshops = Workshop::query()
            ->with('customer:id,address,gps_location')
            ->where('status', 'active')
            ->get(['id', 'name', 'customer_id']);

        $supplierProductNames = $matchedProducts
            ->pluck('name')
            ->map(fn($name) => $this->normalizeProductName((string) $name))
            ->filter(fn($name) => $name !== '')
            ->flip();

        $workshopConsumedByName = collect();

        if ($workshops->isNotEmpty() && $supplierProductNames->isNotEmpty()) {
            $serviceOrders = WorkshopServiceOrder::query()
                ->whereIn('workshop_id', $workshops->pluck('id')->all())
                ->whereIn('status', ['in_progress', 'completed'])
                ->whereNotNull('used_products')
                ->get(['workshop_id', 'used_products']);

            foreach ($serviceOrders as $serviceOrder) {
                $usedProducts = is_array($serviceOrder->used_products) ? $serviceOrder->used_products : [];

                foreach ($usedProducts as $usedProduct) {
                    $normalizedName = $this->normalizeProductName((string) ($usedProduct['product_name'] ?? ''));
                    if ($normalizedName === '' || ! $supplierProductNames->has($normalizedName)) {
                        continue;
                    }

                    $qty = (float) ($usedProduct['quantity'] ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }

                    $workshopId = (int) $serviceOrder->workshop_id;
                    $workshopConsumedByName[$workshopId] = (float) ($workshopConsumedByName[$workshopId] ?? 0) + $qty;
                }
            }
        }

        $markers = collect();

        foreach ($branches as $branch) {
            $coords = $this->parseCoordinates((string) ($branch->gps_location ?? ''));
            if ($coords === null) {
                continue;
            }

            $productsCount = (int) ($branchStockStats->get($branch->id)?->products_count ?? 0);
            if (! $showAllPoints && $productsCount === 0) {
                continue;
            }

            $markers->push([
                'type' => 'branch',
                'type_label' => 'فرع',
                'id' => (int) $branch->id,
                'name' => (string) $branch->name,
                'address' => (string) ($branch->address ?? ''),
                'products_count' => $productsCount,
                'stock_quantity' => null,
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
            ]);
        }

        foreach ($commercialStores as $store) {
            $coords = $this->parseCoordinates((string) ($store->gps_location ?? ''));
            if ($coords === null) {
                continue;
            }

            $productsCount = (int) ($posStockStats->get($store->id)?->products_count ?? 0);
            if (! $showAllPoints && $productsCount === 0) {
                continue;
            }

            $markers->push([
                'type' => 'commercial_store',
                'type_label' => 'محل تجاري',
                'id' => (int) $store->id,
                'name' => (string) $store->name,
                'address' => (string) ($store->address ?? ''),
                'products_count' => $productsCount,
                'stock_quantity' => null,
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
            ]);
        }

        foreach ($workshops as $workshop) {
            $coords = $this->parseCoordinates((string) ($workshop->gps_location ?? ''));
            if ($coords === null) {
                continue;
            }

            $productsCount = (int) ($workshopStockStats->get($workshop->id)?->products_count ?? 0);
            if (! $showAllPoints && $productsCount === 0) {
                continue;
            }

            $receivedQuantity = (float) ($workshopStockStats->get($workshop->id)?->stock_quantity ?? 0);
            $consumedQuantity = (float) ($workshopConsumedByName[$workshop->id] ?? 0);
            $netQuantity = max(0, $receivedQuantity - $consumedQuantity);

            $markers->push([
                'type' => 'workshop',
                'type_label' => 'ورشة',
                'id' => (int) $workshop->id,
                'name' => (string) $workshop->name,
                'address' => (string) ($workshop->address ?? ''),
                'products_count' => $productsCount,
                'stock_quantity' => $netQuantity,
                'received_quantity' => $receivedQuantity,
                'consumed_quantity' => $consumedQuantity,
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
            ]);
        }

        $summary = [
            'branches' => (int) $markers->where('type', 'branch')->count(),
            'commercial_stores' => (int) $markers->where('type', 'commercial_store')->count(),
            'workshops' => (int) $markers->where('type', 'workshop')->count(),
            'all_points' => (int) $markers->count(),
        ];

        $mapCenter = $markers->isNotEmpty()
            ? ['lat' => (float) $markers->first()['lat'], 'lng' => (float) $markers->first()['lng']]
            : ['lat' => 33.3152, 'lng' => 44.3661];

        return view('agent.spread.index', [
            'markers' => $markers->values(),
            'summary' => $summary,
            'mapCenter' => $mapCenter,
            'mapsApiKey' => (string) config('services.google_maps.key'),
            'productQuery' => $productQuery,
            'matchedProductsCount' => (int) $matchedProducts->count(),
        ]);
    }

    private function parseCoordinates(?string $value): ?array
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $parts = array_map('trim', explode(',', $raw));
        if (count($parts) !== 2 || ! is_numeric($parts[0]) || ! is_numeric($parts[1])) {
            return null;
        }

        $lat = (float) $parts[0];
        $lng = (float) $parts[1];

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    private function normalizeProductName(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        return mb_strtolower((string) $normalized);
    }
}
