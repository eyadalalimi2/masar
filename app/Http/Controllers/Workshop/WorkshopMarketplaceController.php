<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Models\Distribution\Branch;
use App\Models\Distribution\BranchProductStock;
use App\Models\Finance\Account;
use App\Models\Finance\PortalPaymentMethod;
use App\Models\Workshop\WorkshopOrderPayment;
use App\Models\Workshop\WorkshopPurchaseOrder;
use App\Models\Workshop\WorkshopPurchaseOrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class WorkshopMarketplaceController extends Controller
{
    private const CART_SESSION_KEY = 'workshop_marketplace_cart';

    public function index(Request $request): View
    {
        $workshopId = Auth::guard('workshop')->id();

        $search = trim((string) $request->query('search', ''));
        $branchId = (int) $request->query('branch_id', 0);
        $sort = (string) $request->query('sort', 'price_asc');

        $stocks = BranchProductStock::query()
            ->with([
                'branch:id,name,address,gps_location,supplier_id',
                'product:id,name,model',
                'productUnit:id,unit_id,product_id',
                'productUnit.unit:id,name',
            ])
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->when($branchId > 0, fn($query) => $query->where('branch_id', $branchId))
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('product', function ($productQuery) use ($search) {
                    $productQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%");
                });
            })
            ->when($sort === 'price_desc', fn($query) => $query->orderByDesc('selling_price'))
            ->when($sort === 'qty_desc', fn($query) => $query->orderByDesc('quantity'))
            ->when($sort === 'price_asc', fn($query) => $query->orderBy('selling_price'))
            ->paginate(18)
            ->withQueryString();

        $branches = Branch::query()
            ->where('status', Account::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name']);

        $marketStats = [
            'available_products' => (int) BranchProductStock::query()
                ->where('is_active', true)
                ->where('quantity', '>', 0)
                ->count(),
            'active_branches' => (int) $branches->count(),
            'pending_purchase_orders' => (int) WorkshopPurchaseOrder::query()
                ->where('workshop_id', $workshopId)
                ->whereIn('status', [
                    WorkshopPurchaseOrder::STATUS_PENDING,
                    WorkshopPurchaseOrder::STATUS_APPROVED,
                    WorkshopPurchaseOrder::STATUS_IN_TRANSIT,
                ])
                ->count(),
        ];

        $cartItems = collect($this->readCart($request, $workshopId));
        $cartSummary = [
            'items_count' => (int) $cartItems->count(),
            'total_quantity' => (float) $cartItems->sum('quantity'),
            'total_amount' => (float) $cartItems->sum('line_total'),
        ];

        $checkoutPaymentMethods = $this->resolveEnabledPaymentMethods('workshop', (int) $workshopId);

        return view('workshop.marketplace.index', compact('stocks', 'branches', 'marketStats', 'cartItems', 'cartSummary', 'checkoutPaymentMethods'));
    }

    public function addToCart(Request $request): RedirectResponse
    {
        $workshopId = Auth::guard('workshop')->id();

        $data = $request->validate([
            'stock_id' => ['required', 'integer', 'exists:branch_product_stocks,id'],
            'quantity' => ['required', 'numeric', 'min:1'],
        ]);

        $stock = BranchProductStock::query()
            ->with(['product:id,name,model', 'productUnit.unit:id,name', 'branch:id,name'])
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->findOrFail((int) $data['stock_id']);

        $requestedQty = (float) $data['quantity'];
        if ($requestedQty > (float) $stock->quantity) {
            return back()->withErrors(['cart' => 'الكمية المطلوبة أكبر من المتوفر في المخزون.']);
        }

        $cart = $this->readCart($request, $workshopId);
        $key = (string) $stock->id;

        if (isset($cart[$key])) {
            $nextQty = (float) $cart[$key]['quantity'] + $requestedQty;
            if ($nextQty > (float) $stock->quantity) {
                return back()->withErrors(['cart' => 'لا يمكن تجاوز الكمية المتاحة لهذا المنتج.']);
            }

            $cart[$key]['quantity'] = $nextQty;
            $cart[$key]['line_total'] = round($nextQty * (float) $cart[$key]['unit_price'], 2);
        } else {
            $unitPrice = (float) ($stock->selling_price ?? 0);
            $cart[$key] = [
                'stock_id' => (int) $stock->id,
                'branch_id' => (int) $stock->branch_id,
                'branch_name' => (string) ($stock->branch?->name ?? 'فرع'),
                'product_id' => (int) $stock->product_id,
                'product_unit_id' => (int) $stock->product_unit_id,
                'product_name' => (string) ($stock->product?->name ?? 'منتج'),
                'unit_name' => (string) ($stock->productUnit?->unit?->name ?? '-'),
                'quantity' => $requestedQty,
                'unit_price' => $unitPrice,
                'line_total' => round($requestedQty * $unitPrice, 2),
            ];
        }

        $this->writeCart($request, $workshopId, $cart);

        return back()->with('status', 'تمت إضافة المنتج إلى سلة الشراء.');
    }

    public function removeFromCart(Request $request, BranchProductStock $stock): RedirectResponse
    {
        $workshopId = Auth::guard('workshop')->id();
        $cart = $this->readCart($request, $workshopId);

        unset($cart[(string) $stock->id]);

        $this->writeCart($request, $workshopId, $cart);

        return back()->with('status', 'تم حذف الصنف من السلة.');
    }

    public function clearCart(Request $request): RedirectResponse
    {
        $workshopId = Auth::guard('workshop')->id();
        $this->writeCart($request, $workshopId, []);

        return back()->with('status', 'تم تفريغ سلة الشراء.');
    }

    public function checkoutCart(Request $request): RedirectResponse
    {
        $workshopId = Auth::guard('workshop')->id();
        $checkoutPaymentMethods = $this->resolveEnabledPaymentMethods('workshop', (int) $workshopId);

        $rules = [];
        if ($checkoutPaymentMethods->isNotEmpty()) {
            $rules['payment_method_id'] = ['required', 'integer'];
        }
        $validated = $request->validate($rules);

        $selectedMethod = null;
        if ($checkoutPaymentMethods->isNotEmpty()) {
            $selectedMethod = $checkoutPaymentMethods->firstWhere('payment_method_id', (int) ($validated['payment_method_id'] ?? 0));

            if ($selectedMethod === null) {
                return back()->withErrors(['payment_method_id' => 'يرجى اختيار طريقة دفع صالحة.'])->withInput();
            }
        }

        $cart = $this->readCart($request, $workshopId);

        if (empty($cart)) {
            return back()->withErrors(['cart' => 'سلة الشراء فارغة.']);
        }

        $lineItems = collect($cart);
        $stockIds = $lineItems->pluck('stock_id')->map(fn($id) => (int) $id)->all();

        DB::transaction(function () use ($workshopId, $lineItems, $stockIds, $selectedMethod): void {
            $stocks = BranchProductStock::query()
                ->with('branch:id,name,status')
                ->whereIn('id', $stockIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lineItems->each(function (array $item) use ($stocks): void {
                $stock = $stocks->get((int) $item['stock_id']);

                if (! $stock || ! $stock->is_active || (float) $stock->quantity < (float) $item['quantity']) {
                    abort(422, 'بعض عناصر السلة لم تعد متاحة بنفس الكمية.');
                }
            });

            $lineItems
                ->groupBy(fn(array $item) => (int) $item['branch_id'])
                ->each(function ($items, int $branchId) use ($workshopId, $stocks, $selectedMethod): void {
                    $branch = $stocks
                        ->first(fn(BranchProductStock $stock) => (int) $stock->branch_id === (int) $branchId)
                        ?->branch;

                    if (! $branch || $branch->status !== Account::STATUS_ACTIVE) {
                        abort(422, 'أحد الفروع في السلة غير نشط حاليا.');
                    }

                    $totalAmount = (float) collect($items)->sum('line_total');

                    $order = WorkshopPurchaseOrder::query()->create([
                        'workshop_id' => $workshopId,
                        'supplier_branch_id' => (int) $branch->id,
                        'order_number' => $this->generateOrderNumber(),
                        'supplier_branch_name' => (string) $branch->name,
                        'total_amount' => $totalAmount,
                        'status' => WorkshopPurchaseOrder::STATUS_PENDING,
                        'payment_method_id' => $selectedMethod?->payment_method_id,
                    ]);

                    $this->attachOrderCheckoutPayment($order, $selectedMethod, $totalAmount);

                    collect($items)->each(function (array $item) use ($order): void {
                        WorkshopPurchaseOrderItem::query()->create([
                            'purchase_order_id' => $order->id,
                            'branch_product_stock_id' => (int) $item['stock_id'],
                            'product_id' => (int) $item['product_id'],
                            'product_unit_id' => (int) $item['product_unit_id'],
                            'quantity' => (float) $item['quantity'],
                            'unit_price' => (float) $item['unit_price'],
                            'line_total' => (float) $item['line_total'],
                        ]);
                    });
                });
        });

        $this->writeCart($request, $workshopId, []);

        return back()->with('status', 'تم إنشاء طلبات الشراء من السلة بنجاح.');
    }

    public function storeOrder(Request $request): RedirectResponse
    {
        $workshopId = Auth::guard('workshop')->id();
        $checkoutPaymentMethods = $this->resolveEnabledPaymentMethods('workshop', (int) $workshopId);

        $rules = [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'product_unit_id' => ['required', 'integer', 'exists:product_units,id'],
            'quantity' => ['required', 'numeric', 'min:1'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
        if ($checkoutPaymentMethods->isNotEmpty()) {
            $rules['payment_method_id'] = ['required', 'integer'];
        }

        $data = $request->validate($rules);

        $selectedMethod = null;
        if ($checkoutPaymentMethods->isNotEmpty()) {
            $selectedMethod = $checkoutPaymentMethods->firstWhere('payment_method_id', (int) ($data['payment_method_id'] ?? 0));

            if ($selectedMethod === null) {
                return back()->withErrors(['payment_method_id' => 'يرجى اختيار طريقة دفع صالحة.'])->withInput();
            }
        }

        $branch = Branch::query()->where('status', Account::STATUS_ACTIVE)->findOrFail((int) $data['branch_id']);

        $stock = BranchProductStock::query()
            ->where('branch_id', $branch->id)
            ->where('product_unit_id', (int) $data['product_unit_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $requestedQty = (float) $data['quantity'];
        if ((float) $stock->quantity < $requestedQty) {
            return back()->withErrors(['order' => 'الكمية المطلوبة غير متوفرة حاليا.'])->withInput();
        }

        DB::transaction(function () use ($workshopId, $branch, $stock, $requestedQty, $data, $selectedMethod): void {
            $unitPrice = (float) ($stock->selling_price ?? 0);
            $lineTotal = $unitPrice * $requestedQty;

            $order = WorkshopPurchaseOrder::query()->create([
                'workshop_id' => $workshopId,
                'supplier_branch_id' => $branch->id,
                'order_number' => $this->generateOrderNumber(),
                'supplier_branch_name' => $branch->name,
                'total_amount' => $lineTotal,
                'status' => WorkshopPurchaseOrder::STATUS_PENDING,
                'notes' => $data['note'] ?? null,
                'payment_method_id' => $selectedMethod?->payment_method_id,
            ]);

            $this->attachOrderCheckoutPayment($order, $selectedMethod, $lineTotal);

            WorkshopPurchaseOrderItem::query()->create([
                'purchase_order_id' => $order->id,
                'branch_product_stock_id' => $stock->id,
                'product_id' => $stock->product_id,
                'product_unit_id' => $stock->product_unit_id,
                'quantity' => $requestedQty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);
        });

        return back()->with('status', 'تم إرسال طلب شراء من السوق بنجاح.');
    }

    private function generateOrderNumber(): string
    {
        do {
            $candidate = 'WPO-' . strtoupper(str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT));
        } while (WorkshopPurchaseOrder::query()->where('order_number', $candidate)->exists());

        return $candidate;
    }

    private function readCart(Request $request, int $workshopId): array
    {
        $allCarts = (array) $request->session()->get(self::CART_SESSION_KEY, []);
        $cart = $allCarts[$workshopId] ?? [];

        return is_array($cart) ? $cart : [];
    }

    private function writeCart(Request $request, int $workshopId, array $cart): void
    {
        $allCarts = (array) $request->session()->get(self::CART_SESSION_KEY, []);
        $allCarts[$workshopId] = $cart;
        $request->session()->put(self::CART_SESSION_KEY, $allCarts);
    }

    private function attachOrderCheckoutPayment(WorkshopPurchaseOrder $order, mixed $selectedMethod, float $amount): void
    {
        if ($selectedMethod === null) {
            return;
        }

        $gatewayType = strtolower((string) ($selectedMethod->paymentMethod?->type ?? 'online'));
        $paymentType = $gatewayType === 'offline' ? WorkshopOrderPayment::TYPE_CASH : WorkshopOrderPayment::TYPE_CREDIT;

        $notes = collect([
            trim((string) ($selectedMethod->account_name ?? '')) !== '' ? 'اسم الحساب: ' . trim((string) $selectedMethod->account_name) : null,
            trim((string) ($selectedMethod->account_number ?? '')) !== '' ? 'رقم الحساب: ' . trim((string) $selectedMethod->account_number) : null,
            trim((string) ($selectedMethod->note ?? '')) !== '' ? 'ملاحظة البوابة: ' . trim((string) $selectedMethod->note) : null,
        ])->filter()->implode("\n");

        WorkshopOrderPayment::query()->create([
            'purchase_order_id' => (int) $order->id,
            'payment_method_id' => (int) ($selectedMethod->payment_method_id ?? 0) ?: null,
            'account_id' => null,
            'amount' => max(0, $amount),
            'currency' => 'YER',
            'status' => WorkshopOrderPayment::STATUS_UNPAID,
            'transaction_reference' => 'TYPE:' . $paymentType . '|WORKSHOP_ORDER:' . (int) $order->id . '|CHECKOUT_METHOD:' . (int) ($selectedMethod->payment_method_id ?? 0),
            'notes' => $notes !== '' ? $notes : null,
            'paid_at' => null,
        ]);
    }

    private function resolveEnabledPaymentMethods(string $portalType, int $portalId)
    {
        if (! Schema::hasTable('payment_methods') || ! Schema::hasTable('portal_payment_methods')) {
            return collect();
        }

        return PortalPaymentMethod::query()
            ->with('paymentMethod:id,name,type,is_active')
            ->where('portal_type', $portalType)
            ->where('portal_id', $portalId)
            ->where('is_enabled', true)
            ->whereHas('paymentMethod', fn($query) => $query->where('is_active', true))
            ->orderBy('id')
            ->get();
    }
}
