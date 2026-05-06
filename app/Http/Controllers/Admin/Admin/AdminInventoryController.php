<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distribution\Branch;
use App\Models\Distribution\BranchProductStock;
use App\Models\Distribution\BranchStockMovement;
use App\Models\Supplier\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminInventoryController extends Controller
{
    public function index(Request $request): View
    {
        $supplierId = (int) $request->query('supplier_id', 0);
        $branchId = (int) $request->query('branch_id', 0);
        $lowStockOnly = (bool) $request->boolean('low_stock_only');

        $stocks = BranchProductStock::query()
            ->join('product_units', 'product_units.id', '=', 'branch_product_stocks.product_unit_id')
            ->with([
                'branch.supplier:id,owner_name,business_name',
                'product:id,name,model',
                'productUnit:id,unit_id,low_stock_threshold',
                'productUnit.unit:id,name',
            ])
            ->when($supplierId > 0, function (Builder $query) use ($supplierId): void {
                $query->whereHas('branch', function (Builder $branchQuery) use ($supplierId): void {
                    $branchQuery->where('supplier_id', $supplierId);
                });
            })
            ->when($branchId > 0, fn(Builder $query) => $query->where('branch_product_stocks.branch_id', $branchId))
            ->when($lowStockOnly, function (Builder $query): void {
                $query->where('product_units.low_stock_threshold', '>', 0)
                    ->whereColumn('branch_product_stocks.quantity', '<=', 'product_units.low_stock_threshold');
            })
            ->select('branch_product_stocks.*', 'product_units.low_stock_threshold as threshold_value')
            ->orderBy('branch_product_stocks.quantity')
            ->paginate(20)
            ->withQueryString();

        $movements = BranchStockMovement::query()
            ->with([
                'branch:id,name,supplier_id',
                'branch.supplier:id,owner_name,business_name',
                'product:id,name,model',
                'productUnit:id,unit_id',
                'productUnit.unit:id,name',
                'order:id,status',
                'distributor:id,name',
            ])
            ->when($supplierId > 0, function (Builder $query) use ($supplierId): void {
                $query->whereHas('branch', function (Builder $branchQuery) use ($supplierId): void {
                    $branchQuery->where('supplier_id', $supplierId);
                });
            })
            ->when($branchId > 0, fn(Builder $query) => $query->where('branch_id', $branchId))
            ->latest()
            ->limit(80)
            ->get();

        $statsBase = BranchProductStock::query()
            ->join('product_units', 'product_units.id', '=', 'branch_product_stocks.product_unit_id')
            ->when($supplierId > 0, function (Builder $query) use ($supplierId): void {
                $query->whereExists(function ($subQuery) use ($supplierId): void {
                    $subQuery->selectRaw('1')
                        ->from('branches')
                        ->whereColumn('branches.id', 'branch_product_stocks.branch_id')
                        ->where('branches.supplier_id', $supplierId);
                });
            })
            ->when($branchId > 0, fn(Builder $query) => $query->where('branch_product_stocks.branch_id', $branchId));

        $stats = [
            'items_count' => (clone $statsBase)->count(),
            'branches_count' => (clone $statsBase)->distinct('branch_product_stocks.branch_id')->count('branch_product_stocks.branch_id'),
            'total_quantity' => (float) ((clone $statsBase)->sum('branch_product_stocks.quantity') ?? 0),
            'low_stock_count' => (clone $statsBase)
                ->where('product_units.low_stock_threshold', '>', 0)
                ->whereColumn('branch_product_stocks.quantity', '<=', 'product_units.low_stock_threshold')
                ->count(),
        ];

        $suppliers = Supplier::query()->orderBy('business_name')->get(['id', 'owner_name', 'business_name']);
        $branches = Branch::query()
            ->with('supplier:id,owner_name,business_name')
            ->when($supplierId > 0, fn(Builder $query) => $query->where('supplier_id', $supplierId))
            ->orderBy('name')
            ->get(['id', 'supplier_id', 'name']);

        return view('admin.inventory.index', compact('stocks', 'movements', 'stats', 'suppliers', 'branches'));
    }
}
