<?php

namespace App\Http\Controllers\Distribution\Agent;

use App\Http\Controllers\Controller;
use App\Models\Catalog\ProductUnit;
use App\Models\Distribution\Branch;
use App\Services\Distribution\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AgentInventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function index(Request $request): View
    {
        $supplierId = (int) Auth::guard('agent')->user()->supplier->id;

        $inventory = $this->inventoryService->getSupplierInventory($supplierId);
        $movements = $this->inventoryService->getRecentMovements($supplierId);
        $branches = Branch::query()
            ->where('supplier_id', $supplierId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);
        $inventoryRows = collect($inventory->items());

        $totals = [
            'units_count' => (int) $inventory->total(),
            'total_stock' => (float) $inventoryRows->sum('stock_quantity'),
            'low_stock_count' => (int) $inventoryRows->filter(function ($row) {
                return (float) $row->low_stock_threshold > 0
                    && (float) $row->stock_quantity <= (float) $row->low_stock_threshold;
            })->count(),
        ];

        return view('agent.inventory.index', compact('inventory', 'movements', 'branches', 'totals'));
    }

    public function addStock(Request $request): RedirectResponse
    {
        $supplierId = (int) Auth::guard('agent')->user()->supplier->id;
        $agentId = (int) Auth::guard('agent')->id();

        $data = $request->validate([
            'product_unit_id' => ['required', 'integer', 'exists:product_units,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $productUnit = ProductUnit::query()->with('product')->findOrFail((int) $data['product_unit_id']);

        try {
            $this->inventoryService->addStock(
                $supplierId,
                $agentId,
                $productUnit,
                (float) $data['quantity'],
                isset($data['low_stock_threshold']) ? (float) $data['low_stock_threshold'] : null,
                $data['note'] ?? null,
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['inventory' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', 'تمت إضافة المخزون بنجاح.');
    }

    public function adjustStock(Request $request): RedirectResponse
    {
        $supplierId = (int) Auth::guard('agent')->user()->supplier->id;
        $agentId = (int) Auth::guard('agent')->id();

        $data = $request->validate([
            'product_unit_id' => ['required', 'integer', 'exists:product_units,id'],
            'new_quantity' => ['required', 'numeric', 'min:0'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $productUnit = ProductUnit::query()->with('product')->findOrFail((int) $data['product_unit_id']);

        try {
            $this->inventoryService->adjustStock(
                $supplierId,
                $agentId,
                $productUnit,
                (float) $data['new_quantity'],
                isset($data['low_stock_threshold']) ? (float) $data['low_stock_threshold'] : null,
                $data['note'] ?? null,
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['inventory' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', 'تم ضبط المخزون بنجاح.');
    }

    public function distribute(Request $request): RedirectResponse
    {
        $supplierId = (int) Auth::guard('agent')->user()->supplier->id;
        $agentId = (int) Auth::guard('agent')->id();

        $data = $request->validate([
            'product_unit_id' => ['required', 'integer', 'exists:product_units,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $productUnit = ProductUnit::query()->with('product')->findOrFail((int) $data['product_unit_id']);
        $branch = Branch::query()->findOrFail((int) $data['branch_id']);

        try {
            $this->inventoryService->distributeToBranch(
                $supplierId,
                $agentId,
                $productUnit,
                $branch,
                (float) $data['quantity'],
                $data['note'] ?? null,
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['inventory' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', 'تم صرف الكمية للفرع بنجاح.');
    }
}
