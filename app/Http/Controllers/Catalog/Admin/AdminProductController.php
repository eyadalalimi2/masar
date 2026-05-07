<?php

namespace App\Http\Controllers\Catalog\Admin;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Category;
use App\Models\Catalog\ProductAttribute;
use App\Models\Catalog\ProductionYear;
use App\Models\Catalog\Product;
use App\Models\Catalog\Unit;
use App\Models\Catalog\VariantType;
use App\Http\Requests\Catalog\ProductRequest;
use App\Services\Catalog\ProductService;
use App\Models\Supplier\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->get('search', ''));
        $status = (string) $request->get('status', '');
        $categoryId = (int) $request->get('category_id', 0);
        $trashed = (string) $request->get('trashed', '');
        $attributeValueIds = collect((array) $request->input('attribute_value_ids', []))
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        $products = Product::query()
            ->with([
                'supplier',
                'category',
                'productUnits.unit',
                'productVariants.variantValue.type',
                'productConfigurations.attributeValues.attribute',
                'productConfigurations.units.unit',
            ])
            ->when($trashed === 'all', function ($query) {
                $query->withTrashed();
            })
            ->when($trashed === 'only', function ($query) {
                $query->onlyTrashed();
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhereHas('productConfigurations', function ($configurationsQuery) use ($search) {
                            $configurationsQuery->where('sku', 'like', "%{$search}%")
                                ->orWhere('barcode', 'like', "%{$search}%")
                                ->orWhereHas('attributeValues', function ($valuesQuery) use ($search) {
                                    $valuesQuery->where('value', 'like', "%{$search}%")
                                        ->orWhereHas('attribute', function ($attributesQuery) use ($search) {
                                            $attributesQuery->where('name', 'like', "%{$search}%");
                                        });
                                });
                        });
                });
            })
            ->when($categoryId > 0, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when(in_array($status, Product::STATUSES, true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($attributeValueIds->isNotEmpty(), function ($query) use ($attributeValueIds) {
                foreach ($attributeValueIds as $attributeValueId) {
                    $query->whereHas('productConfigurations.attributeValues', function ($valuesQuery) use ($attributeValueId) {
                        $valuesQuery->where('product_attribute_values.id', (int) $attributeValueId);
                    });
                }
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $categories = Category::query()->orderBy('name')->get(['id', 'name']);
        $attributes = ProductAttribute::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->with(['values' => fn($query) => $query->orderBy('value')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.products.index', compact('products', 'categories', 'attributes'));
    }

    public function create(): View
    {
        $suppliers = Supplier::latest()->get(['id', 'owner_name', 'business_name']);
        $categories = Category::orderBy('name')->get(['id', 'name']);
        $productionYears = ProductionYear::query()->orderBy('year')->pluck('year');
        $units = Unit::orderBy('name')->get(['id', 'name']);
        $variantTypes = VariantType::query()->with('values:id,variant_type_id,value')->orderBy('name')->get(['id', 'name']);
        $attributes = ProductAttribute::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->with(['values' => fn($query) => $query->orderBy('value')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.products.create', compact('suppliers', 'categories', 'productionYears', 'units', 'variantTypes', 'attributes'));
    }

    public function show(Product $product): View
    {
        $product->load([
            'supplier',
            'category',
            'productUnits.unit',
            'productVariants.variantValue.type',
            'productConfigurations.attributeValues.attribute',
            'productConfigurations.units.unit',
        ]);

        return view('admin.products.show', compact('product'));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $this->productService->create($request->validated());

        return redirect()->route('admin.products.index')->with('success', 'تم إضافة المنتج بنجاح.');
    }

    public function edit(Product $product): View
    {
        $product->load('productUnits', 'productVariants.variantValue.type', 'productConfigurations.attributeValues.attribute', 'productConfigurations.units.unit');
        $suppliers = Supplier::latest()->get(['id', 'owner_name', 'business_name']);
        $categories = Category::orderBy('name')->get(['id', 'name']);
        $productionYears = ProductionYear::query()->orderBy('year')->pluck('year');
        $units = Unit::orderBy('name')->get(['id', 'name']);
        $variantTypes = VariantType::query()->with('values:id,variant_type_id,value')->orderBy('name')->get(['id', 'name']);
        $attributes = ProductAttribute::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->with(['values' => fn($query) => $query->orderBy('value')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.products.edit', compact('product', 'suppliers', 'categories', 'productionYears', 'units', 'variantTypes', 'attributes'));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->productService->update($product, $request->validated());

        return redirect()->route('admin.products.index')->with('success', 'تم تعديل المنتج بنجاح.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->productService->delete($product);

        return redirect()->route('admin.products.index')->with('success', 'تم حذف المنتج بنجاح.');
    }

    public function restore(int $product): RedirectResponse
    {
        $model = Product::withTrashed()->findOrFail($product);
        $this->productService->restore($model);

        return redirect()->route('admin.products.index')->with('success', 'تم استرجاع المنتج بنجاح.');
    }

    public function forceDelete(int $product): RedirectResponse
    {
        $model = Product::withTrashed()->findOrFail($product);
        $this->productService->forceDelete($model);

        return redirect()->route('admin.products.index')->with('success', 'تم الحذف النهائي للمنتج بنجاح.');
    }

    public function toggle(Product $product): RedirectResponse
    {
        $this->productService->toggleStatus($product);

        return redirect()->route('admin.products.index')->with('success', 'تم تحديث حالة المنتج.');
    }
}
