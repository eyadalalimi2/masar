<?php

namespace App\Services\Orders;

use App\Models\Catalog\Product;
use App\Models\Catalog\ProductConfiguration;
use App\Models\Catalog\ProductConfigurationUnit;
use App\Models\Catalog\ProductUnit;
use App\Models\Catalog\ProductVariant;
use App\Models\Catalog\ProductVariantUnit;
use App\Models\Pos;
use App\Models\Customer\Workshop;
use App\Models\Customer\Consumer;
use App\Models\Customer\Customer;
use App\Models\Distribution\BranchAccount;
use App\Models\Distribution\Branch;
use App\Models\Distribution\Distributor;
use App\Modules\Orders\Services\OrdersDomainService;
use App\Services\Distribution\BranchInventoryService;
use App\Services\Notifications\WebAlertService;
use App\Services\Pricing\CommissionEngineService;
use App\Traits\Notifications\SendNotification;
use App\Models\Orders\Order;
use App\Models\Supplier\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderService
{
    use SendNotification;

    public function __construct(
        private readonly OrdersDomainService $ordersDomainService,
        private readonly WebAlertService $webAlertService,
        private readonly CommissionEngineService $commissionEngineService,
        private readonly BranchInventoryService $branchInventoryService,
    ) {}

    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            [$buyerType, $buyerId] = $this->resolveBuyerPayload($data);
            $pricingMode = $buyerType === Order::BUYER_TYPE_CONSUMER ? 'b2c' : 'b2b';

            $calculated = $this->calculateTotal($data['items'], $pricingMode, (int) $data['supplier_id']);
            $baseTotal = (float) ($calculated['total'] ?? 0);
            $entityType = $this->resolveCommissionEntityType((string) ($data['seller_type'] ?? 'global'));
            $entityId = isset($data['seller_id']) ? (int) $data['seller_id'] : null;
            $commission = $this->commissionEngineService->calculate(
                $baseTotal,
                $entityType,
                $entityId,
                null
            );

            $customer = null;
            $consumer = null;

            if ($buyerType === Order::BUYER_TYPE_CUSTOMER) {
                $customer = Customer::query()->findOrFail($buyerId);
            }

            if ($buyerType === Order::BUYER_TYPE_CONSUMER) {
                $consumer = Consumer::query()->findOrFail($buyerId);
            }

            $order = $this->ordersDomainService->ordersQuery()->create([
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'distributor_id' => $data['distributor_id'] ?? null,
                'buyer_type' => $buyerType,
                'buyer_id' => $buyerId,
                'seller_type' => $data['seller_type'],
                'seller_id' => (int) $data['seller_id'],
                'snapshot_customer_name' => $customer?->name ?? $consumer?->name,
                'snapshot_customer_phone' => $customer?->phone ?? $consumer?->phone,
                'snapshot_customer_address' => isset($data['customer_address_override']) && is_string($data['customer_address_override'])
                    ? $data['customer_address_override']
                    : ($customer?->address ?? $consumer?->address),
                'total_price' => $baseTotal,
                'commission_rule_id' => $commission['rule_id'],
                'commission_percent' => $commission['commission_percent'],
                'commission_value' => $commission['commission_value'],
                'platform_service_fee' => $commission['service_fee'],
                'platform_fixed_fee' => $commission['fixed_fee'],
                'payable_total' => $commission['final_amount'],
                'status' => Order::STATUS_PENDING,
                'created_by_agent_id' => (int) ($data['created_by_agent_id'] ?? $data['created_by']),
            ]);

            foreach ($calculated['items'] as $item) {
                $order->items()->create($item);
            }

            $order = $order->fresh(['supplier', 'branch', 'distributor', 'buyer', 'items.product', 'items.productUnit.unit', 'items.productVariant.variantValue.type']);

            $sellerUser = $this->resolveSellerUser($order->seller_type, (int) $order->seller_id);
            $this->sendToUser($sellerUser, 'طلب جديد', 'تم إنشاء طلب جديد برقم #' . $order->id, [
                'type' => 'new_order',
                'order_id' => $order->id,
                'status' => $order->status,
            ]);

            return $order;
        });
    }

    private function resolveCommissionEntityType(string $sellerType): string
    {
        return in_array($sellerType, ['supplier', 'branch', 'distributor', 'customer'], true)
            ? $sellerType
            : 'global';
    }

    public function calculateTotal(array $items, string $customerType, int $supplierId): array
    {
        $result = [
            'items' => [],
            'total' => 0,
        ];

        $productIds = collect($items)->pluck('product_id')->filter()->map(fn($id) => (int) $id)->unique()->values();
        $productUnitIds = collect($items)->pluck('product_unit_id')->filter()->map(fn($id) => (int) $id)->unique()->values();
        $productConfigurationIds = collect($items)->pluck('product_configuration_id')->filter()->map(fn($id) => (int) $id)->unique()->values();
        $productVariantIds = collect($items)->pluck('product_variant_id')->filter()->map(fn($id) => (int) $id)->unique()->values();

        $products = Product::query()
            ->where('supplier_id', $supplierId)
            ->whereIn('id', $productIds->all())
            ->get()
            ->keyBy('id');

        $productUnits = ProductUnit::query()
            ->whereIn('id', $productUnitIds->all())
            ->get()
            ->keyBy('id');

        $productConfigurations = $productConfigurationIds->isEmpty()
            ? collect()
            : ProductConfiguration::query()
            ->whereIn('id', $productConfigurationIds->all())
            ->get()
            ->keyBy('id');

        $productVariants = $productVariantIds->isEmpty()
            ? collect()
            : ProductVariant::query()
            ->whereIn('id', $productVariantIds->all())
            ->get()
            ->keyBy('id');

        $unitIds = $productUnits->pluck('unit_id')->map(fn($id) => (int) $id)->unique()->values();

        $configurationUnits = $productConfigurationIds->isEmpty() || $unitIds->isEmpty()
            ? collect()
            : ProductConfigurationUnit::query()
            ->whereIn('product_configuration_id', $productConfigurationIds->all())
            ->whereIn('unit_id', $unitIds->all())
            ->get()
            ->keyBy(fn(ProductConfigurationUnit $unit) => $unit->product_configuration_id . ':' . $unit->unit_id);

        $variantUnits = $productVariantIds->isEmpty() || $unitIds->isEmpty()
            ? collect()
            : ProductVariantUnit::query()
            ->whereIn('product_variant_id', $productVariantIds->all())
            ->whereIn('unit_id', $unitIds->all())
            ->get()
            ->keyBy(fn(ProductVariantUnit $unit) => $unit->product_variant_id . ':' . $unit->unit_id);

        foreach ($items as $row) {
            $product = $products->get((int) $row['product_id']);
            if (! $product) {
                abort(404);
            }

            $productUnit = $productUnits->get((int) $row['product_unit_id']);
            if (! $productUnit || (int) $productUnit->product_id !== (int) $product->id) {
                abort(404);
            }

            $productConfiguration = null;
            if (! empty($row['product_configuration_id'])) {
                $productConfiguration = $productConfigurations->get((int) $row['product_configuration_id']);
                if (! $productConfiguration || (int) $productConfiguration->product_id !== (int) $product->id) {
                    abort(404);
                }
            }

            $productVariant = null;
            if ($productConfiguration === null && ! empty($row['product_variant_id'])) {
                $productVariant = $productVariants->get((int) $row['product_variant_id']);
                if (! $productVariant || (int) $productVariant->product_id !== (int) $product->id) {
                    abort(404);
                }
            }

            $quantity = (int) $row['quantity'];

            $configurationUnitPrice = null;
            if ($productConfiguration) {
                $configurationUnit = $configurationUnits->get((int) $productConfiguration->id . ':' . (int) $productUnit->unit_id);
                if (! $configurationUnit) {
                    abort(422, 'الوحدة المختارة غير متاحة داخل التهيئة المحددة للمنتج.');
                }

                $configurationUnitPrice = $customerType === 'b2b'
                    ? (float) $configurationUnit->wholesale_price
                    : (float) $configurationUnit->retail_price;
            }

            $variantUnitPrice = null;
            if ($productVariant) {
                $variantUnit = $variantUnits->get((int) $productVariant->id . ':' . (int) $productUnit->unit_id);
                if ($variantUnit) {
                    $variantUnitPrice = $customerType === 'b2b'
                        ? (float) $variantUnit->wholesale_price
                        : (float) $variantUnit->retail_price;
                }
            }

            $unitPrice = $configurationUnitPrice ?? $variantUnitPrice ?? ($customerType === 'b2b'
                ? (float) $productUnit->wholesale_price
                : (float) $productUnit->retail_price);

            $lineTotal = $unitPrice * $quantity;

            $result['items'][] = [
                'product_id' => $product->id,
                'product_unit_id' => $productUnit->id,
                'product_variant_id' => $productVariant?->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $lineTotal,
            ];

            $result['total'] += $lineTotal;
        }

        return $result;
    }

    public function assignDistributor(Order $order, ?int $distributorId): Order
    {
        if ($distributorId) {
            Distributor::where('supplier_id', $order->supplier_id)->findOrFail($distributorId);
        }

        $nextStatus = $order->status;
        if ($distributorId && in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_APPROVED, Order::STATUS_ASSIGNED], true)) {
            $nextStatus = Order::STATUS_ASSIGNED;
        }
        if (! $distributorId && $order->status === Order::STATUS_ASSIGNED) {
            $nextStatus = Order::STATUS_APPROVED;
        }

        $order->update([
            'distributor_id' => $distributorId,
            'distributor_stage' => $distributorId ? Order::STATUS_ASSIGNED : null,
            'status' => $nextStatus,
        ]);

        $order = $order->fresh(['distributor.account']);

        if ($order->distributor?->account) {
            $this->sendToUser(
                $order->distributor->account,
                'تم تعيين مندوب',
                'تم تعيينك على الطلب #' . $order->id,
                [
                    'type' => 'distributor_assigned',
                    'order_id' => $order->id,
                ]
            );

            $this->webAlertService->create(
                'distributor_account',
                (int) $order->distributor->account->id,
                'طلب جديد مخصص لك',
                'تم تعيينك على الطلب #' . $order->id,
                [
                    'type' => 'distributor_assigned',
                    'order_id' => $order->id,
                ]
            );
        }

        return $order;
    }

    public function changeStatus(Order $order, string $status): Order
    {
        $allowed = [
            Order::STATUS_PENDING,
            Order::STATUS_APPROVED,
            Order::STATUS_ASSIGNED,
            Order::STATUS_OUT_FOR_DELIVERY,
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED,
        ];

        if (! in_array($status, $allowed, true)) {
            abort(422, 'حالة الطلب غير صحيحة.');
        }

        $previousStatus = null;

        $order = DB::transaction(function () use ($order, $status, &$previousStatus): Order {
            $lockedOrder = $this->ordersDomainService->ordersQuery()
                ->with(['branch', 'items.product', 'items.productUnit', 'distributor.account', 'customer', 'consumer'])
                ->lockForUpdate()
                ->findOrFail((int) $order->id);

            $previousStatus = (string) $lockedOrder->status;

            if (
                (int) ($lockedOrder->branch_id ?? 0) > 0
                && in_array($status, [Order::STATUS_APPROVED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED], true)
                && $previousStatus !== $status
            ) {
                $branch = $lockedOrder->branch;

                if (! $branch instanceof Branch) {
                    abort(422, 'لا يمكن تحديث الحالة: الفرع غير متاح.');
                }

                $this->branchInventoryService->ensureOrderStockAvailable($branch, $lockedOrder);
                $this->branchInventoryService->deductOrderStock($branch, $lockedOrder);
            }

            $lockedOrder->update(['status' => $status]);

            if ($previousStatus !== $status && Schema::hasTable('order_status_histories')) {
                [$actorGuard, $actorId] = $this->resolveActorContext();

                $this->ordersDomainService->orderStatusHistoriesQuery()->create([
                    'order_id' => (int) $lockedOrder->id,
                    'from_status' => $previousStatus,
                    'to_status' => $status,
                    'actor_guard' => $actorGuard,
                    'actor_id' => $actorId,
                ]);
            }

            return $lockedOrder;
        });

        if ($order->distributor?->account && $previousStatus !== $status && in_array($status, [Order::STATUS_APPROVED, Order::STATUS_OUT_FOR_DELIVERY], true)) {
            $this->webAlertService->create(
                'distributor_account',
                (int) $order->distributor->account->id,
                'تعديل على الطلب',
                'تم تحديث حالة الطلب #' . $order->id . ' إلى ' . $status,
                [
                    'type' => 'order_updated',
                    'order_id' => $order->id,
                    'status' => $status,
                ]
            );
        }

        if ($status === Order::STATUS_CANCELLED && $order->distributor?->account) {
            $this->webAlertService->create(
                'distributor_account',
                (int) $order->distributor->account->id,
                'تم إلغاء الطلب',
                'تم إلغاء الطلب #' . $order->id,
                [
                    'type' => 'order_cancelled',
                    'order_id' => $order->id,
                    'status' => Order::STATUS_CANCELLED,
                ]
            );
        }

        if (in_array($status, [Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED], true)) {
            $targetPhone = $order->isBusinessBuyer()
                ? $order->customer?->phone
                : $order->consumer?->phone;

            if (! $targetPhone) {
                $targetPhone = $order->customer_phone;
            }

            $customer = null;

            if ($order->isBusinessBuyer() && (int) ($order->buyer_id ?? 0) > 0) {
                $customer = Pos::query()->where('owner_id', (int) $order->buyer_id)->first()
                    ?? Workshop::query()->where('owner_id', (int) $order->buyer_id)->first()
                    ?? Customer::query()->whereKey((int) $order->buyer_id)->first();
            }

            if (! $customer && $order->isConsumerBuyer() && $targetPhone) {
                $customer = Consumer::query()->where('phone', $targetPhone)->first();
            }

            if ($customer) {
                $title = $status === Order::STATUS_OUT_FOR_DELIVERY ? 'الطلب في الطريق' : 'تم التسليم';
                $body = $status === Order::STATUS_OUT_FOR_DELIVERY
                    ? 'الطلب #' . $order->id . ' أصبح في الطريق إليك.'
                    : 'تم تسليم الطلب #' . $order->id . ' بنجاح.';

                $this->sendToUser($customer, $title, $body, [
                    'type' => $status === Order::STATUS_OUT_FOR_DELIVERY ? 'order_out_for_delivery' : 'order_delivered',
                    'order_id' => $order->id,
                    'status' => $status,
                ]);
            }
        }

        return $order->fresh(['buyer']);
    }

    private function resolveBuyerPayload(array $data): array
    {
        if (! empty($data['buyer_type']) && ! empty($data['buyer_id'])) {
            return [(string) $data['buyer_type'], (int) $data['buyer_id']];
        }

        if (($data['customer_type'] ?? null) === 'b2c' && ! empty($data['consumer_id'])) {
            return [Order::BUYER_TYPE_CONSUMER, (int) $data['consumer_id']];
        }

        if (($data['customer_type'] ?? null) === 'b2b' && ! empty($data['customer_id'])) {
            return [Order::BUYER_TYPE_CUSTOMER, (int) $data['customer_id']];
        }

        abort(422, 'بيانات المشتري غير مكتملة.');
    }

    private function resolveSellerUser(?string $sellerType, int $sellerId): mixed
    {
        if (! $sellerType || $sellerId <= 0) {
            return null;
        }

        return match ($sellerType) {
            'supplier' => Supplier::query()->with('agentAccount')->find($sellerId)?->agentAccount,
            'branch' => ($branch = Branch::query()->find($sellerId))
                ? BranchAccount::query()->where('owner_id', $branch->id)->first()
                : null,
            'distributor' => Distributor::query()->with('account')->find($sellerId)?->account,
            'customer' => ($customer = Customer::query()->find($sellerId))
                ? Customer::query()->where('id', $customer->id)->first()
                : null,
            default => null,
        };
    }

    private function resolveActorContext(): array
    {
        foreach (['admin', 'agent', 'branch', 'distributor', 'customer', 'consumer', 'pos', 'workshop'] as $guard) {
            $id = Auth::guard($guard)->id();

            if ($id !== null) {
                return [$guard, (int) $id];
            }
        }

        return ['system', null];
    }
}
