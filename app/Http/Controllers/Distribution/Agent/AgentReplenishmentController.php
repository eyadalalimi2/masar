<?php

namespace App\Http\Controllers\Distribution\Agent;

use App\Http\Controllers\Controller;
use App\Models\Catalog\ProductUnit;
use App\Models\Distribution\Branch;
use App\Models\Distribution\BranchReplenishmentRequest;
use App\Services\Distribution\InventoryService;
use App\Services\Notifications\WebAlertService;
use App\Traits\Notifications\SendNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AgentReplenishmentController extends Controller
{
    use SendNotification;

    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly WebAlertService $webAlertService,
    ) {}

    public function index(Request $request): View
    {
        $supplierId = (int) (Auth::guard('agent')->user()->supplier_id ?? 0);
        $status = (string) $request->query('status', '');

        $requests = BranchReplenishmentRequest::query()
            ->with(['branch:id,name,phone', 'product:id,name,model', 'productUnit:id,unit_id', 'productUnit.unit:id,name'])
            ->where(function ($query) use ($supplierId) {
                $query
                    ->where('supplier_id', $supplierId)
                    ->orWhereHas('branch', fn($branchQuery) => $branchQuery->where('supplier_id', $supplierId));
            })
            ->when($status !== '', fn($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('agent.replenishment.index', compact('requests'));
    }

    public function approve(BranchReplenishmentRequest $replenishment): RedirectResponse
    {
        $supplierId = (int) (Auth::guard('agent')->user()->supplier_id ?? 0);
        abort_unless(
            (int) $replenishment->supplier_id === $supplierId
                || (int) ($replenishment->branch?->supplier_id ?? 0) === $supplierId,
            404
        );

        if (! in_array($replenishment->status, ['pending', 'rejected'], true)) {
            return back()->withErrors(['replenishment' => 'لا يمكن اعتماد هذا الطلب في حالته الحالية.']);
        }

        $replenishment->update([
            'status' => 'approved',
            'resolved_at' => now(),
        ]);

        $this->notifyBranch($replenishment, 'تمت الموافقة على طلب التوريد', 'تمت الموافقة على طلب التوريد #' . $replenishment->id);

        return back()->with('success', 'تم اعتماد طلب التوريد بنجاح.');
    }

    public function reject(Request $request, BranchReplenishmentRequest $replenishment): RedirectResponse
    {
        $supplierId = (int) (Auth::guard('agent')->user()->supplier_id ?? 0);
        abort_unless(
            (int) $replenishment->supplier_id === $supplierId
                || (int) ($replenishment->branch?->supplier_id ?? 0) === $supplierId,
            404
        );

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! in_array($replenishment->status, ['pending', 'approved'], true)) {
            return back()->withErrors(['replenishment' => 'لا يمكن رفض هذا الطلب في حالته الحالية.']);
        }

        $replenishment->update([
            'status' => 'rejected',
            'note' => $data['note'] ?? $replenishment->note,
            'resolved_at' => now(),
        ]);

        $this->notifyBranch($replenishment, 'تم رفض طلب التوريد', 'تم رفض طلب التوريد #' . $replenishment->id);

        return back()->with('success', 'تم رفض طلب التوريد.');
    }

    public function fulfill(Request $request, BranchReplenishmentRequest $replenishment): RedirectResponse
    {
        $supplierId = (int) (Auth::guard('agent')->user()->supplier_id ?? 0);
        $agentId = (int) Auth::guard('agent')->id();
        abort_unless(
            (int) $replenishment->supplier_id === $supplierId
                || (int) ($replenishment->branch?->supplier_id ?? 0) === $supplierId,
            404
        );

        $data = $request->validate([
            'fulfilled_quantity' => ['nullable', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! in_array($replenishment->status, ['approved', 'pending'], true)) {
            return back()->withErrors(['replenishment' => 'لا يمكن تنفيذ هذا الطلب في حالته الحالية.']);
        }

        $branch = Branch::query()->where('supplier_id', $supplierId)->findOrFail((int) $replenishment->branch_id);
        $productUnit = ProductUnit::query()->with('product')->findOrFail((int) $replenishment->product_unit_id);
        $quantity = isset($data['fulfilled_quantity']) ? (float) $data['fulfilled_quantity'] : (float) $replenishment->requested_quantity;

        try {
            $this->inventoryService->distributeToBranch(
                $supplierId,
                $agentId,
                $productUnit,
                $branch,
                $quantity,
                'تنفيذ طلب توريد #' . $replenishment->id . (($data['note'] ?? '') !== '' ? ' - ' . $data['note'] : '')
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['replenishment' => $e->getMessage()]);
        }

        $replenishment->update([
            'status' => 'fulfilled',
            'resolved_at' => now(),
            'note' => $data['note'] ?? $replenishment->note,
        ]);

        $this->notifyBranch($replenishment, 'تم تزويد الفرع بالمخزون', 'تم تنفيذ طلب التوريد #' . $replenishment->id . ' بكمية ' . number_format($quantity, 3));

        return back()->with('success', 'تم تنفيذ طلب التوريد وتزويد الفرع بالمخزون.');
    }

    private function notifyBranch(BranchReplenishmentRequest $replenishment, string $title, string $body): void
    {
        $replenishment->loadMissing('branch.account');

        $branchAccountId = (int) ($replenishment->branch?->account?->id ?? 0);

        if ($branchAccountId > 0) {
            $this->webAlertService->create(
                'branch_account',
                $branchAccountId,
                $title,
                $body,
                [
                    'type' => 'branch_replenishment_update',
                    'request_id' => $replenishment->id,
                    'status' => $replenishment->status,
                    'branch_id' => $replenishment->branch_id,
                ]
            );
        }

        $this->sendToUser($replenishment->branch?->account, $title, $body, [
            'type' => 'branch_replenishment_update',
            'request_id' => $replenishment->id,
            'status' => $replenishment->status,
            'branch_id' => $replenishment->branch_id,
        ]);
    }
}
