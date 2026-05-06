<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use App\Models\Orders\Order as CustomerOrder;
use App\Services\Pos\PosContextService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly PosContextService $posContext) {}

    public function index(Request $request): View
    {
        $pos = $this->posContext->currentPos();
        $customer = $this->posContext->resolveOrCreateCustomer($pos);
        $status = (string) $request->query('status', '');

        $orders = Order::query()
            ->with(['branch:id,name', 'supplier:id,business_name,owner_name', 'items.product:id,name', 'latestPayment.paymentMethod', 'latestPayment.account'])
            ->where('buyer_type', CustomerOrder::BUYER_TYPE_CUSTOMER)
            ->where('buyer_id', $customer->id)
            ->when($status !== '', fn($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pos.orders.index', compact('pos', 'orders'));
    }

    public function show(Order $order): View
    {
        $pos = $this->posContext->currentPos();
        $customer = $this->posContext->resolveOrCreateCustomer($pos);

        abort_unless((int) $order->buyer_id === (int) $customer->id && $order->buyer_type === CustomerOrder::BUYER_TYPE_CUSTOMER, 404);

        $order->load([
            'branch:id,name,address,phone',
            'supplier:id,business_name,owner_name',
            'distributor:id,name,phone',
            'items.product:id,name',
            'items.productUnit.unit:id,name',
            'latestPayment.paymentMethod',
            'latestPayment.account',
        ]);

        return view('pos.orders.show', compact('pos', 'order'));
    }
}
