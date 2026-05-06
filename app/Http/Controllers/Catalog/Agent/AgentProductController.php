<?php

namespace App\Http\Controllers\Catalog\Agent;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Category;
use App\Models\Catalog\ProductionYear;
use App\Models\Catalog\Product;
use App\Models\Catalog\Unit;
use App\Models\Catalog\VariantType;
use App\Http\Requests\Catalog\ProductRequest;
use App\Services\Catalog\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AgentProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(Request $request): View
    {
        $supplierId = Auth::user()->supplier->id;
        $search = trim((string) $request->query('search', ''));
        $categoryId = (int) $request->query('category_id', 0);

        $products = Product::with(['category', 'productUnits.unit', 'productVariants.variantValue.type'])
            ->where('supplier_id', $supplierId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%");
                });
            })
            ->when($categoryId > 0, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $categories = Category::query()->orderBy('name')->get(['id', 'name']);

        return view('agent.products.index', compact('products', 'categories'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get(['id', 'name']);
        $productionYears = ProductionYear::query()->orderBy('year')->pluck('year');
        $units = Unit::orderBy('name')->get(['id', 'name']);
        $variantTypes = VariantType::query()->with('values:id,variant_type_id,value')->orderBy('name')->get(['id', 'name']);

        return view('agent.products.create', compact('categories', 'productionYears', 'units', 'variantTypes'));
    }

    public function show(Product $product): View
    {
        $supplierId = Auth::user()->supplier->id;
        abort_unless($product->supplier_id === $supplierId, 404);

        $product->load(['category', 'productUnits.unit', 'productVariants.variantValue.type']);

        return view('agent.products.show', compact('product'));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $payload = array_merge($request->validated(), [
            'supplier_id' => Auth::user()->supplier->id,
        ]);

        $this->productService->create($payload);

        return redirect()->route('agent.products.index')->with('success', 'تم إضافة المنتج بنجاح.');
    }

    public function edit(Product $product): View
    {
        $supplierId = Auth::user()->supplier->id;
        abort_unless($product->supplier_id === $supplierId, 404);

        $product->load('productUnits', 'productVariants.variantValue.type');
        $categories = Category::orderBy('name')->get(['id', 'name']);
        $productionYears = ProductionYear::query()->orderBy('year')->pluck('year');
        $units = Unit::orderBy('name')->get(['id', 'name']);
        $variantTypes = VariantType::query()->with('values:id,variant_type_id,value')->orderBy('name')->get(['id', 'name']);

        return view('agent.products.edit', compact('product', 'categories', 'productionYears', 'units', 'variantTypes'));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $supplierId = Auth::user()->supplier->id;
        abort_unless($product->supplier_id === $supplierId, 404);

        $payload = array_merge($request->validated(), [
            'supplier_id' => $supplierId,
        ]);

        $this->productService->update($product, $payload);

        return redirect()->route('agent.products.index')->with('success', 'تم تعديل المنتج بنجاح.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $supplierId = Auth::user()->supplier->id;
        abort_unless($product->supplier_id === $supplierId, 404);

        $this->productService->delete($product);

        return redirect()->route('agent.products.index')->with('success', 'تم حذف المنتج بنجاح.');
    }

    public function toggle(Product $product): RedirectResponse
    {
        $supplierId = Auth::user()->supplier->id;
        abort_unless($product->supplier_id === $supplierId, 404);

        $this->productService->toggleStatus($product);

        return redirect()->route('agent.products.index')->with('success', 'تم تحديث حالة المنتج.');
    }

    public function bulkPricingUpdate(Request $request): RedirectResponse
    {
        $supplierId = Auth::user()->supplier->id;

        $data = $request->validate([
            'update_mode' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric'],
            'apply_to' => ['required', 'in:wholesale,retail,both'],
        ]);

        $value = (float) $data['value'];
        $applyTo = (string) $data['apply_to'];
        $updatedRows = 0;

        DB::transaction(function () use ($supplierId, $data, $value, $applyTo, &$updatedRows) {
            $units = DB::table('product_units')
                ->join('products', 'products.id', '=', 'product_units.product_id')
                ->where('products.supplier_id', $supplierId)
                ->select('product_units.id', 'product_units.wholesale_price', 'product_units.retail_price')
                ->get();

            foreach ($units as $unit) {
                $newWholesale = (float) $unit->wholesale_price;
                $newRetail = (float) $unit->retail_price;

                if (in_array($applyTo, ['wholesale', 'both'], true)) {
                    $newWholesale = $data['update_mode'] === 'percentage'
                        ? $newWholesale + ($newWholesale * ($value / 100))
                        : $newWholesale + $value;
                }

                if (in_array($applyTo, ['retail', 'both'], true)) {
                    $newRetail = $data['update_mode'] === 'percentage'
                        ? $newRetail + ($newRetail * ($value / 100))
                        : $newRetail + $value;
                }

                DB::table('product_units')
                    ->where('id', $unit->id)
                    ->update([
                        'wholesale_price' => max(0, round($newWholesale, 2)),
                        'retail_price' => max(0, round($newRetail, 2)),
                        'updated_at' => now(),
                    ]);

                $updatedRows++;
            }
        });

        return back()->with('success', 'تم تحديث الأسعار جماعيًا لعدد ' . $updatedRows . ' وحدة منتج.');
    }
}
