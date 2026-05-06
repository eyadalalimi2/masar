<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductUnit;
use App\Models\Distribution\Branch;
use App\Models\Distribution\BranchReplenishmentRequest;
use App\Services\Notifications\WebAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReplenishmentController extends Controller
{
    public function __construct(private readonly WebAlertService $webAlertService) {}

    public function index(): View
    {
        $branch = $this->currentBranch();

        $requests = BranchReplenishmentRequest::query()
            ->with(['product:id,name,model', 'productUnit:id,unit_id', 'productUnit.unit:id,name'])
            ->where('branch_id', $branch->id)
            ->latest()
            ->paginate(15);

        $products = Product::query()
            ->with(['productUnits.unit:id,name'])
            ->where('supplier_id', $branch->supplier_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'model']);

        return view('branch.replenishment.index', compact('branch', 'requests', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $branch = $this->currentBranch();

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_unit_id' => ['required', 'integer', 'exists:product_units,id'],
            'items.*.requested_quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.note' => ['nullable', 'string', 'max:1000'],
        ]);

        $createdRequestIds = [];

        DB::transaction(function () use ($data, $branch, &$createdRequestIds): void {
            foreach ((array) $data['items'] as $item) {
                $product = Product::query()
                    ->where('supplier_id', $branch->supplier_id)
                    ->findOrFail((int) $item['product_id']);

                ProductUnit::query()
                    ->where('product_id', $product->id)
                    ->findOrFail((int) $item['product_unit_id']);

                $createdRequest = BranchReplenishmentRequest::query()->create([
                    'branch_id' => $branch->id,
                    'supplier_id' => $branch->supplier_id,
                    'product_id' => $product->id,
                    'product_unit_id' => (int) $item['product_unit_id'],
                    'requested_quantity' => (float) $item['requested_quantity'],
                    'status' => 'pending',
                    'note' => $item['note'] ?? null,
                    'requested_at' => now(),
                ]);

                $createdRequestIds[] = (int) $createdRequest->id;
            }
        });

        $createdCount = count($createdRequestIds);

        $agentIds = Agent::query()
            ->where('supplier_id', $branch->supplier_id)
            ->pluck('id');

        foreach ($agentIds as $agentId) {
            $this->webAlertService->create(
                'agent',
                (int) $agentId,
                'طلب توريد جديد من الفرع',
                'قام الفرع ' . $branch->name . ' بإنشاء ' . $createdCount . ' طلب/طلبات توريد جديدة.',
                [
                    'type' => 'branch_replenishment_created',
                    'request_ids' => $createdRequestIds,
                    'branch_id' => $branch->id,
                    'supplier_id' => $branch->supplier_id,
                ]
            );
        }

        return back()->with('success', 'تم إرسال ' . $createdCount . ' طلب/طلبات توريد إلى الوكيل بنجاح.');
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
