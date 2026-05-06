<?php

namespace App\Http\Controllers\Finance\Branch;

use App\Http\Controllers\Controller;
use App\Models\Distribution\Branch;
use App\Models\Finance\Payment;
use App\Http\Requests\Finance\PaymentRequest;
use App\Services\Finance\FinanceService;
use App\Models\Orders\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BranchFinanceController extends Controller
{
    public function __construct(private readonly FinanceService $financeService) {}

    public function payments(): View
    {
        $branch = $this->currentBranch();

        $payments = Payment::with(['order.distributor'])
            ->whereHas('order', function ($query) use ($branch) {
                $query->where('branch_id', $branch->id);
            })
            ->latest()
            ->paginate(15);

        return view('branch.finance.payments.index', compact('payments', 'branch'));
    }

    public function createPayment(): View
    {
        $branch = $this->currentBranch();

        $orders = Order::with('payments')
            ->where('branch_id', $branch->id)
            ->whereIn('status', [
                Order::STATUS_APPROVED,
                Order::STATUS_OUT_FOR_DELIVERY,
                Order::STATUS_DELIVERED,
            ])
            ->latest()
            ->get();

        return view('branch.finance.payments.create', compact('orders', 'branch'));
    }

    public function storePayment(PaymentRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();
        $data = $request->validated();

        $order = Order::where('branch_id', $branch->id)->findOrFail($data['order_id']);

        $this->financeService->createPayment($order, [
            'amount' => $data['amount'] ?? 0,
            'payment_type' => $data['payment_type'],
            'notes' => $data['notes'] ?? null,
            'distributor_id' => $order->distributor_id,
        ]);

        return redirect()->route('branch.payments.index')->with('success', 'تم تسجيل عملية الدفع بنجاح.');
    }

    private function currentBranch(): Branch
    {
        return Branch::query()
            ->where('phone', Auth::user()->phone)
            ->where('status', 'active')
            ->firstOrFail();
    }
}
