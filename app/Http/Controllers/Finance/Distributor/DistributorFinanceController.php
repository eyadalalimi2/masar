<?php

namespace App\Http\Controllers\Finance\Distributor;

use App\Http\Controllers\Controller;
use App\Models\Finance\Payment;
use App\Http\Requests\Finance\PaymentRequest;
use App\Services\Finance\FinanceService;
use App\Models\Orders\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DistributorFinanceController extends Controller
{
    public function __construct(private readonly FinanceService $financeService) {}

    public function payments(): View
    {
        $distributorId = Auth::user()->distributor->id;

        $payments = Payment::with(['order.supplier'])
            ->whereHas('order', function ($query) use ($distributorId): void {
                $query->where('distributor_id', $distributorId);
            })
            ->latest()
            ->paginate(15);

        return view('distributor.finance.payments.index', compact('payments'));
    }

    public function createPayment(): View
    {
        $distributorId = Auth::user()->distributor->id;

        $orders = Order::with('payments')->where('distributor_id', $distributorId)
            ->whereIn('status', [
                Order::STATUS_OUT_FOR_DELIVERY,
                Order::STATUS_DELIVERED,
            ])
            ->latest()
            ->get();

        return view('distributor.finance.payments.create', compact('orders'));
    }

    public function storePayment(PaymentRequest $request): RedirectResponse
    {
        $distributorId = Auth::user()->distributor->id;
        $data = $request->validated();

        $order = Order::where('distributor_id', $distributorId)->findOrFail($data['order_id']);

        $this->financeService->createPayment($order, [
            'amount' => $data['amount'] ?? 0,
            'payment_type' => $data['payment_type'],
            'notes' => $data['notes'] ?? null,
            'distributor_id' => $distributorId,
        ]);

        return redirect()->route('distributor.payments.index')->with('success', 'تم تسجيل التحصيل بنجاح.');
    }
}
