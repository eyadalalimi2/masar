<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Catalog\ProductUnit;
use App\Models\Distribution\Branch;
use App\Models\Distribution\BranchProductStock;
use App\Models\Distribution\BranchReplenishmentRequest;
use App\Modules\Inventory\Services\InventoryDomainService;
use App\Services\Distribution\BranchInventoryService;
use App\Services\Notifications\WebAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryDomainService $inventoryDomainService,
        private readonly BranchInventoryService $branchInventoryService,
        private readonly WebAlertService $webAlertService,
    ) {}

    public function index(): View
    {
        $branch = $this->currentBranch();
        $syncedInbound = $this->branchInventoryService->syncInboundFromAgent($branch);

        $stocks = $this->branchInventoryService->getInventoryOverview($branch);
        $movements = $this->branchInventoryService->getStockMovements($branch, 40);

        $totals = [
            'total_items' => (int) $stocks->count(),
            'total_quantity' => (float) $stocks->sum('quantity'),
            'low_stock_count' => (int) $stocks->filter(function ($row): bool {
                $threshold = (float) ($row->productUnit?->low_stock_threshold ?? 0);

                return $threshold > 0 && (float) $row->quantity <= $threshold;
            })->count(),
        ];

        return view('branch.inventory.index', compact('branch', 'stocks', 'movements', 'totals', 'syncedInbound'));
    }

    public function updateStock(Request $request): RedirectResponse
    {
        $branch = $this->currentBranch();

        $data = $request->validate([
            'product_unit_id' => ['required', 'integer', 'exists:product_units,id'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $productUnit = ProductUnit::query()->with('product')->findOrFail((int) $data['product_unit_id']);

        try {
            $this->branchInventoryService->updateStock(
                $branch,
                $productUnit,
                (float) $data['quantity'],
                $data['note'] ?? null,
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['inventory' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'تم تحديث مخزون المنتج بنجاح.');
    }

    public function updatePrice(Request $request): RedirectResponse
    {
        $branch = $this->currentBranch();

        $data = $request->validate([
            'product_unit_id' => ['required', 'integer', 'exists:product_units,id'],
            'selling_price' => ['required', 'numeric', 'min:0'],
        ]);

        $productUnit = ProductUnit::query()->with('product')->findOrFail((int) $data['product_unit_id']);

        try {
            $this->branchInventoryService->updateSellingPrice($branch, $productUnit, (float) $data['selling_price']);
        } catch (\Throwable $e) {
            return back()->withErrors(['inventory' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'تم تحديث سعر البيع بنجاح.');
    }

    public function autoReorder(): RedirectResponse
    {
        $branch = $this->currentBranch();

        $lowStocks = $this->inventoryDomainService->stocksQuery()
            ->with(['product:id,name', 'productUnit:id,product_id,low_stock_threshold'])
            ->where('branch_id', $branch->id)
            ->get()
            ->filter(function (BranchProductStock $stock): bool {
                $threshold = (float) ($stock->productUnit?->low_stock_threshold ?? 0);

                return $threshold > 0 && (float) $stock->quantity <= $threshold;
            });

        $created = 0;
        $skipped = 0;

        foreach ($lowStocks as $stock) {
            $threshold = (float) ($stock->productUnit?->low_stock_threshold ?? 0);
            $targetQuantity = $threshold * 2;
            $requestedQuantity = max($threshold, $targetQuantity - (float) $stock->quantity);

            $hasOpenRequest = BranchReplenishmentRequest::query()
                ->where('branch_id', $branch->id)
                ->where('product_unit_id', $stock->product_unit_id)
                ->whereIn('status', [
                    BranchReplenishmentRequest::STATUS_PENDING,
                    BranchReplenishmentRequest::STATUS_APPROVED,
                ])
                ->exists();

            if ($hasOpenRequest || $requestedQuantity <= 0) {
                $skipped++;
                continue;
            }

            $request = BranchReplenishmentRequest::query()->create([
                'branch_id' => $branch->id,
                'supplier_id' => $branch->supplier_id,
                'product_id' => $stock->product_id,
                'product_unit_id' => $stock->product_unit_id,
                'requested_quantity' => $requestedQuantity,
                'status' => BranchReplenishmentRequest::STATUS_PENDING,
                'note' => 'إنشاء تلقائي بسبب انخفاض المخزون إلى ما دون حد التنبيه.',
                'requested_at' => now(),
            ]);

            $agentIds = Agent::query()
                ->where('supplier_id', $branch->supplier_id)
                ->pluck('id');

            foreach ($agentIds as $agentId) {
                $this->webAlertService->create(
                    'agent',
                    (int) $agentId,
                    'طلب توريد تلقائي من الفرع',
                    'تم إنشاء طلب توريد تلقائي للمنتج ' . ($stock->product?->name ?? ('#' . $stock->product_id)),
                    [
                        'type' => 'branch_auto_replenishment_created',
                        'request_id' => $request->id,
                        'branch_id' => $branch->id,
                        'product_id' => $stock->product_id,
                        'product_unit_id' => $stock->product_unit_id,
                    ]
                );
            }

            $created++;
        }

        return back()->with('success', 'تم إنشاء ' . $created . ' طلب توريد تلقائي. تم تجاوز ' . $skipped . ' صنف لوجود طلب مفتوح أو عدم الحاجة.');
    }

    private function currentBranch(): Branch
    {
        $account = Auth::guard('branch')->user();

        if ($account && isset($account->branch_id) && (int) $account->branch_id > 0) {
            return Branch::query()->whereKey((int) $account->branch_id)->firstOrFail();
        }

        $phone = trim((string) ($account->phone ?? ''));
        if ($phone === '') {
            abort(403);
        }

        return Branch::query()->where('phone', $phone)->firstOrFail();
    }
}
