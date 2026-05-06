<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosLocalProduct;
use App\Models\PosSale;
use App\Support\OptionLists;
use App\Services\Pos\PosInventoryInsightService;
use App\Services\Pos\PosContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __construct(
        private readonly PosContextService $posContext,
        private readonly PosInventoryInsightService $inventoryInsightService,
    ) {}

    public function index(Request $request): View
    {
        $pos = $this->posContext->currentPos();

        $sales = PosSale::query()
            ->with('localProduct.product:id,name')
            ->where('pos_account_id', $pos->id)
            ->latest('sold_at')
            ->paginate(20);

        $saleProducts = PosLocalProduct::query()
            ->with(['product:id,name,model', 'productUnit.unit:id,name'])
            ->where('pos_account_id', $pos->id)
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->get();

        return view('pos.sales.index', compact('pos', 'sales', 'saleProducts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $pos = $this->posContext->currentPos();

        $data = $request->validate([
            'pos_local_product_id' => ['required', 'integer', 'exists:pos_local_products,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'sale_channel' => ['required', 'in:' . implode(',', OptionLists::POS_SALE_CHANNELS)],
            'discount_type' => ['nullable', 'in:percent,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'campaign_code' => ['nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $localProduct = PosLocalProduct::query()
            ->with('product:id,name')
            ->where('pos_account_id', $pos->id)
            ->findOrFail((int) $data['pos_local_product_id']);

        $qty = (float) $data['quantity'];
        $unitPrice = (float) $localProduct->selling_price;
        $purchasePrice = (float) $localProduct->purchase_price;
        $gross = round($unitPrice * $qty, 2);
        $discount = $this->calculateDiscountAmount($gross, $data['discount_type'] ?? null, $data['discount_value'] ?? null);
        $total = round(max($gross - $discount, 0), 2);
        $profit = round($total - ($purchasePrice * $qty), 2);

        PosSale::query()->create([
            'pos_account_id' => $pos->id,
            'pos_local_product_id' => $localProduct->id,
            'product_name' => (string) ($localProduct->product?->name ?? 'منتج'),
            'snapshot_customer_name' => $data['customer_name'] ?? null,
            'snapshot_customer_phone' => $data['customer_phone'] ?? null,
            'sale_channel' => (string) $data['sale_channel'],
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'gross_amount' => $gross,
            'discount_type' => $data['discount_type'] ?? null,
            'discount_value' => (float) ($data['discount_value'] ?? 0),
            'discount_amount' => $discount,
            'campaign_code' => $data['campaign_code'] ?? null,
            'total_amount' => $total,
            'profit_amount' => $profit,
            'note' => $data['note'] ?? null,
            'sold_at' => now(),
        ]);

        if ((float) $localProduct->local_quantity > 0) {
            $localProduct->update([
                'local_quantity' => max(0, (float) $localProduct->local_quantity - $qty),
            ]);
        }

        $this->inventoryInsightService->generateSmartRefillAlerts($pos);

        return back()->with('success', 'تم تسجيل عملية البيع بنجاح.');
    }

    public function storeQuickSale(Request $request): RedirectResponse
    {
        $pos = $this->posContext->currentPos();

        $data = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'gt:0'],
            'purchase_unit_price' => ['nullable', 'numeric', 'min:0'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'sale_channel' => ['required', 'in:' . implode(',', OptionLists::POS_SALE_CHANNELS)],
            'discount_type' => ['nullable', 'in:percent,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'campaign_code' => ['nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $qty = (float) $data['quantity'];
        $unitPrice = (float) $data['unit_price'];
        $purchaseUnitPrice = isset($data['purchase_unit_price']) ? (float) $data['purchase_unit_price'] : 0.0;
        $gross = round($unitPrice * $qty, 2);
        $discount = $this->calculateDiscountAmount($gross, $data['discount_type'] ?? null, $data['discount_value'] ?? null);
        $total = round(max($gross - $discount, 0), 2);
        $profit = round($total - ($purchaseUnitPrice * $qty), 2);

        PosSale::query()->create([
            'pos_account_id' => $pos->id,
            'pos_local_product_id' => null,
            'product_name' => (string) $data['product_name'],
            'snapshot_customer_name' => $data['customer_name'] ?? null,
            'snapshot_customer_phone' => $data['customer_phone'] ?? null,
            'sale_channel' => (string) $data['sale_channel'],
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'gross_amount' => $gross,
            'discount_type' => $data['discount_type'] ?? null,
            'discount_value' => (float) ($data['discount_value'] ?? 0),
            'discount_amount' => $discount,
            'campaign_code' => $data['campaign_code'] ?? null,
            'total_amount' => $total,
            'profit_amount' => $profit,
            'note' => ($data['note'] ?? '') !== '' ? $data['note'] : 'Quick sale',
            'sold_at' => now(),
        ]);

        return back()->with('success', 'تم تسجيل عملية Quick Sale بنجاح.');
    }

    private function calculateDiscountAmount(float $grossAmount, ?string $discountType, mixed $discountValue): float
    {
        if (! in_array($discountType, ['percent', 'fixed'], true)) {
            return 0.0;
        }

        $value = max((float) ($discountValue ?? 0), 0);
        if ($value <= 0) {
            return 0.0;
        }

        if ($discountType === 'percent') {
            $value = min($value, 100);
            return round(($grossAmount * $value) / 100, 2);
        }

        return round(min($value, $grossAmount), 2);
    }
}
