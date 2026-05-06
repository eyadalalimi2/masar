<?php

namespace App\Http\Controllers\Finance\Agent;

use App\Http\Controllers\Controller;
use App\Models\Finance\CustomerAccount;
use App\Models\Finance\Payment;
use App\Http\Requests\Finance\PaymentRequest;
use App\Services\Finance\FinanceService;
use App\Models\Orders\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AgentFinanceController extends Controller
{
    private const TYPE_RETAIL_STORE = 'retail_store';
    private const TYPE_WORKSHOP = 'workshop';

    public function __construct(private readonly FinanceService $financeService) {}

    public function commercialStoresPayments(): View
    {
        return $this->paymentsByType(self::TYPE_RETAIL_STORE, 'المحلات التجارية', 'agent.payments.commercial-stores.index', 'agent.payments.commercial-stores.create');
    }

    public function workshopsPayments(): View
    {
        return $this->paymentsByType(self::TYPE_WORKSHOP, 'الورش', 'agent.payments.workshops.index', 'agent.payments.workshops.create');
    }

    public function createCommercialStoresPayment(): View
    {
        return $this->createPaymentByType(self::TYPE_RETAIL_STORE, 'المحلات التجارية', 'agent.payments.commercial-stores.index', 'agent.payments.commercial-stores.store');
    }

    public function createWorkshopsPayment(): View
    {
        return $this->createPaymentByType(self::TYPE_WORKSHOP, 'الورش', 'agent.payments.workshops.index', 'agent.payments.workshops.store');
    }

    public function storeCommercialStoresPayment(PaymentRequest $request): RedirectResponse
    {
        return $this->storePaymentByType($request, self::TYPE_RETAIL_STORE, 'agent.payments.commercial-stores.index');
    }

    public function storeWorkshopsPayment(PaymentRequest $request): RedirectResponse
    {
        return $this->storePaymentByType($request, self::TYPE_WORKSHOP, 'agent.payments.workshops.index');
    }

    private function paymentsByType(string $customerType, string $sectionLabel, string $sectionRoute, string $createRoute): View
    {
        $supplierId = Auth::user()->supplier->id;

        $payments = Payment::with(['order.distributor'])
            ->whereHas('order', function ($query) use ($supplierId): void {
                $query->where('supplier_id', $supplierId);
            })
            ->whereHas('order.customer', function ($query) use ($customerType) {
                $query->where('type', $customerType);
            })
            ->latest()
            ->paginate(15);

        return view('agent.finance.payments.index', compact('payments', 'sectionLabel', 'sectionRoute', 'createRoute'));
    }

    private function createPaymentByType(string $customerType, string $sectionLabel, string $indexRoute, string $submitRoute): View
    {
        $supplierId = Auth::user()->supplier->id;

        $orders = Order::with('payments')
            ->where('supplier_id', $supplierId)
            ->whereHas('customer', function ($query) use ($customerType) {
                $query->where('type', $customerType);
            })
            ->latest()
            ->get();

        return view('agent.finance.payments.create', compact('orders', 'sectionLabel', 'indexRoute', 'submitRoute'));
    }

    private function storePaymentByType(PaymentRequest $request, string $customerType, string $redirectRoute): RedirectResponse
    {
        $supplierId = Auth::user()->supplier->id;
        $data = $request->validated();

        $order = Order::where('supplier_id', $supplierId)
            ->whereHas('customer', function ($query) use ($customerType) {
                $query->where('type', $customerType);
            })
            ->findOrFail($data['order_id']);

        $this->financeService->createPayment($order, [
            'amount' => $data['amount'] ?? 0,
            'payment_type' => $data['payment_type'],
            'notes' => $data['notes'] ?? null,
            'distributor_id' => null,
        ]);

        return redirect()->route($redirectRoute)->with('success', 'تم تسجيل الدفع بنجاح.');
    }

    public function commercialStoresAccounts(): View
    {
        return $this->accountsByType(self::TYPE_RETAIL_STORE, 'المحلات التجارية', 'agent.accounts.commercial-stores.index');
    }

    public function workshopsAccounts(): View
    {
        return $this->accountsByType(self::TYPE_WORKSHOP, 'الورش', 'agent.accounts.workshops.index');
    }

    private function accountsByType(string $customerType, string $sectionLabel, string $sectionRoute): View
    {
        $supplierId = Auth::user()->supplier->id;

        $customerIds = Order::where('supplier_id', $supplierId)
            ->whereNotNull('customer_id')
            ->distinct()
            ->pluck('customer_id');

        $accounts = CustomerAccount::with(['transactions' => function ($query) {
            $query->latest()->limit(10);
        }, 'customer'])
            ->whereIn('customer_id', $customerIds)
            ->whereHas('customer', function ($query) use ($customerType) {
                $query->where('type', $customerType);
            })
            ->latest()
            ->paginate(15);

        return view('agent.finance.accounts.index', compact('accounts', 'sectionLabel', 'sectionRoute'));
    }
}
