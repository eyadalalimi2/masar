<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosLocalProduct;
use App\Services\Pos\PosInventoryInsightService;
use App\Services\Pos\PosContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(
        private readonly PosContextService $posContext,
        private readonly PosInventoryInsightService $inventoryInsightService,
    ) {}

    public function index(Request $request): View
    {
        $pos = $this->posContext->currentPos();
        $search = trim((string) $request->query('search', ''));

        $products = PosLocalProduct::query()
            ->with(['branch:id,name', 'product:id,name,model', 'productUnit.unit:id,name'])
            ->where('pos_account_id', $pos->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('product', function ($productQuery) use ($search) {
                    $productQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $insightsMap = $this->inventoryInsightService->insightsForPos($pos)
            ->keyBy('local_product_id');

        return view('pos.catalog.index', compact('pos', 'products', 'insightsMap'));
    }

    public function generateSmartRefillAlerts(): RedirectResponse
    {
        $pos = $this->posContext->currentPos();
        $created = $this->inventoryInsightService->generateSmartRefillAlerts($pos);

        return back()->with('success', 'تم إنشاء ' . $created . ' تنبيه إعادة تعبئة ذكي.');
    }

    public function updatePrice(Request $request, PosLocalProduct $localProduct): RedirectResponse
    {
        $pos = $this->posContext->currentPos();
        abort_unless((int) $localProduct->pos_account_id === (int) $pos->id, 404);

        $data = $request->validate([
            'selling_price' => ['required', 'numeric', 'min:0'],
        ]);

        $localProduct->update([
            'selling_price' => (float) $data['selling_price'],
        ]);

        return back()->with('success', 'تم تحديث سعر البيع بنجاح.');
    }

    public function toggle(PosLocalProduct $localProduct): RedirectResponse
    {
        $pos = $this->posContext->currentPos();
        abort_unless((int) $localProduct->pos_account_id === (int) $pos->id, 404);

        $localProduct->update([
            'is_active' => ! $localProduct->is_active,
        ]);

        return back()->with('success', 'تم تحديث حالة المنتج في متجرك.');
    }
}
