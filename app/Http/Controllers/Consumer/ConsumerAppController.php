<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Models\Customer\ConsumerAddress;
use App\Models\Customer\ConsumerLoyaltyPoint;
use App\Models\Customer\ConsumerRating;
use App\Models\Customer\ConsumerVehicleProfile;
use App\Models\Customer\Workshop;
use App\Models\Distribution\Branch;
use App\Models\Distribution\BranchProductStock;
use App\Models\Finance\Account;
use App\Models\Notifications\WebAlert;
use App\Models\Orders\Order;
use App\Models\Pos;
use App\Models\PosLocalProduct;
use App\Models\PosSale;
use App\Models\Workshop\WorkshopAppointment;
use App\Models\Workshop\WorkshopService;
use App\Models\Workshop\WorkshopServiceOrder;
use App\Services\Notifications\WebAlertService;
use App\Services\Orders\OrderService;
use App\Services\Pricing\CommissionEngineService;
use App\Support\Validation\UniqueUserContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConsumerAppController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly WebAlertService $webAlertService,
        private readonly CommissionEngineService $commissionEngineService,
    ) {}

    public function dashboard(): View
    {
        $consumer = Auth::guard('consumer')->user();
        $this->syncLoyaltyPoints($consumer);

        $ordersBase = Order::query()
            ->where('buyer_type', Order::BUYER_TYPE_CONSUMER)
            ->where('buyer_id', $consumer->id);

        $stats = [
            'orders_count' => (int) (clone $ordersBase)->count(),
            'pending_count' => (int) (clone $ordersBase)
                ->whereIn('status', [
                    Order::STATUS_PENDING,
                    Order::STATUS_APPROVED,
                    Order::STATUS_OUT_FOR_DELIVERY,
                ])
                ->count(),
            'delivered_count' => (int) (clone $ordersBase)
                ->where('status', Order::STATUS_DELIVERED)
                ->count(),
            'total_spend' => (float) (clone $ordersBase)
                ->where('status', Order::STATUS_DELIVERED)
                ->sum(DB::raw('COALESCE(payable_total, total_price)')),
        ];

        $recentOrders = (clone $ordersBase)
            ->latest('id')
            ->limit(10)
            ->get(['id', 'total_price', 'payable_total', 'seller_type', 'status', 'created_at']);

        $predictions = $this->buildReorderPredictions($consumer);
        $this->generateConsumerSmartAlerts($consumer, $predictions);

        $dueProductsCount = (int) collect($predictions['product_predictions'])
            ->where('should_reorder_soon', true)
            ->count();

        $dueServicesCount = (int) collect($predictions['service_predictions'])
            ->where('should_reorder_soon', true)
            ->count();

        $recentConsumerAlerts = $this->webAlertService->getRecent('consumer', (int) $consumer->id, 8);
        $consumerUnreadAlertsCount = $this->webAlertService->unreadCount('consumer', (int) $consumer->id);
        $loyaltyBalance = $this->loyaltyBalance((int) $consumer->id);
        $vehiclesCount = (int) ConsumerVehicleProfile::query()->where('consumer_id', $consumer->id)->count();

        return view('consumer.dashboard', compact(
            'consumer',
            'stats',
            'recentOrders',
            'dueProductsCount',
            'dueServicesCount',
            'recentConsumerAlerts',
            'consumerUnreadAlertsCount',
            'loyaltyBalance',
            'vehiclesCount'
        ));
    }

    public function home(): View
    {
        $consumer = Auth::guard('consumer')->user();
        $originCoordinates = $this->resolveConsumerCoordinates($consumer);
        $hasGeoLocation = $originCoordinates !== null;

        $nearbyPos = Branch::query()
            ->where('status', Account::STATUS_ACTIVE)
            ->get(['id', 'name', 'address', 'gps_location']);

        $nearbyPos = $this->withDistance(
            $nearbyPos,
            fn($row) => $row->gps_location,
            $originCoordinates
        )
            ->sortBy(fn($row) => $row->distance_km ?? INF)
            ->take(6)
            ->values();

        $nearbyWorkshops = Workshop::query()
            ->with(['customer:id,address,gps_location'])
            ->where('status', Account::STATUS_ACTIVE)
            ->get(['id', 'name', 'customer_id']);

        $nearbyWorkshops = $this->withDistance(
            $nearbyWorkshops,
            fn($row) => $row->gps_location,
            $originCoordinates
        )
            ->sortBy(fn($row) => $row->distance_km ?? INF)
            ->take(6)
            ->values();

        $nearbyRetailPos = Pos::query()
            ->with(['customer:id,address,gps_location'])
            ->where('status', Account::STATUS_ACTIVE)
            ->get(['id', 'name', 'customer_id']);

        $nearbyRetailPos = $this->withDistance(
            $nearbyRetailPos,
            fn($row) => (string) ($row->gps_location ?? ''),
            $originCoordinates
        )
            ->sortBy(fn($row) => $row->distance_km ?? INF)
            ->take(6)
            ->values();

        $popularServices = WorkshopService::query()
            ->where('is_active', true)
            ->latest('id')
            ->limit(6)
            ->get(['id', 'workshop_id', 'name', 'price', 'duration_minutes']);

        $featuredProducts = BranchProductStock::query()
            ->with(['product:id,name,model', 'productUnit.unit:id,name', 'branch:id,name'])
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->orderByDesc('quantity')
            ->limit(8)
            ->get();

        $offers = BranchProductStock::query()
            ->with(['product:id,name,model', 'branch:id,name'])
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->whereNotNull('selling_price')
            ->orderBy('selling_price')
            ->limit(5)
            ->get();

        $featuredProducts = $this->hideConsumerInventoryFields($featuredProducts);
        $offers = $this->hideConsumerInventoryFields($offers);

        $predictions = $this->buildReorderPredictions($consumer);
        $this->generateConsumerSmartAlerts($consumer, $predictions);
        $reorderProductPredictions = $predictions['product_predictions'];
        $reorderServicePredictions = $predictions['service_predictions'];

        $recentConsumerAlerts = $this->webAlertService->getRecent('consumer', (int) $consumer->id, 6);
        $consumerUnreadAlertsCount = $this->webAlertService->unreadCount('consumer', (int) $consumer->id);

        return view('consumer.home', compact(
            'consumer',
            'hasGeoLocation',
            'nearbyPos',
            'nearbyRetailPos',
            'nearbyWorkshops',
            'popularServices',
            'featuredProducts',
            'offers',
            'reorderProductPredictions',
            'reorderServicePredictions',
            'recentConsumerAlerts',
            'consumerUnreadAlertsCount'
        ));
    }

    public function browse(Request $request): View
    {
        $consumer = Auth::guard('consumer')->user();
        $type = (string) $request->query('type', 'all');
        $q = trim((string) $request->query('q', ''));
        $maxPrice = (float) $request->query('max_price', 0);
        $minRating = (float) $request->query('min_rating', 0);
        $radiusKm = (float) $request->query('radius_km', 0);
        $sort = (string) $request->query('sort', 'price_asc');
        $originCoordinates = $this->resolveConsumerCoordinates($consumer);

        $ratings = ConsumerRating::query()
            ->selectRaw('store_type, store_id, AVG(rating) as avg_rating, COUNT(*) as ratings_count')
            ->groupBy('store_type', 'store_id')
            ->get()
            ->keyBy(fn($item) => $item->store_type . ':' . $item->store_id);

        $productItems = BranchProductStock::query()
            ->with(['product:id,name,model', 'productUnit.unit:id,name', 'branch:id,name,address,gps_location'])
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->when($maxPrice > 0, fn($q) => $q->where('selling_price', '<=', $maxPrice))
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('product', function ($productQuery) use ($q) {
                    $productQuery
                        ->where('name', 'like', '%' . $q . '%')
                        ->orWhere('model', 'like', '%' . $q . '%');

                    if (is_numeric($q)) {
                        $productQuery->orWhere('id', (int) $q);
                    }
                });
            })
            ->get();

        $productItems = $this->withDistance(
            $productItems,
            fn($item) => $item->branch?->gps_location,
            $originCoordinates
        );

        if ($radiusKm > 0 && $originCoordinates !== null) {
            $productItems = $productItems
                ->filter(fn($item) => ! is_null($item->distance_km) && (float) $item->distance_km <= $radiusKm)
                ->values();
        }

        if ($minRating > 0) {
            $productItems = $productItems
                ->filter(function ($item) use ($ratings, $minRating) {
                    $rate = $ratings->get('pos:' . $item->branch_id);

                    return $rate && (float) $rate->avg_rating >= $minRating;
                })
                ->values();
        }

        if ($sort === 'price_desc') {
            $productItems = $productItems->sortByDesc(fn($item) => (float) $item->selling_price)->values();
        } elseif ($sort === 'distance') {
            $productItems = $productItems->sortBy(fn($item) => $item->distance_km ?? INF)->values();
        } else {
            $productItems = $productItems->sortBy(fn($item) => (float) $item->selling_price)->values();
        }

        $products = $this->paginateCollection($productItems, 12, 'products_page', $request);
        $products = $this->hideConsumerInventoryFields($products);

        $serviceItems = WorkshopService::query()
            ->with(['workshop:id,name,customer_id', 'workshop.customer:id,address,gps_location'])
            ->where('is_active', true)
            ->when($maxPrice > 0, fn($q) => $q->where('price', '<=', $maxPrice))
            ->get();

        $serviceItems = $this->withDistance(
            $serviceItems,
            fn($item) => $item->workshop?->gps_location,
            $originCoordinates
        );

        if ($radiusKm > 0 && $originCoordinates !== null) {
            $serviceItems = $serviceItems
                ->filter(fn($item) => ! is_null($item->distance_km) && (float) $item->distance_km <= $radiusKm)
                ->values();
        }

        if ($minRating > 0) {
            $serviceItems = $serviceItems
                ->filter(function ($item) use ($ratings, $minRating) {
                    $rate = $ratings->get('workshop:' . $item->workshop_id);

                    return $rate && (float) $rate->avg_rating >= $minRating;
                })
                ->values();
        }

        if ($sort === 'price_desc') {
            $serviceItems = $serviceItems->sortByDesc(fn($item) => (float) $item->price)->values();
        } elseif ($sort === 'distance') {
            $serviceItems = $serviceItems->sortBy(fn($item) => $item->distance_km ?? INF)->values();
        } else {
            $serviceItems = $serviceItems->sortBy(fn($item) => (float) $item->price)->values();
        }

        $services = $this->paginateCollection($serviceItems, 12, 'services_page', $request);

        return view('consumer.browse.index', compact(
            'products',
            'services',
            'type',
            'q',
            'sort',
            'maxPrice',
            'minRating',
            'radiusKm',
            'ratings'
        ));
    }

    public function recommendations(): \Illuminate\Http\JsonResponse
    {
        $consumer = Auth::guard('consumer')->user();
        $originCoordinates = $this->resolveConsumerCoordinates($consumer);

        $topProductUnitIds = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.buyer_type', Order::BUYER_TYPE_CONSUMER)
            ->where('orders.buyer_id', $consumer->id)
            ->where('orders.status', Order::STATUS_DELIVERED)
            ->selectRaw('order_items.product_unit_id, SUM(order_items.quantity) as qty')
            ->groupBy('order_items.product_unit_id')
            ->orderByDesc('qty')
            ->limit(8)
            ->pluck('product_unit_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $productRecommendations = BranchProductStock::query()
            ->with(['branch:id,name,gps_location', 'product:id,name,model'])
            ->whereIn('product_unit_id', $topProductUnitIds)
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->orderBy('selling_price')
            ->limit(20)
            ->get();

        $productRecommendations = $this->withDistance(
            $productRecommendations,
            fn($item) => (string) ($item->branch?->gps_location ?? ''),
            $originCoordinates
        )
            ->sortBy(function ($row) {
                return ((float) $row->selling_price * 10) + (float) ($row->distance_km ?? 0);
            })
            ->take(8)
            ->values();

        $productRecommendations = $this->hideConsumerInventoryFields($productRecommendations);

        $serviceRecommendations = WorkshopService::query()
            ->with(['workshop:id,name,customer_id', 'workshop.customer:id,gps_location'])
            ->where('is_active', true)
            ->orderBy('price')
            ->limit(30)
            ->get();

        $serviceRecommendations = $this->withDistance(
            $serviceRecommendations,
            fn($item) => (string) ($item->workshop?->gps_location ?? ''),
            $originCoordinates
        )
            ->sortBy(function ($row) {
                return ((float) $row->price * 10) + (float) ($row->distance_km ?? 0);
            })
            ->take(8)
            ->values();

        return response()->json([
            'success' => true,
            'consumer_id' => (int) $consumer->id,
            'product_recommendations' => $productRecommendations,
            'service_recommendations' => $serviceRecommendations,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function storeView(string $storeType, int $storeId): View
    {
        abort_unless(in_array($storeType, ['pos', 'workshop', 'retail'], true), 404);

        $consumer = Auth::guard('consumer')->user();
        $originCoordinates = $this->resolveConsumerCoordinates($consumer);
        $compareProductUnitId = (int) request()->query('compare_product_unit_id', 0);
        $priceComparisons = collect();

        if ($storeType === 'pos') {
            $store = Branch::query()->where('status', Account::STATUS_ACTIVE)->findOrFail($storeId);
            $this->ensureNearbyAccess((string) $store->gps_location, $originCoordinates);

            $products = BranchProductStock::query()
                ->with(['product:id,name,model', 'productUnit.unit:id,name'])
                ->where('branch_id', $store->id)
                ->where('is_active', true)
                ->where('quantity', '>', 0)
                ->orderBy('selling_price')
                ->paginate(12)
                ->withQueryString();

            $products = $this->hideConsumerInventoryFields($products);

            if ($compareProductUnitId > 0) {
                $priceComparisons = BranchProductStock::query()
                    ->with(['branch:id,name,gps_location', 'product:id,name', 'productUnit.unit:id,name'])
                    ->where('product_unit_id', $compareProductUnitId)
                    ->where('is_active', true)
                    ->where('quantity', '>', 0)
                    ->get();

                $priceComparisons = $this->withDistance(
                    $priceComparisons,
                    fn($item) => (string) ($item->branch?->gps_location ?? ''),
                    $originCoordinates
                )
                    ->sortBy(fn($item) => (float) $item->selling_price)
                    ->values();

                $priceComparisons = $this->hideConsumerInventoryFields($priceComparisons);
            }

            $services = collect();
        } elseif ($storeType === 'retail') {
            $store = Pos::query()->where('status', Account::STATUS_ACTIVE)->findOrFail($storeId);
            $this->ensureNearbyAccess((string) ($store->gps_location ?? ''), $originCoordinates);

            $products = PosLocalProduct::query()
                ->with(['product:id,name,model', 'productUnit.unit:id,name'])
                ->where('pos_account_id', $store->id)
                ->where('is_active', true)
                ->where('local_quantity', '>', 0)
                ->orderBy('selling_price')
                ->paginate(12)
                ->withQueryString();

            $products = $this->hideConsumerInventoryFields($products);

            $services = collect();
        } else {
            $store = Workshop::query()->where('status', Account::STATUS_ACTIVE)->findOrFail($storeId);
            $this->ensureNearbyAccess((string) ($store->gps_location ?? ''), $originCoordinates);

            $services = WorkshopService::query()
                ->where('workshop_id', $store->id)
                ->where('is_active', true)
                ->orderBy('price')
                ->paginate(12)
                ->withQueryString();

            $products = collect();
        }

        if ($storeType === 'retail') {
            $storeRatings = collect();
            $ratingSummary = ['avg' => 0.0, 'count' => 0];
        } else {
            $storeRatings = ConsumerRating::query()
                ->where('store_type', $storeType)
                ->where('store_id', $storeId)
                ->latest()
                ->limit(12)
                ->get();

            $ratingSummary = [
                'avg' => (float) ConsumerRating::query()
                    ->where('store_type', $storeType)
                    ->where('store_id', $storeId)
                    ->avg('rating'),
                'count' => (int) ConsumerRating::query()
                    ->where('store_type', $storeType)
                    ->where('store_id', $storeId)
                    ->count(),
            ];
        }

        return view('consumer.store.show', compact(
            'storeType',
            'store',
            'products',
            'services',
            'storeRatings',
            'ratingSummary',
            'priceComparisons',
            'compareProductUnitId'
        ));
    }

    private function hideConsumerInventoryFields(Collection|LengthAwarePaginator $items): Collection|LengthAwarePaginator
    {
        $hidden = ['quantity', 'local_quantity'];

        if ($items instanceof LengthAwarePaginator) {
            $items->setCollection(
                $items->getCollection()->map(function ($item) use ($hidden) {
                    if (is_object($item) && method_exists($item, 'makeHidden')) {
                        $item->makeHidden($hidden);
                    }

                    return $item;
                })
            );

            return $items;
        }

        return $items->map(function ($item) use ($hidden) {
            if (is_object($item) && method_exists($item, 'makeHidden')) {
                $item->makeHidden($hidden);
            }

            return $item;
        });
    }

    public function createRetailOrder(Request $request): RedirectResponse
    {
        $consumer = Auth::guard('consumer')->user();

        $data = $request->validate([
            'pos_id' => ['required', 'integer', Rule::exists('accounts', 'id')->where(fn($q) => $q->where('account_type', 'pos'))],
            'pos_local_product_id' => ['required', 'integer', 'exists:pos_local_products,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $pos = Pos::query()->where('status', Account::STATUS_ACTIVE)->findOrFail((int) $data['pos_id']);
        $localProduct = PosLocalProduct::query()
            ->with('product:id,name')
            ->where('pos_account_id', $pos->id)
            ->where('is_active', true)
            ->findOrFail((int) $data['pos_local_product_id']);

        $qty = (float) $data['quantity'];
        if ((float) $localProduct->local_quantity <= 0 || (float) $localProduct->local_quantity < $qty) {
            return back()->withErrors(['order' => 'الكمية المطلوبة غير متوفرة في متجر POS.'])->withInput();
        }

        $unitPrice = (float) $localProduct->selling_price;
        $purchasePrice = (float) $localProduct->purchase_price;
        $total = round($unitPrice * $qty, 2);
        $profit = round(($unitPrice - $purchasePrice) * $qty, 2);

        PosSale::query()->create([
            'pos_account_id' => $pos->id,
            'pos_local_product_id' => $localProduct->id,
            'product_name' => (string) ($localProduct->product?->name ?? 'منتج'),
            'customer_name' => (string) $consumer->name,
            'customer_phone' => (string) $consumer->phone,
            'sale_channel' => 'online',
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'total_amount' => $total,
            'profit_amount' => $profit,
            'note' => ($data['notes'] ?? '') !== ''
                ? 'طلب من تطبيق المستهلك: ' . $data['notes']
                : 'طلب من تطبيق المستهلك',
            'sold_at' => now(),
        ]);

        $localProduct->update([
            'local_quantity' => max(0, (float) $localProduct->local_quantity - $qty),
        ]);

        $this->webAlertService->create(
            'pos_account',
            $pos->id,
            'طلب مستهلك جديد',
            'تم تسجيل طلب جديد من المستهلك على منتج ' . ($localProduct->product?->name ?? 'منتج'),
            [
                'type' => 'consumer_retail_order',
                'pos_local_product_id' => $localProduct->id,
                'quantity' => $qty,
                'consumer_phone' => $consumer->phone,
            ]
        );

        return back()->with('status', 'تم إرسال الطلب إلى متجر POS بنجاح.');
    }

    public function createProductOrder(Request $request): RedirectResponse
    {
        $consumer = Auth::guard('consumer')->user();

        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'product_unit_id' => ['required', 'integer', 'exists:product_units,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'fulfillment' => ['required', 'in:pickup,delivery'],
        ]);

        $branch = Branch::query()->where('status', Account::STATUS_ACTIVE)->findOrFail((int) $data['branch_id']);

        $stock = BranchProductStock::query()
            ->where('branch_id', $branch->id)
            ->where('product_unit_id', (int) $data['product_unit_id'])
            ->where('is_active', true)
            ->firstOrFail();

        if ((float) $stock->quantity < (float) $data['quantity']) {
            return back()->withErrors(['order' => 'الكمية المطلوبة غير متوفرة حاليا.'])->withInput();
        }

        $systemUserId = $this->resolveSystemCreatorId();
        if ($systemUserId <= 0) {
            return back()->withErrors(['order' => 'لا يوجد حساب نظام فعّال لإنشاء الطلب.']);
        }

        $order = $this->orderService->createOrder([
            'supplier_id' => $branch->supplier_id,
            'branch_id' => $branch->id,
            'buyer_type' => Order::BUYER_TYPE_CONSUMER,
            'buyer_id' => $consumer->id,
            'seller_type' => 'branch',
            'seller_id' => $branch->id,
            'created_by_agent_id' => $systemUserId,
            'customer_address_override' => $this->resolveOrderAddressForFulfillment($consumer, $branch, (string) $data['fulfillment']),
            'items' => [
                [
                    'product_id' => $stock->product_id,
                    'product_unit_id' => (int) $data['product_unit_id'],
                    'quantity' => (int) $data['quantity'],
                ],
            ],
        ]);

        $modeText = $data['fulfillment'] === 'delivery' ? 'توصيل' : 'استلام';

        return back()->with('status', 'تم إنشاء الطلب بنجاح (' . $modeText . '). رقم الطلب #' . $order->id);
    }

    public function createServiceOrder(Request $request): RedirectResponse
    {
        $consumer = Auth::guard('consumer')->user();

        $data = $request->validate([
            'workshop_id' => ['required', 'integer', Rule::exists('accounts', 'id')->where(fn($q) => $q->where('account_type', 'workshop'))],
            'service_id' => ['required', 'integer', 'exists:workshop_services,id'],
            'appointment_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $workshop = Workshop::query()->where('status', Account::STATUS_ACTIVE)->findOrFail((int) $data['workshop_id']);
        $service = WorkshopService::query()
            ->where('workshop_id', $workshop->id)
            ->where('is_active', true)
            ->findOrFail((int) $data['service_id']);

        $appointment = null;
        if (! empty($data['appointment_at'])) {
            $appointment = WorkshopAppointment::query()->create([
                'workshop_id' => $workshop->id,
                'service_id' => $service->id,
                'snapshot_customer_name' => $consumer->name,
                'snapshot_customer_phone' => $consumer->phone,
                'appointment_at' => $data['appointment_at'],
                'estimated_minutes' => (int) $service->duration_minutes,
                'status' => WorkshopAppointment::STATUS_SCHEDULED,
                'notes' => $data['notes'] ?? null,
            ]);
        }

        $baseTotal = (float) $service->price;
        $commission = $this->commissionEngineService->calculate($baseTotal, 'workshop', (int) $workshop->id, null);

        WorkshopServiceOrder::query()->create([
            'workshop_id' => $workshop->id,
            'service_id' => $service->id,
            'appointment_id' => $appointment?->id,
            'order_number' => 'CSO-' . strtoupper(str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT)),
            'snapshot_customer_name' => $consumer->name,
            'snapshot_customer_phone' => $consumer->phone,
            'consumer_id' => (int) $consumer->id,
            'service_fee' => $baseTotal,
            'products_total' => 0,
            'total_amount' => $baseTotal,
            'commission_rule_id' => $commission['rule_id'],
            'commission_percent' => $commission['commission_percent'],
            'commission_value' => $commission['commission_value'],
            'platform_service_fee' => $commission['service_fee'],
            'platform_fixed_fee' => $commission['fixed_fee'],
            'payable_total' => $commission['final_amount'],
            'status' => WorkshopServiceOrder::STATUS_REQUESTED,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('status', 'تم إرسال طلب الخدمة بنجاح.');
    }

    public function tracking(): View
    {
        $consumer = Auth::guard('consumer')->user();

        $productOrders = Order::query()
            ->with([
                'items.product:id,name',
                'locationLogs' => fn($query) => $query->latest()->limit(1),
            ])
            ->where('buyer_type', Order::BUYER_TYPE_CONSUMER)
            ->where('buyer_id', $consumer->id)
            ->latest()
            ->limit(30)
            ->get();

        $serviceOrders = WorkshopServiceOrder::query()
            ->with(['service:id,name', 'workshop:id,name'])
            ->where(function ($query) use ($consumer) {
                $query->where('consumer_id', (int) $consumer->id)
                    ->orWhere(function ($legacy) use ($consumer) {
                        $legacy->whereNull('consumer_id')
                            ->where('snapshot_customer_phone', $consumer->phone);
                    });
            })
            ->latest()
            ->limit(30)
            ->get();

        return view('consumer.tracking.index', compact('productOrders', 'serviceOrders'));
    }

    public function history(): View
    {
        $consumer = Auth::guard('consumer')->user();

        $predictions = $this->buildReorderPredictions($consumer);
        $dueProductUnitIds = collect($predictions['product_predictions'])
            ->filter(fn(array $row) => (bool) $row['should_reorder_soon'])
            ->pluck('product_unit_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $dueServiceIds = collect($predictions['service_predictions'])
            ->filter(fn(array $row) => (bool) $row['should_reorder_soon'])
            ->pluck('service_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $productOrders = Order::query()
            ->with(['items.product:id,name'])
            ->where('buyer_type', Order::BUYER_TYPE_CONSUMER)
            ->where('buyer_id', $consumer->id)
            ->whereIn('status', [
                Order::STATUS_DELIVERED,
                Order::STATUS_CANCELLED,
            ])
            ->latest()
            ->paginate(15, ['*'], 'product_orders_page');

        $serviceOrders = WorkshopServiceOrder::query()
            ->with(['service:id,name', 'workshop:id,name'])
            ->where(function ($query) use ($consumer) {
                $query->where('consumer_id', (int) $consumer->id)
                    ->orWhere(function ($legacy) use ($consumer) {
                        $legacy->whereNull('consumer_id')
                            ->where('snapshot_customer_phone', $consumer->phone);
                    });
            })
            ->whereIn('status', [
                WorkshopServiceOrder::STATUS_COMPLETED,
                WorkshopServiceOrder::STATUS_CANCELLED,
            ])
            ->latest()
            ->paginate(15, ['*'], 'service_orders_page');

        $productReorderHints = [];
        foreach ($productOrders as $order) {
            $productReorderHints[(int) $order->id] = $order->status === Order::STATUS_DELIVERED
                && $order->items->contains(fn($item) => in_array((int) $item->product_unit_id, $dueProductUnitIds, true));
        }

        $serviceReorderHints = [];
        foreach ($serviceOrders as $order) {
            $serviceReorderHints[(int) $order->id] = $order->status === WorkshopServiceOrder::STATUS_COMPLETED
                && in_array((int) $order->service_id, $dueServiceIds, true);
        }

        return view('consumer.history.index', compact(
            'productOrders',
            'serviceOrders',
            'productReorderHints',
            'serviceReorderHints'
        ));
    }

    public function reorderProduct(Order $order): RedirectResponse
    {
        $consumer = Auth::guard('consumer')->user();

        abort_unless(
            (int) $order->buyer_id === (int) $consumer->id && (string) $order->buyer_type === Order::BUYER_TYPE_CONSUMER,
            403
        );

        if ($order->status !== Order::STATUS_DELIVERED) {
            return back()->withErrors(['order' => 'يمكن إعادة الطلبات المسلّمة فقط.']);
        }

        if (! $order->branch_id) {
            return back()->withErrors(['order' => 'لا يمكن إعادة هذا الطلب لعدم وجود محل تجاري مرتبطة.']);
        }

        $branch = Branch::query()->where('status', Account::STATUS_ACTIVE)->find($order->branch_id);
        if (! $branch) {
            return back()->withErrors(['order' => 'المحل التجاري غير متاحة حاليا.']);
        }

        $order->loadMissing('items');
        if ($order->items->isEmpty()) {
            return back()->withErrors(['order' => 'لا يحتوي الطلب السابق على عناصر قابلة للإعادة.']);
        }

        $itemsPayload = [];
        foreach ($order->items as $item) {
            $stock = BranchProductStock::query()
                ->where('branch_id', $branch->id)
                ->where('product_unit_id', (int) $item->product_unit_id)
                ->where('is_active', true)
                ->first();

            if (! $stock || (float) $stock->quantity < (float) $item->quantity) {
                return back()->withErrors([
                    'order' => 'تعذر إعادة الطلب: بعض العناصر لم تعد متوفرة بنفس الكمية.',
                ]);
            }

            $itemsPayload[] = [
                'product_id' => (int) $item->product_id,
                'product_unit_id' => (int) $item->product_unit_id,
                'product_variant_id' => $item->product_variant_id ? (int) $item->product_variant_id : null,
                'quantity' => (int) $item->quantity,
            ];
        }

        $systemUserId = $this->resolveSystemCreatorId();
        if ($systemUserId <= 0) {
            return back()->withErrors(['order' => 'لا يوجد حساب نظام فعّال لإنشاء الطلب.']);
        }

        $newOrder = $this->orderService->createOrder([
            'supplier_id' => $branch->supplier_id,
            'branch_id' => $branch->id,
            'buyer_type' => Order::BUYER_TYPE_CONSUMER,
            'buyer_id' => $consumer->id,
            'seller_type' => 'branch',
            'seller_id' => $branch->id,
            'created_by_agent_id' => $systemUserId,
            'items' => $itemsPayload,
        ]);

        return back()->with('status', 'تمت إعادة الطلب بنجاح. رقم الطلب الجديد #' . $newOrder->id);
    }

    public function reorderService(WorkshopServiceOrder $order): RedirectResponse
    {
        $consumer = Auth::guard('consumer')->user();

        $ownsOrder = (int) ($order->consumer_id ?? 0) === (int) $consumer->id
            || ((int) ($order->consumer_id ?? 0) === 0 && $order->customer_phone === $consumer->phone);

        abort_unless($ownsOrder, 403);

        if ($order->status !== WorkshopServiceOrder::STATUS_COMPLETED) {
            return back()->withErrors(['order' => 'يمكن إعادة طلبات الخدمات المكتملة فقط.']);
        }

        $workshop = Workshop::query()->where('status', Account::STATUS_ACTIVE)->find($order->workshop_id);
        if (! $workshop) {
            return back()->withErrors(['order' => 'الورشة غير متاحة حاليا.']);
        }

        $service = WorkshopService::query()
            ->where('workshop_id', $workshop->id)
            ->where('is_active', true)
            ->find($order->service_id);

        if (! $service) {
            return back()->withErrors(['order' => 'الخدمة لم تعد متاحة حاليا في الورشة.']);
        }

        $baseTotal = (float) $service->price;
        $commission = $this->commissionEngineService->calculate($baseTotal, 'workshop', (int) $workshop->id, null);

        $newOrder = WorkshopServiceOrder::query()->create([
            'workshop_id' => $workshop->id,
            'service_id' => $service->id,
            'appointment_id' => null,
            'order_number' => 'CSO-' . strtoupper(str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT)),
            'snapshot_customer_name' => $consumer->name,
            'snapshot_customer_phone' => $consumer->phone,
            'consumer_id' => (int) $consumer->id,
            'service_fee' => $baseTotal,
            'products_total' => 0,
            'total_amount' => $baseTotal,
            'commission_rule_id' => $commission['rule_id'],
            'commission_percent' => $commission['commission_percent'],
            'commission_value' => $commission['commission_value'],
            'platform_service_fee' => $commission['service_fee'],
            'platform_fixed_fee' => $commission['fixed_fee'],
            'payable_total' => $commission['final_amount'],
            'status' => WorkshopServiceOrder::STATUS_REQUESTED,
            'notes' => 'إعادة طلب من الطلب السابق ' . $order->order_number,
        ]);

        return back()->with('status', 'تمت إعادة طلب الخدمة بنجاح. رقم الطلب الجديد ' . $newOrder->order_number);
    }

    public function addresses(): View
    {
        $consumer = Auth::guard('consumer')->user();

        $addresses = ConsumerAddress::query()
            ->where('consumer_id', $consumer->id)
            ->latest()
            ->get();

        return view('consumer.addresses.index', compact('consumer', 'addresses'));
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $consumer = Auth::guard('consumer')->user();

        $data = $request->validate([
            'label' => ['required', 'string', 'max:60'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address_line' => ['required', 'string', 'max:500'],
            'gps_location' => ['nullable', 'string', 'max:120'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $isDefault = (bool) ($data['is_default'] ?? false);

        if ($isDefault) {
            ConsumerAddress::query()
                ->where('consumer_id', $consumer->id)
                ->update(['is_default' => false]);
        }

        ConsumerAddress::query()->create([
            'consumer_id' => $consumer->id,
            'label' => $data['label'],
            'contact_name' => $data['contact_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address_line' => $data['address_line'],
            'gps_location' => $data['gps_location'] ?? null,
            'is_default' => $isDefault,
        ]);

        return back()->with('status', 'تمت إضافة العنوان بنجاح.');
    }

    public function updateAddress(Request $request, ConsumerAddress $address): RedirectResponse
    {
        $consumer = Auth::guard('consumer')->user();
        abort_unless($address->consumer_id === $consumer->id, 403);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:60'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address_line' => ['required', 'string', 'max:500'],
            'gps_location' => ['nullable', 'string', 'max:120'],
        ]);

        $address->update($data);

        return back()->with('status', 'تم تحديث العنوان بنجاح.');
    }

    public function setDefaultAddress(ConsumerAddress $address): RedirectResponse
    {
        $consumer = Auth::guard('consumer')->user();
        abort_unless($address->consumer_id === $consumer->id, 403);

        ConsumerAddress::query()
            ->where('consumer_id', $consumer->id)
            ->update(['is_default' => false]);

        $address->update(['is_default' => true]);

        return back()->with('status', 'تم تعيين العنوان الافتراضي بنجاح.');
    }

    public function destroyAddress(ConsumerAddress $address): RedirectResponse
    {
        $consumer = Auth::guard('consumer')->user();
        abort_unless($address->consumer_id === $consumer->id, 403);

        $address->delete();

        return back()->with('status', 'تم حذف العنوان.');
    }

    public function ratings(): View
    {
        $consumer = Auth::guard('consumer')->user();

        $ratings = ConsumerRating::query()
            ->where('consumer_id', $consumer->id)
            ->latest()
            ->get();

        $posStores = Branch::query()->where('status', Account::STATUS_ACTIVE)->orderBy('name')->get(['id', 'name']);
        $workshops = Workshop::query()->where('status', Account::STATUS_ACTIVE)->orderBy('name')->get(['id', 'name']);

        $posStoreNames = $posStores->pluck('name', 'id');
        $workshopNames = $workshops->pluck('name', 'id');

        return view('consumer.ratings.index', compact('ratings', 'posStores', 'workshops', 'posStoreNames', 'workshopNames'));
    }

    public function storeRating(Request $request): RedirectResponse
    {
        $consumer = Auth::guard('consumer')->user();

        $data = $request->validate([
            'store_type' => ['required', 'in:pos,workshop'],
            'store_id' => ['required', 'integer'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['store_type'] === 'pos') {
            Branch::query()->where('status', Account::STATUS_ACTIVE)->findOrFail((int) $data['store_id']);

            $eligibleOrder = Order::query()
                ->where('buyer_type', Order::BUYER_TYPE_CONSUMER)
                ->where('buyer_id', $consumer->id)
                ->where('branch_id', (int) $data['store_id'])
                ->where('status', Order::STATUS_DELIVERED)
                ->latest()
                ->first();
        } else {
            Workshop::query()->where('status', Account::STATUS_ACTIVE)->findOrFail((int) $data['store_id']);

            $eligibleOrder = WorkshopServiceOrder::query()
                ->where('workshop_id', (int) $data['store_id'])
                ->where(function ($query) use ($consumer) {
                    $query->where('consumer_id', (int) $consumer->id)
                        ->orWhere(function ($legacy) use ($consumer) {
                            $legacy->whereNull('consumer_id')
                                ->where('snapshot_customer_phone', $consumer->phone);
                        });
                })
                ->where('status', WorkshopServiceOrder::STATUS_COMPLETED)
                ->latest()
                ->first();
        }

        if (! $eligibleOrder) {
            return back()->withErrors([
                'rating' => 'لا يمكنك التقييم قبل إكمال طلب فعلي من هذا المتجر.',
            ])->withInput();
        }

        ConsumerRating::query()->create([
            'consumer_id' => $consumer->id,
            'store_type' => $data['store_type'],
            'store_id' => (int) $data['store_id'],
            'order_id' => $data['store_type'] === 'pos' ? (int) $eligibleOrder->id : null,
            'rating' => (int) $data['rating'],
            'review' => $data['review'] ?? null,
        ]);

        return back()->with('status', 'تم حفظ التقييم بنجاح.');
    }

    public function profile(): View
    {
        $consumer = Auth::guard('consumer')->user();
        $this->syncLoyaltyPoints($consumer);

        $vehicles = ConsumerVehicleProfile::query()
            ->where('consumer_id', $consumer->id)
            ->orderByDesc('is_default')
            ->latest('id')
            ->get();

        $loyaltyBalance = $this->loyaltyBalance((int) $consumer->id);

        return view('consumer.profile.index', compact('consumer', 'vehicles', 'loyaltyBalance'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $consumer = Auth::guard('consumer')->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30', new UniqueUserContact('phone', [UniqueUserContact::ignore('consumers', $consumer->id)])],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'gps_location' => ['nullable', 'string', 'max:120'],
        ]);

        $consumer->update($data);

        return back()->with('status', 'تم تحديث بيانات الحساب بنجاح.');
    }

    public function storeVehicle(Request $request): RedirectResponse
    {
        $consumer = Auth::guard('consumer')->user();

        $data = $request->validate([
            'nickname' => ['nullable', 'string', 'max:80'],
            'plate_number' => ['nullable', 'string', 'max:80'],
            'brand' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:80'],
            'production_year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'last_odometer_km' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ((bool) ($data['is_default'] ?? false)) {
            ConsumerVehicleProfile::query()
                ->where('consumer_id', $consumer->id)
                ->update(['is_default' => false]);
        }

        ConsumerVehicleProfile::query()->create([
            'consumer_id' => $consumer->id,
            'nickname' => $data['nickname'] ?? null,
            'plate_number' => $data['plate_number'] ?? null,
            'brand' => $data['brand'] ?? null,
            'model' => $data['model'] ?? null,
            'production_year' => $data['production_year'] ?? null,
            'last_odometer_km' => $data['last_odometer_km'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_default' => (bool) ($data['is_default'] ?? false),
        ]);

        return back()->with('status', 'تمت إضافة مركبة المستهلك بنجاح.');
    }

    public function updateVehicle(Request $request, ConsumerVehicleProfile $vehicle): RedirectResponse
    {
        $consumer = Auth::guard('consumer')->user();
        abort_unless((int) $vehicle->consumer_id === (int) $consumer->id, 403);

        $data = $request->validate([
            'nickname' => ['nullable', 'string', 'max:80'],
            'plate_number' => ['nullable', 'string', 'max:80'],
            'brand' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:80'],
            'production_year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'last_odometer_km' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ((bool) ($data['is_default'] ?? false)) {
            ConsumerVehicleProfile::query()
                ->where('consumer_id', $consumer->id)
                ->update(['is_default' => false]);
        }

        $vehicle->update([
            'nickname' => $data['nickname'] ?? null,
            'plate_number' => $data['plate_number'] ?? null,
            'brand' => $data['brand'] ?? null,
            'model' => $data['model'] ?? null,
            'production_year' => $data['production_year'] ?? null,
            'last_odometer_km' => $data['last_odometer_km'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_default' => (bool) ($data['is_default'] ?? false),
        ]);

        return back()->with('status', 'تم تحديث بيانات المركبة.');
    }

    public function destroyVehicle(ConsumerVehicleProfile $vehicle): RedirectResponse
    {
        $consumer = Auth::guard('consumer')->user();
        abort_unless((int) $vehicle->consumer_id === (int) $consumer->id, 403);

        $vehicle->delete();

        return back()->with('status', 'تم حذف المركبة.');
    }

    public function setDefaultVehicle(ConsumerVehicleProfile $vehicle): RedirectResponse
    {
        $consumer = Auth::guard('consumer')->user();
        abort_unless((int) $vehicle->consumer_id === (int) $consumer->id, 403);

        ConsumerVehicleProfile::query()
            ->where('consumer_id', $consumer->id)
            ->update(['is_default' => false]);

        $vehicle->update(['is_default' => true]);

        return back()->with('status', 'تم تعيين المركبة الافتراضية.');
    }

    public function markAllAlertsAsRead(): RedirectResponse
    {
        $consumer = Auth::guard('consumer')->user();

        $count = $this->webAlertService->markAllAsRead('consumer', (int) $consumer->id);

        return back()->with('status', 'تم تعليم ' . $count . ' تنبيه كمقروء.');
    }

    private function loyaltyBalance(int $consumerId): int
    {
        $credit = (int) ConsumerLoyaltyPoint::query()
            ->where('consumer_id', $consumerId)
            ->where('direction', 'credit')
            ->sum('points');

        $debit = (int) ConsumerLoyaltyPoint::query()
            ->where('consumer_id', $consumerId)
            ->where('direction', 'debit')
            ->sum('points');

        return $credit - $debit;
    }

    private function syncLoyaltyPoints($consumer): void
    {
        $consumerId = (int) $consumer->id;

        $orders = Order::query()
            ->where('buyer_type', Order::BUYER_TYPE_CONSUMER)
            ->where('buyer_id', $consumerId)
            ->where('status', Order::STATUS_DELIVERED)
            ->get(['id', 'total_price', 'payable_total', 'updated_at']);

        foreach ($orders as $order) {
            $exists = ConsumerLoyaltyPoint::query()
                ->where('consumer_id', $consumerId)
                ->where('source_type', 'order')
                ->where('source_id', (int) $order->id)
                ->exists();

            if ($exists) {
                continue;
            }

            $amount = (float) ($order->payable_total ?? $order->total_price ?? 0);
            $points = max((int) floor($amount / 100), 1);

            ConsumerLoyaltyPoint::query()->create([
                'consumer_id' => $consumerId,
                'source_type' => 'order',
                'source_id' => (int) $order->id,
                'points' => $points,
                'direction' => 'credit',
                'note' => 'نقاط من طلب مكتمل #' . $order->id,
                'awarded_at' => $order->updated_at ?? now(),
            ]);
        }

        $serviceOrders = WorkshopServiceOrder::query()
            ->where(function ($query) use ($consumer) {
                $query->where('consumer_id', (int) $consumer->id)
                    ->orWhere(function ($legacy) use ($consumer) {
                        $legacy->whereNull('consumer_id')
                            ->where('snapshot_customer_phone', $consumer->phone);
                    });
            })
            ->where('status', WorkshopServiceOrder::STATUS_COMPLETED)
            ->get(['id', 'total_amount', 'payable_total', 'updated_at']);

        foreach ($serviceOrders as $serviceOrder) {
            $exists = ConsumerLoyaltyPoint::query()
                ->where('consumer_id', $consumerId)
                ->where('source_type', 'service_order')
                ->where('source_id', (int) $serviceOrder->id)
                ->exists();

            if ($exists) {
                continue;
            }

            $amount = (float) ($serviceOrder->payable_total ?? $serviceOrder->total_amount ?? 0);
            $points = max((int) floor($amount / 100), 1);

            ConsumerLoyaltyPoint::query()->create([
                'consumer_id' => $consumerId,
                'source_type' => 'service_order',
                'source_id' => (int) $serviceOrder->id,
                'points' => $points,
                'direction' => 'credit',
                'note' => 'نقاط من خدمة مكتملة #' . $serviceOrder->id,
                'awarded_at' => $serviceOrder->updated_at ?? now(),
            ]);
        }
    }

    private function resolveConsumerCoordinates($consumer): ?array
    {
        $coordinates = $this->parseCoordinates($consumer->gps_location ?? null);
        if ($coordinates !== null) {
            return $coordinates;
        }

        $defaultAddress = ConsumerAddress::query()
            ->where('consumer_id', $consumer->id)
            ->where('is_default', true)
            ->value('gps_location');

        return $this->parseCoordinates($defaultAddress);
    }

    private function resolveOrderAddressForFulfillment($consumer, Branch $branch, string $fulfillment): string
    {
        if ($fulfillment === 'pickup') {
            return 'استلام من المتجر: ' . $branch->name;
        }

        $defaultAddress = ConsumerAddress::query()
            ->where('consumer_id', $consumer->id)
            ->where('is_default', true)
            ->value('address_line');

        $deliveryAddress = trim((string) ($defaultAddress ?: $consumer->address));
        if ($deliveryAddress === '') {
            abort(422, 'يرجى إضافة عنوان توصيل في الحساب أو العناوين أولاً.');
        }

        return $deliveryAddress;
    }

    private function ensureNearbyAccess(string $storeGpsLocation, ?array $originCoordinates): void
    {
        if ($originCoordinates === null) {
            return;
        }

        $storeCoordinates = $this->parseCoordinates($storeGpsLocation);
        if ($storeCoordinates === null) {
            return;
        }

        $distance = $this->haversineKm($originCoordinates, $storeCoordinates);
        abort_if($distance > 80, 404);
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

    private function resolveSystemCreatorId(): int
    {
        $agentId = (int) (\App\Models\Agent::query()->orderBy('id')->value('id') ?? 0);
        if ($agentId > 0) {
            return $agentId;
        }

        return (int) (\App\Models\Admin::query()->orderBy('id')->value('id') ?? 0);
    }

    private function buildReorderPredictions($consumer): array
    {
        $productOrders = Order::query()
            ->with(['items.product:id,name'])
            ->where('buyer_type', Order::BUYER_TYPE_CONSUMER)
            ->where('buyer_id', $consumer->id)
            ->where('status', Order::STATUS_DELIVERED)
            ->where('created_at', '>=', now()->subDays(180))
            ->orderBy('created_at')
            ->get();

        $productEvents = collect();
        foreach ($productOrders as $order) {
            foreach ($order->items as $item) {
                $productEvents->push([
                    'branch_id' => (int) $order->branch_id,
                    'product_unit_id' => (int) $item->product_unit_id,
                    'product_name' => (string) ($item->product?->name ?? 'منتج'),
                    'quantity' => (float) $item->quantity,
                    'ordered_at' => $order->created_at,
                ]);
            }
        }

        $reorderProductPredictions = $productEvents
            ->groupBy(fn(array $event) => $event['branch_id'] . ':' . $event['product_unit_id'])
            ->map(function (Collection $group): array {
                $sorted = $group->sortBy('ordered_at')->values();
                $count = (int) $sorted->count();
                $last = $sorted->last();

                $avgIntervalDays = null;
                if ($count > 1) {
                    $intervals = [];
                    for ($i = 1; $i < $count; $i++) {
                        $intervals[] = $sorted[$i - 1]['ordered_at']->diffInDays($sorted[$i]['ordered_at']);
                    }
                    $avgIntervalDays = count($intervals) > 0 ? (float) (array_sum($intervals) / count($intervals)) : null;
                }

                $daysSinceLast = $last ? (int) $last['ordered_at']->diffInDays(now()) : 0;
                $shouldReorderSoon = $avgIntervalDays !== null && $daysSinceLast >= ($avgIntervalDays * 0.8);

                return [
                    'branch_id' => (int) $last['branch_id'],
                    'product_unit_id' => (int) $last['product_unit_id'],
                    'product_name' => (string) $last['product_name'],
                    'ordered_count' => $count,
                    'total_quantity' => (float) $sorted->sum('quantity'),
                    'days_since_last' => $daysSinceLast,
                    'avg_interval_days' => $avgIntervalDays,
                    'predicted_next_reorder_in_days' => $avgIntervalDays !== null
                        ? round(max($avgIntervalDays - $daysSinceLast, 0), 1)
                        : null,
                    'should_reorder_soon' => $shouldReorderSoon,
                ];
            })
            ->sortByDesc(fn(array $row) => (($row['should_reorder_soon'] ? 1000 : 0) + $row['ordered_count']))
            ->values()
            ->take(6);

        $serviceOrders = WorkshopServiceOrder::query()
            ->with('service:id,name')
            ->where(function ($query) use ($consumer) {
                $query->where('consumer_id', (int) $consumer->id)
                    ->orWhere(function ($legacy) use ($consumer) {
                        $legacy->whereNull('consumer_id')
                            ->where('snapshot_customer_phone', $consumer->phone);
                    });
            })
            ->where('status', WorkshopServiceOrder::STATUS_COMPLETED)
            ->where('created_at', '>=', now()->subDays(180))
            ->orderBy('created_at')
            ->get();

        $reorderServicePredictions = $serviceOrders
            ->groupBy('service_id')
            ->map(function (Collection $group): array {
                $sorted = $group->sortBy('created_at')->values();
                $count = (int) $sorted->count();
                $last = $sorted->last();

                $avgIntervalDays = null;
                if ($count > 1) {
                    $intervals = [];
                    for ($i = 1; $i < $count; $i++) {
                        $intervals[] = $sorted[$i - 1]->created_at->diffInDays($sorted[$i]->created_at);
                    }
                    $avgIntervalDays = count($intervals) > 0 ? (float) (array_sum($intervals) / count($intervals)) : null;
                }

                $daysSinceLast = $last ? (int) $last->created_at->diffInDays(now()) : 0;
                $shouldReorderSoon = $avgIntervalDays !== null && $daysSinceLast >= ($avgIntervalDays * 0.8);

                return [
                    'service_id' => (int) $last->service_id,
                    'service_name' => (string) ($last->service?->name ?? 'خدمة'),
                    'ordered_count' => $count,
                    'days_since_last' => $daysSinceLast,
                    'avg_interval_days' => $avgIntervalDays,
                    'predicted_next_reorder_in_days' => $avgIntervalDays !== null
                        ? round(max($avgIntervalDays - $daysSinceLast, 0), 1)
                        : null,
                    'should_reorder_soon' => $shouldReorderSoon,
                ];
            })
            ->sortByDesc(fn(array $row) => (($row['should_reorder_soon'] ? 1000 : 0) + $row['ordered_count']))
            ->values()
            ->take(6);

        return [
            'product_predictions' => $reorderProductPredictions,
            'service_predictions' => $reorderServicePredictions,
        ];
    }

    private function generateConsumerSmartAlerts($consumer, array $predictions): void
    {
        $today = now()->toDateString();

        $lastProductActivityAt = Order::query()
            ->where('buyer_type', Order::BUYER_TYPE_CONSUMER)
            ->where('buyer_id', $consumer->id)
            ->max('updated_at');

        $lastServiceActivityAt = WorkshopServiceOrder::query()
            ->where(function ($query) use ($consumer) {
                $query->where('consumer_id', (int) $consumer->id)
                    ->orWhere(function ($legacy) use ($consumer) {
                        $legacy->whereNull('consumer_id')
                            ->where('snapshot_customer_phone', $consumer->phone);
                    });
            })
            ->max('updated_at');

        $lastActivityAt = collect([$lastProductActivityAt, $lastServiceActivityAt])
            ->filter()
            ->map(fn($value) => \Illuminate\Support\Carbon::parse($value))
            ->sortDesc()
            ->first();

        if ($lastActivityAt && $lastActivityAt->lte(now()->subDays(30))) {
            $title = 'تنبيه نشاط المستهلك';
            $body = 'لم تقم بأي طلب منذ ' . $lastActivityAt->diffInDays(now()) . ' يوم. تصفّح العروض الحالية.';

            $exists = WebAlert::query()
                ->where('recipient_type', 'consumer')
                ->where('recipient_id', $consumer->id)
                ->whereDate('created_at', $today)
                ->where('title', $title)
                ->where('body', $body)
                ->exists();

            if (! $exists) {
                $this->webAlertService->create(
                    'consumer',
                    (int) $consumer->id,
                    $title,
                    $body,
                    [
                        'type' => 'consumer_inactivity_alert',
                        'days_inactive' => (int) $lastActivityAt->diffInDays(now()),
                    ]
                );
            }
        }

        $dueProduct = collect($predictions['product_predictions'] ?? [])->first(fn(array $row) => (bool) $row['should_reorder_soon']);
        if ($dueProduct) {
            $title = 'اقتراح إعادة طلب ذكي';
            $body = 'قد تحتاج لإعادة طلب المنتج ' . $dueProduct['product_name'] . ' قريبًا.';

            $exists = WebAlert::query()
                ->where('recipient_type', 'consumer')
                ->where('recipient_id', $consumer->id)
                ->whereDate('created_at', $today)
                ->where('title', $title)
                ->where('body', $body)
                ->exists();

            if (! $exists) {
                $this->webAlertService->create(
                    'consumer',
                    (int) $consumer->id,
                    $title,
                    $body,
                    [
                        'type' => 'consumer_reorder_prediction',
                        'product_name' => $dueProduct['product_name'],
                        'predicted_in_days' => $dueProduct['predicted_next_reorder_in_days'],
                    ]
                );
            }
        }
    }
}
