<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Catalog\ProductUnit;
use App\Models\Distribution\Branch;
use App\Models\Distribution\BranchProductStock;
use App\Models\Finance\Payment;
use App\Models\Finance\PortalPaymentMethod;
use App\Models\Orders\Order;
use App\Models\PosLocalProduct;
use App\Services\Orders\OrderService;
use App\Services\Pos\PosContextService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class MarketplaceController extends Controller
{
    public function __construct(
        private readonly PosContextService $posContext,
        private readonly OrderService $orderService,
    ) {}

    public function index(Request $request): View
    {
        $pos = $this->posContext->currentPos();
        $customer = $this->posContext->resolveOrCreateCustomer($pos);
        $search = trim((string) $request->query('search', ''));
        $branchId = (int) $request->query('branch_id', 0);
        $sort = (string) $request->query('sort', 'price_asc');
        $compareProductUnitId = (int) $request->query('compare_product_unit_id', 0);
        $compareQuantity = max((int) $request->query('compare_quantity', 1), 1);

        $stocksCollection = BranchProductStock::query()
            ->with([
                'branch:id,name,address,gps_location,supplier_id',
                'branch.supplier:id,business_name,owner_name',
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
            ->get();

        $originCoordinates = $this->parseCoordinates((string) ($customer->gps_location ?? ''));

        $stocksCollection = $this->withDistance(
            $stocksCollection,
            fn($item) => (string) ($item->branch?->gps_location ?? ''),
            $originCoordinates
        );

        if ($sort === 'price_desc') {
            $stocksCollection = $stocksCollection->sortByDesc(fn($item) => (float) $item->selling_price)->values();
        } elseif ($sort === 'qty_desc') {
            $stocksCollection = $stocksCollection->sortByDesc(fn($item) => (float) $item->quantity)->values();
        } elseif ($sort === 'distance') {
            $stocksCollection = $stocksCollection->sortBy(fn($item) => $item->distance_km ?? INF)->values();
        } else {
            $stocksCollection = $stocksCollection->sortBy(fn($item) => (float) $item->selling_price)->values();
        }

        $stocks = $this->paginateCollection($stocksCollection, 18, 'page', $request);

        $branches = Branch::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $cartItems = collect($this->getCart($pos));
        $cartTotal = (float) $cartItems->sum(fn(array $row) => (float) $row['unit_price'] * (float) $row['quantity']);
        $checkoutPaymentMethods = $this->resolveEnabledPaymentMethods('pos', (int) $pos->id);

        $comparisonOffers = collect();
        if ($compareProductUnitId > 0) {
            $comparisonOffers = BranchProductStock::query()
                ->with([
                    'branch:id,name,gps_location',
                    'product:id,name',
                    'productUnit.unit:id,name',
                ])
                ->where('product_unit_id', $compareProductUnitId)
                ->where('is_active', true)
                ->where('quantity', '>', 0)
                ->get();

            $comparisonOffers = $this->withDistance(
                $comparisonOffers,
                fn($item) => (string) ($item->branch?->gps_location ?? ''),
                $originCoordinates
            )
                ->sortBy(fn($item) => (float) $item->selling_price)
                ->values()
                ->map(function ($item) use ($compareQuantity) {
                    $item->requested_quantity = $compareQuantity;
                    $item->estimated_total = round((float) $item->selling_price * $compareQuantity, 2);
                    return $item;
                });
        }

        return view('pos.marketplace.index', compact(
            'pos',
            'stocks',
            'branches',
            'cartItems',
            'cartTotal',
            'checkoutPaymentMethods',
            'comparisonOffers',
            'compareProductUnitId',
            'compareQuantity'
        ));
    }

    public function addToCart(Request $request): RedirectResponse
    {
        $pos = $this->posContext->currentPos();

        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'product_unit_id' => ['required', 'integer', 'exists:product_units,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $stock = BranchProductStock::query()
            ->with(['product:id,name', 'productUnit.unit:id,name', 'branch:id,name'])
            ->where('branch_id', (int) $data['branch_id'])
            ->where('product_unit_id', (int) $data['product_unit_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $key = $this->cartItemKey((int) $stock->branch_id, (int) $stock->product_unit_id);
        $cart = $this->getCart($pos);
        $existingQty = (int) ($cart[$key]['quantity'] ?? 0);
        $nextQty = $existingQty + (int) $data['quantity'];

        if ((float) $stock->quantity < (float) $nextQty) {
            return back()->withErrors(['cart' => 'الكمية المطلوبة تتجاوز المتاح في الفرع.'])->withInput();
        }

        $cart[$key] = [
            'key' => $key,
            'branch_id' => (int) $stock->branch_id,
            'branch_name' => (string) ($stock->branch?->name ?? '-'),
            'product_id' => (int) $stock->product_id,
            'product_unit_id' => (int) $stock->product_unit_id,
            'product_name' => (string) ($stock->product?->name ?? 'منتج'),
            'unit_name' => (string) ($stock->productUnit?->unit?->name ?? ''),
            'unit_price' => (float) ($stock->selling_price ?? 0),
            'available_qty' => (float) ($stock->quantity ?? 0),
            'quantity' => $nextQty,
        ];

        $this->putCart($pos, $cart);

        return back()->with('success', 'تمت إضافة المنتج إلى سلة التوريد.');
    }

    public function removeFromCart(string $key): RedirectResponse
    {
        $pos = $this->posContext->currentPos();
        $cart = $this->getCart($pos);

        unset($cart[$key]);
        $this->putCart($pos, $cart);

        return back()->with('success', 'تم حذف المنتج من السلة.');
    }

    public function clearCart(): RedirectResponse
    {
        $pos = $this->posContext->currentPos();
        session()->forget($this->cartSessionKey($pos->id));

        return back()->with('success', 'تم تفريغ سلة التوريد.');
    }

    public function checkoutCart(Request $request): RedirectResponse
    {
        $pos = $this->posContext->currentPos();
        $customer = $this->posContext->resolveOrCreateCustomer($pos);
        $checkoutPaymentMethods = $this->resolveEnabledPaymentMethods('pos', (int) $pos->id);

        $rules = [
            'note' => ['nullable', 'string', 'max:1000'],
        ];
        if ($checkoutPaymentMethods->isNotEmpty()) {
            $rules['payment_method_id'] = ['required', 'integer'];
        }

        $validated = $request->validate($rules);

        $cart = $this->getCart($pos);
        if (empty($cart)) {
            return back()->withErrors(['cart' => 'سلة التوريد فارغة.']);
        }

        $systemUserId = $this->resolveSystemCreatorId();
        if ($systemUserId <= 0) {
            return back()->withErrors(['order' => 'لا يوجد حساب نظام فعّال لإنشاء الطلب.']);
        }

        $selectedMethod = null;
        if ($checkoutPaymentMethods->isNotEmpty()) {
            $selectedMethod = $checkoutPaymentMethods->firstWhere('payment_method_id', (int) ($validated['payment_method_id'] ?? 0));

            if ($selectedMethod === null) {
                return back()->withErrors(['payment_method_id' => 'يرجى اختيار طريقة دفع صالحة.'])->withInput();
            }
        }

        $groups = collect($cart)->groupBy(fn(array $row) => (int) $row['branch_id']);
        $orderIds = [];

        foreach ($groups as $branchId => $items) {
            $branch = Branch::query()->where('status', 'active')->findOrFail((int) $branchId);

            $itemsPayload = [];
            foreach ($items as $item) {
                $stock = BranchProductStock::query()
                    ->where('branch_id', $branch->id)
                    ->where('product_unit_id', (int) $item['product_unit_id'])
                    ->where('is_active', true)
                    ->first();

                if (! $stock || (float) $stock->quantity < (float) $item['quantity']) {
                    return back()->withErrors(['cart' => 'بعض عناصر السلة لم تعد متوفرة بالكمية المطلوبة.']);
                }

                $itemsPayload[] = [
                    'product_id' => (int) $item['product_id'],
                    'product_unit_id' => (int) $item['product_unit_id'],
                    'quantity' => (int) $item['quantity'],
                ];
            }

            $order = $this->orderService->createOrder([
                'supplier_id' => $branch->supplier_id,
                'branch_id' => $branch->id,
                'buyer_type' => Order::BUYER_TYPE_CUSTOMER,
                'buyer_id' => $customer->id,
                'seller_type' => 'branch',
                'seller_id' => $branch->id,
                'created_by_agent_id' => $systemUserId,
                'items' => $itemsPayload,
            ]);

            $this->attachOrderCheckoutPayment($order, $selectedMethod);

            foreach ($items as $item) {
                $unitId = (int) $item['product_unit_id'];
                $stock = BranchProductStock::query()
                    ->where('branch_id', $branch->id)
                    ->where('product_unit_id', $unitId)
                    ->where('is_active', true)
                    ->first();

                PosLocalProduct::query()->updateOrCreate(
                    [
                        'pos_account_id' => $pos->id,
                        'branch_id' => $branch->id,
                        'product_unit_id' => $unitId,
                    ],
                    [
                        'product_id' => (int) $item['product_id'],
                        'purchase_price' => (float) ($stock?->selling_price ?? 0),
                        'selling_price' => round(((float) ($stock?->selling_price ?? 0)) * 1.15, 2),
                        'is_active' => true,
                    ]
                );
            }

            $orderIds[] = $order->id;
        }

        session()->forget($this->cartSessionKey($pos->id));

        return back()->with('success', 'تم إنشاء طلبات التوريد بنجاح: #' . implode(', #', $orderIds));
    }

    public function storeOrder(Request $request): RedirectResponse
    {
        $pos = $this->posContext->currentPos();
        $customer = $this->posContext->resolveOrCreateCustomer($pos);
        $checkoutPaymentMethods = $this->resolveEnabledPaymentMethods('pos', (int) $pos->id);

        $rules = [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'product_unit_id' => ['required', 'integer', 'exists:product_units,id'],
            'quantity' => ['required', 'integer', 'min:1'],
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

        $branch = Branch::query()->where('status', 'active')->findOrFail((int) $data['branch_id']);

        $stock = BranchProductStock::query()
            ->where('branch_id', $branch->id)
            ->where('product_unit_id', (int) $data['product_unit_id'])
            ->where('is_active', true)
            ->firstOrFail();

        if ((float) $stock->quantity < (float) $data['quantity']) {
            return back()->withErrors(['order' => 'الكمية المطلوبة غير متوفرة حاليا.'])->withInput();
        }

        $productUnit = ProductUnit::query()->with('product')->findOrFail((int) $data['product_unit_id']);

        $systemUserId = (int) (\App\Models\Agent::query()->orderBy('id')->value('id') ?? 0);
        if ($systemUserId <= 0) {
            $systemUserId = (int) (\App\Models\Admin::query()->orderBy('id')->value('id') ?? 0);
        }
        if ($systemUserId <= 0) {
            return back()->withErrors(['order' => 'لا يوجد حساب نظام فعّال لإنشاء الطلب.']);
        }

        $order = $this->orderService->createOrder([
            'supplier_id' => $branch->supplier_id,
            'branch_id' => $branch->id,
            'buyer_type' => Order::BUYER_TYPE_CUSTOMER,
            'buyer_id' => $customer->id,
            'seller_type' => 'branch',
            'seller_id' => $branch->id,
            'created_by_agent_id' => $systemUserId,
            'items' => [
                [
                    'product_id' => $productUnit->product_id,
                    'product_unit_id' => $productUnit->id,
                    'quantity' => (int) $data['quantity'],
                ],
            ],
        ]);

        $this->attachOrderCheckoutPayment($order, $selectedMethod);

        PosLocalProduct::query()->updateOrCreate(
            [
                'pos_account_id' => $pos->id,
                'branch_id' => $branch->id,
                'product_unit_id' => $productUnit->id,
            ],
            [
                'product_id' => $productUnit->product_id,
                'purchase_price' => (float) ($stock->selling_price ?? 0),
                'selling_price' => round(((float) ($stock->selling_price ?? 0)) * 1.15, 2),
                'is_active' => true,
            ]
        );

        return back()->with('success', 'تم إرسال طلب التوريد بنجاح. رقم الطلب #' . $order->id);
    }

    private function cartSessionKey(int $posId): string
    {
        return 'pos_marketplace_cart_' . $posId;
    }

    private function cartItemKey(int $branchId, int $productUnitId): string
    {
        return $branchId . ':' . $productUnitId;
    }

    private function getCart($pos): array
    {
        $cart = session()->get($this->cartSessionKey((int) $pos->id), []);
        return is_array($cart) ? $cart : [];
    }

    private function putCart($pos, array $cart): void
    {
        session()->put($this->cartSessionKey((int) $pos->id), $cart);
    }

    private function resolveSystemCreatorId(): int
    {
        $agentId = (int) (\App\Models\Agent::query()->orderBy('id')->value('id') ?? 0);
        if ($agentId > 0) {
            return $agentId;
        }

        return (int) (\App\Models\Admin::query()->orderBy('id')->value('id') ?? 0);
    }

    private function resolveEnabledPaymentMethods(string $portalType, int $portalId): Collection
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

    private function attachOrderCheckoutPayment(Order $order, mixed $selectedMethod): void
    {
        if ($selectedMethod === null) {
            return;
        }

        $gatewayType = strtolower((string) ($selectedMethod->paymentMethod?->type ?? 'online'));
        $paymentType = $gatewayType === 'offline' ? Payment::TYPE_CASH : Payment::TYPE_CREDIT;

        $notes = collect([
            trim((string) ($selectedMethod->account_name ?? '')) !== '' ? 'اسم الحساب: ' . trim((string) $selectedMethod->account_name) : null,
            trim((string) ($selectedMethod->account_number ?? '')) !== '' ? 'رقم الحساب: ' . trim((string) $selectedMethod->account_number) : null,
            trim((string) ($selectedMethod->note ?? '')) !== '' ? 'ملاحظة البوابة: ' . trim((string) $selectedMethod->note) : null,
        ])->filter()->implode("\n");

        Payment::query()->create([
            'order_id' => (int) $order->id,
            'payment_method_id' => (int) ($selectedMethod->payment_method_id ?? 0) ?: null,
            'account_id' => null,
            'amount' => 0,
            'currency' => 'YER',
            'status' => Payment::STATUS_UNPAID,
            'transaction_reference' => 'TYPE:' . $paymentType . '|CHECKOUT_METHOD:' . (int) ($selectedMethod->payment_method_id ?? 0),
            'notes' => $notes !== '' ? $notes : null,
            'paid_at' => null,
        ]);
    }

    private function parseCoordinates(?string $value): ?array
    {
        if (! $value || ! str_contains($value, ',')) {
            return null;
        }

        [$lat, $lng] = array_map('trim', explode(',', $value, 2));
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return [(float) $lat, (float) $lng];
    }

    private function haversineKm(array $origin, array $destination): float
    {
        [$lat1, $lon1] = $origin;
        [$lat2, $lon2] = $destination;

        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return $earthRadius * (2 * asin(min(1, sqrt($a))));
    }

    private function withDistance(Collection $items, callable $gpsResolver, ?array $originCoordinates): Collection
    {
        return $items->map(function ($item) use ($gpsResolver, $originCoordinates) {
            $itemCoordinates = $this->parseCoordinates($gpsResolver($item));

            if ($originCoordinates === null || $itemCoordinates === null) {
                $item->distance_km = null;
            } else {
                $item->distance_km = $this->haversineKm($originCoordinates, $itemCoordinates);
            }

            return $item;
        });
    }

    private function paginateCollection(Collection $items, int $perPage, string $pageName, Request $request): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);
        $total = $items->count();
        $results = $items->forPage($page, $perPage)->values();

        return (new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => $pageName,
            ]
        ))->appends($request->query());
    }
}
