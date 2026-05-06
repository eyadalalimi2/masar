<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Models\Distribution\BranchProductStock;
use App\Models\Distribution\BranchStockMovement;
use App\Models\Workshop\WorkshopPurchaseOrder;
use App\Support\OptionLists;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WorkshopPurchaseOrderController extends Controller
{
    public function index(): View
    {
        $workshopId = Auth::guard('workshop')->id();

        $orders = WorkshopPurchaseOrder::query()
            ->with([
                'supplierBranch:id,name',
                'items.product:id,name',
                'items.productUnit.unit:id,name',
                'latestPayment.paymentMethod',
                'latestPayment.account',
            ])
            ->where('workshop_id', $workshopId)
            ->latest()
            ->get();

        return view('workshop.orders.purchase', compact('orders'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_branch_name' => ['required', 'string', 'max:120'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        WorkshopPurchaseOrder::create([
            'workshop_id' => Auth::guard('workshop')->id(),
            'order_number' => $this->generateOrderNumber(),
            'supplier_branch_name' => $data['supplier_branch_name'],
            'total_amount' => $data['total_amount'],
            'status' => WorkshopPurchaseOrder::STATUS_PENDING,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('status', 'تم إنشاء طلب الشراء بنجاح.');
    }

    public function updateStatus(Request $request, WorkshopPurchaseOrder $order): RedirectResponse
    {
        $this->authorizeOwnership($order);

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', OptionLists::WORKSHOP_PURCHASE_ORDER_STATUSES)],
        ]);

        DB::transaction(function () use ($order, $data): void {
            $order = WorkshopPurchaseOrder::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($order->id);

            $nextStatus = $data['status'];
            $stockCurrentlyDeducted = $order->stock_deducted_at !== null && $order->stock_restored_at === null;

            $shouldDeductStock = in_array($nextStatus, [WorkshopPurchaseOrder::STATUS_APPROVED, WorkshopPurchaseOrder::STATUS_RECEIVED], true)
                && ! $stockCurrentlyDeducted;

            $shouldRestoreStock = $nextStatus === WorkshopPurchaseOrder::STATUS_CANCELLED
                && $stockCurrentlyDeducted;

            if ($shouldDeductStock && $order->items->isNotEmpty()) {
                foreach ($order->items as $item) {
                    if (! $item->branch_product_stock_id) {
                        continue;
                    }

                    $stock = BranchProductStock::query()
                        ->lockForUpdate()
                        ->findOrFail($item->branch_product_stock_id);

                    $requiredQty = (float) $item->quantity;
                    $stockBefore = (float) $stock->quantity;

                    if ($stockBefore < $requiredQty) {
                        abort(422, 'لا يمكن اعتماد الطلب: كمية المخزون في الفرع غير كافية.');
                    }

                    $stockAfter = $stockBefore - $requiredQty;

                    $stock->update([
                        'quantity' => $stockAfter,
                        'is_active' => $stockAfter > 0,
                    ]);

                    BranchStockMovement::query()->create([
                        'branch_id' => $stock->branch_id,
                        'product_id' => $stock->product_id,
                        'product_unit_id' => $stock->product_unit_id,
                        'movement_type' => 'sale',
                        'quantity' => $requiredQty,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'note' => 'خصم مخزون بسبب طلب ورشة رقم ' . $order->order_number,
                    ]);
                }

                $order->stock_deducted_at = now();
                $order->stock_restored_at = null;
            }

            if ($shouldRestoreStock && $order->items->isNotEmpty()) {
                foreach ($order->items as $item) {
                    if (! $item->branch_product_stock_id) {
                        continue;
                    }

                    $stock = BranchProductStock::query()
                        ->lockForUpdate()
                        ->findOrFail($item->branch_product_stock_id);

                    $restoreQty = (float) $item->quantity;
                    $stockBefore = (float) $stock->quantity;
                    $stockAfter = $stockBefore + $restoreQty;

                    $stock->update([
                        'quantity' => $stockAfter,
                        'is_active' => true,
                    ]);

                    BranchStockMovement::query()->create([
                        'branch_id' => $stock->branch_id,
                        'product_id' => $stock->product_id,
                        'product_unit_id' => $stock->product_unit_id,
                        'movement_type' => 'return',
                        'quantity' => $restoreQty,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'note' => 'استرجاع مخزون بعد إلغاء طلب ورشة رقم ' . $order->order_number,
                    ]);
                }

                $order->stock_restored_at = now();
            }

            $order->status = $nextStatus;
            $order->save();
        });

        return back()->with('status', 'تم تحديث حالة طلب الشراء.');
    }

    private function authorizeOwnership(WorkshopPurchaseOrder $order): void
    {
        abort_unless($order->workshop_id === Auth::guard('workshop')->id(), 403);
    }

    private function generateOrderNumber(): string
    {
        do {
            $candidate = 'WPO-' . strtoupper(str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT));
        } while (WorkshopPurchaseOrder::query()->where('order_number', $candidate)->exists());

        return $candidate;
    }
}
