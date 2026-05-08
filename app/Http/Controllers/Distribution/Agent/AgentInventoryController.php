<?php

namespace App\Http\Controllers\Distribution\Agent;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Category;
use App\Models\Catalog\InventoryMovement;
use App\Models\Catalog\ProductUnit;
use App\Models\Catalog\Unit;
use App\Models\Distribution\Branch;
use App\Services\Distribution\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class AgentInventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function index(Request $request): View
    {
        $supplierId = $this->supplierId();

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'category_id' => (int) $request->query('category_id', 0),
            'unit_id' => (int) $request->query('unit_id', 0),
            'stock_status' => trim((string) $request->query('stock_status', '')),
            'stock_from' => $request->query('stock_from'),
            'stock_to' => $request->query('stock_to'),
        ];

        $stockFrom = is_numeric($filters['stock_from']) ? (float) $filters['stock_from'] : null;
        $stockTo = is_numeric($filters['stock_to']) ? (float) $filters['stock_to'] : null;
        if ($stockFrom !== null && $stockTo !== null && $stockFrom > $stockTo) {
            [$stockFrom, $stockTo] = [$stockTo, $stockFrom];
        }
        $filters['stock_from'] = $stockFrom;
        $filters['stock_to'] = $stockTo;

        $inventory = $this->inventoryService->getSupplierInventory($supplierId, 20, $filters);
        $inventoryRows = collect($inventory->items());

        $categories = Category::query()
            ->whereHas('products', fn($query) => $query->where('supplier_id', $supplierId))
            ->orderBy('name')
            ->get(['id', 'name']);
        $units = Unit::query()
            ->whereHas('productUnits.product', fn($query) => $query->where('supplier_id', $supplierId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $totals = [
            'units_count' => (int) $inventory->total(),
            'total_stock' => (float) $inventoryRows->sum('stock_quantity'),
            'low_stock_count' => (int) $inventoryRows->filter(function ($row) {
                return (float) $row->low_stock_threshold > 0
                    && (float) $row->stock_quantity <= (float) $row->low_stock_threshold;
            })->count(),
        ];

        return view('agent.inventory.index', compact('totals', 'inventory', 'categories', 'units', 'filters'));
    }

    public function stockManagement(Request $request): View
    {
        $supplierId = $this->supplierId();
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'category_id' => (int) $request->query('category_id', 0),
            'unit_id' => (int) $request->query('unit_id', 0),
            'stock_status' => trim((string) $request->query('stock_status', '')),
            'stock_from' => $request->query('stock_from'),
            'stock_to' => $request->query('stock_to'),
        ];

        $stockFrom = is_numeric($filters['stock_from']) ? (float) $filters['stock_from'] : null;
        $stockTo = is_numeric($filters['stock_to']) ? (float) $filters['stock_to'] : null;
        if ($stockFrom !== null && $stockTo !== null && $stockFrom > $stockTo) {
            [$stockFrom, $stockTo] = [$stockTo, $stockFrom];
        }
        $filters['stock_from'] = $stockFrom;
        $filters['stock_to'] = $stockTo;

        $inventory = $this->inventoryService->getSupplierInventory($supplierId, 20, $filters);
        $categories = Category::query()
            ->whereHas('products', fn($query) => $query->where('supplier_id', $supplierId))
            ->orderBy('name')
            ->get(['id', 'name']);
        $units = Unit::query()
            ->whereHas('productUnits.product', fn($query) => $query->where('supplier_id', $supplierId))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('agent.inventory.stock-management', compact('inventory', 'categories', 'units', 'filters'));
    }

    public function distributionPage(): View
    {
        $supplierId = $this->supplierId();
        $branches = $this->activeBranches($supplierId);

        return view('agent.inventory.distribution', compact('branches'));
    }

    public function distributionModelLookup(Request $request): JsonResponse
    {
        $supplierId = $this->supplierId();
        $model = trim((string) $request->query('model', ''));

        if ($model === '') {
            return response()->json([
                'message' => 'رقم الموديل مطلوب.',
            ], 422);
        }

        $productUnit = ProductUnit::query()
            ->with([
                'product:id,supplier_id,category_id,name,model,image',
                'product.category:id,name',
                'unit:id,name',
            ])
            ->whereHas('product', function ($query) use ($supplierId, $model) {
                $query->where('supplier_id', $supplierId)
                    ->where(function ($inner) use ($model) {
                        $inner->where('model', $model)
                            ->orWhere('model', 'like', $model . '%');
                    });
            })
            ->orderByDesc('stock_quantity')
            ->first();

        if (! $productUnit) {
            return response()->json([
                'message' => 'لم يتم العثور على منتج بهذا الموديل.',
            ], 404);
        }

        return response()->json([
            'id' => (int) $productUnit->id,
            'model' => (string) ($productUnit->product?->model ?? ''),
            'image_url' => $productUnit->product?->image ? asset('storage/' . $productUnit->product->image) : null,
            'product_name' => (string) ($productUnit->product?->name ?? '-'),
            'category_name' => (string) ($productUnit->product?->category?->name ?? '-'),
            'unit_name' => (string) ($productUnit->unit?->name ?? '-'),
            'stock_quantity' => (float) ($productUnit->stock_quantity ?? 0),
        ]);
    }

    public function movements(): View
    {
        $supplierId = $this->supplierId();
        $movements = $this->inventoryService->getRecentMovements($supplierId, 100);

        return view('agent.inventory.movements', compact('movements'));
    }

    public function movementPdf(InventoryMovement $movement)
    {
        $supplierId = $this->supplierId();
        abort_unless((int) $movement->supplier_id === $supplierId, 404);

        $movement->load([
            'supplier:id,name,phone,address',
            'branch:id,name,phone,address',
            'product:id,name,model',
            'productUnit:id,unit_id',
            'productUnit.unit:id,name',
            'agent:id,name,phone',
        ]);

        $html = view('agent.inventory.movement-pdf', [
            'movement' => $movement,
            'printedAt' => now()->format('Y-m-d H:i'),
        ])->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 40,
            'margin_bottom' => 22,
            'margin_left' => 12,
            'margin_right' => 12,
            'tempDir' => storage_path('app/mpdf-temp'),
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        $filename = 'inventory_movement_' . $movement->id . '.pdf';

        return response(
            $mpdf->Output('', Destination::STRING_RETURN),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]
        );
    }

    public function stockReportPdf(Request $request)
    {
        $supplierId = $this->supplierId();
        $supplier = Auth::guard('agent')->user()->supplier;

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'category_id' => (int) $request->query('category_id', 0),
            'unit_id' => (int) $request->query('unit_id', 0),
            'stock_status' => trim((string) $request->query('stock_status', '')),
            'stock_from' => $request->query('stock_from'),
            'stock_to' => $request->query('stock_to'),
        ];

        $stockFrom = is_numeric($filters['stock_from']) ? (float) $filters['stock_from'] : null;
        $stockTo = is_numeric($filters['stock_to']) ? (float) $filters['stock_to'] : null;
        if ($stockFrom !== null && $stockTo !== null && $stockFrom > $stockTo) {
            [$stockFrom, $stockTo] = [$stockTo, $stockFrom];
        }
        $filters['stock_from'] = $stockFrom;
        $filters['stock_to'] = $stockTo;

        $inventory = $this->inventoryService->getSupplierInventory($supplierId, 5000, $filters);

        $html = view('agent.inventory.stock-report-pdf', [
            'rows' => collect($inventory->items()),
            'printedAt' => now()->format('Y-m-d H:i'),
            'printedBy' => (string) (Auth::guard('agent')->user()->name ?? ''),
            'businessName' => (string) ($supplier->business_name ?? $supplier->name ?? ''),
            'businessAddressAr' => (string) ($supplier->address ?? ''),
            'businessAddressEn' => (string) ($supplier->address ?? ''),
            'businessPhone' => (string) ($supplier->phone ?? ''),
        ])->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 52,
            'margin_bottom' => 22,
            'margin_left' => 12,
            'margin_right' => 12,
            'tempDir' => storage_path('app/mpdf-temp'),
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        $filename = 'inventory_report_' . now()->format('Ymd_His') . '.pdf';

        return response(
            $mpdf->Output('', Destination::STRING_RETURN),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]
        );
    }

    public function addStock(Request $request): RedirectResponse
    {
        $supplierId = $this->supplierId();
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
        $supplierId = $this->supplierId();
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
        $supplierId = $this->supplierId();
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

    private function supplierId(): int
    {
        return (int) Auth::guard('agent')->user()->supplier->id;
    }

    private function activeBranches(int $supplierId)
    {
        return Branch::query()
            ->where('supplier_id', $supplierId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
