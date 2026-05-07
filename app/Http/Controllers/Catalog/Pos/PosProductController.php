<?php

namespace App\Http\Controllers\Catalog\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\ProductRequest;
use App\Models\Catalog\Category;
use App\Models\Catalog\ProductAttribute;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductionYear;
use App\Models\Catalog\Unit;
use App\Models\Catalog\VariantType;
use App\Services\Catalog\PortalSupplierResolver;
use App\Services\Catalog\ProductService;
use App\Services\Pos\PosContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly PosContextService $posContext,
        private readonly PortalSupplierResolver $supplierResolver,
    ) {}

    public function index(Request $request): View
    {
        $pos = $this->posContext->currentPos();
        $supplierId = $this->supplierResolver->resolveForPos($pos);
        $search = trim((string) $request->query('search', ''));
        $categoryId = (int) $request->query('category_id', 0);
        $attributeValueIds = collect((array) $request->input('attribute_value_ids', []))
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        $products = Product::with([
            'category',
            'productUnits.unit',
            'productVariants.variantValue.type',
            'productConfigurations.attributeValues.attribute',
            'productConfigurations.units.unit',
        ])
            ->when($supplierId !== null, fn($query) => $query->where('supplier_id', (int) $supplierId))
            ->when($supplierId === null, fn($query) => $query->whereRaw('1=0'))
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
            ->when($categoryId > 0, fn($query) => $query->where('category_id', $categoryId))
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

        return view('pos.products.index', compact('products', 'categories', 'attributes', 'supplierId'));
    }

    public function create(): View|RedirectResponse
    {
        $pos = $this->posContext->currentPos();
        $supplierId = $this->supplierResolver->resolveForPos($pos);

        if ($supplierId === null) {
            return redirect()->route('pos.products.index')->withErrors([
                'products' => 'لا يوجد وكيل مرتبط بهذا المحل حتى الآن. أنشئ توريدًا أولًا ليتم الربط تلقائيًا.',
            ]);
        }

        $categories = Category::query()->orderBy('name')->get(['id', 'name']);
        $productionYears = ProductionYear::query()->orderBy('year')->pluck('year');
        $units = Unit::query()->orderBy('name')->get(['id', 'name']);
        $variantTypes = VariantType::query()->with('values:id,variant_type_id,value')->orderBy('name')->get(['id', 'name']);
        $attributes = ProductAttribute::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->with(['values' => fn($query) => $query->orderBy('value')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
        $variantTypesPayload = $variantTypes
            ->map(fn($type) => [
                'id' => $type->id,
                'name' => $type->name,
                'values' => $type->values->map(fn($value) => ['id' => $value->id, 'value' => $value->value])->values(),
            ])
            ->values();

        return view('pos.products.create', compact('categories', 'productionYears', 'units', 'variantTypes', 'variantTypesPayload', 'attributes', 'supplierId'));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $pos = $this->posContext->currentPos();
        $supplierId = $this->supplierResolver->resolveForPos($pos);

        if ($supplierId === null) {
            return back()->withErrors([
                'products' => 'تعذر تحديد الوكيل المرتبط بالمحل. يرجى تنفيذ عملية توريد أولًا.',
            ])->withInput();
        }

        $payload = array_merge($request->validated(), [
            'supplier_id' => (int) $supplierId,
        ]);

        $this->productService->create($payload);

        return redirect()->route('pos.products.index')->with('success', 'تم إضافة المنتج بنجاح وربطه تلقائيًا بالوكيل.');
    }

    public function show(Product $product): View
    {
        $supplierId = $this->resolveLinkedSupplierIdOrFail();
        abort_unless((int) $product->supplier_id === $supplierId, 404);

        $product->load(['category', 'productUnits.unit', 'productVariants.variantValue.type', 'productConfigurations.attributeValues.attribute', 'productConfigurations.units.unit']);

        return view('pos.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $supplierId = $this->resolveLinkedSupplierIdOrFail();
        abort_unless((int) $product->supplier_id === $supplierId, 404);

        $product->load('productUnits', 'productVariants.variantValue.type', 'productConfigurations.attributeValues.attribute', 'productConfigurations.units.unit');
        $categories = Category::query()->orderBy('name')->get(['id', 'name']);
        $productionYears = ProductionYear::query()->orderBy('year')->pluck('year');
        $units = Unit::query()->orderBy('name')->get(['id', 'name']);
        $variantTypes = VariantType::query()->with('values:id,variant_type_id,value')->orderBy('name')->get(['id', 'name']);
        $attributes = ProductAttribute::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->with(['values' => fn($query) => $query->orderBy('value')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
        $variantTypesPayload = $variantTypes
            ->map(fn($type) => [
                'id' => $type->id,
                'name' => $type->name,
                'values' => $type->values->map(fn($value) => ['id' => $value->id, 'value' => $value->value])->values(),
            ])
            ->values();

        return view('pos.products.edit', compact('product', 'categories', 'productionYears', 'units', 'variantTypes', 'variantTypesPayload', 'attributes', 'supplierId'));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $supplierId = $this->resolveLinkedSupplierIdOrFail();
        abort_unless((int) $product->supplier_id === $supplierId, 404);

        $payload = array_merge($request->validated(), [
            'supplier_id' => $supplierId,
        ]);

        $this->productService->update($product, $payload);

        return redirect()->route('pos.products.index')->with('success', 'تم تعديل المنتج بنجاح.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $supplierId = $this->resolveLinkedSupplierIdOrFail();
        abort_unless((int) $product->supplier_id === $supplierId, 404);

        $this->productService->delete($product);

        return redirect()->route('pos.products.index')->with('success', 'تم حذف المنتج بنجاح.');
    }

    public function duplicate(Product $product): RedirectResponse
    {
        $supplierId = $this->resolveLinkedSupplierIdOrFail();
        abort_unless((int) $product->supplier_id === $supplierId, 404);

        $product->load(['productUnits', 'productVariants.variantValue:id,variant_type_id', 'productConfigurations.attributeValues.attribute', 'productConfigurations.units']);

        if ($product->productUnits->isEmpty()) {
            return back()->withErrors(['products' => 'لا يمكن نسخ منتج بدون وحدات.']);
        }

        $payload = [
            'supplier_id' => $supplierId,
            'category_id' => (int) $product->category_id,
            'name' => (string) $product->name . ' (نسخة)',
            'model' => (string) $product->model . '-COPY',
            'car_models' => is_array($product->car_models) ? $product->car_models : [],
            'description' => $product->description,
            'status' => 'inactive',
            'units' => $product->productUnits
                ->map(fn($unit) => [
                    'unit_id' => (int) $unit->unit_id,
                    'wholesale_price' => (float) $unit->wholesale_price,
                    'retail_price' => (float) $unit->retail_price,
                    'conversion_factor' => (float) ($unit->conversion_factor ?: 1),
                ])
                ->values()
                ->all(),
            'variants' => $product->productVariants
                ->filter(fn($variant) => (int) ($variant->variant_value_id ?? 0) > 0 && (int) ($variant->variantValue?->variant_type_id ?? 0) > 0)
                ->map(fn($variant) => [
                    'variant_type_id' => (int) $variant->variantValue->variant_type_id,
                    'variant_value_id' => (int) $variant->variant_value_id,
                ])
                ->values()
                ->all(),
            'configurations' => $product->productConfigurations
                ->map(fn($configuration) => [
                    'name' => $configuration->name,
                    'sku' => $configuration->sku,
                    'barcode' => $configuration->barcode,
                    'is_default' => (bool) $configuration->is_default,
                    'status' => $configuration->status,
                    'attribute_value_ids' => $configuration->attributeValues->pluck('id')->map(fn($id) => (int) $id)->values()->all(),
                    'units' => $configuration->units->map(fn($unitRow) => [
                        'unit_id' => (int) $unitRow->unit_id,
                        'wholesale_price' => (float) $unitRow->wholesale_price,
                        'retail_price' => (float) $unitRow->retail_price,
                        'conversion_factor' => (float) ($unitRow->conversion_factor ?: 1),
                        'stock_quantity' => (float) ($unitRow->stock_quantity ?: 0),
                        'low_stock_threshold' => (float) ($unitRow->low_stock_threshold ?: 0),
                    ])->values()->all(),
                ])
                ->values()
                ->all(),
        ];

        $newProduct = $this->productService->create($payload);

        return redirect()->route('pos.products.edit', $newProduct)->with('success', 'تم نسخ المنتج بنجاح. يمكنك الآن تعديل النسخة الجديدة.');
    }

    private function resolveLinkedSupplierIdOrFail(): int
    {
        $pos = $this->posContext->currentPos();
        $supplierId = $this->supplierResolver->resolveForPos($pos);

        abort_unless($supplierId !== null, 404);

        return (int) $supplierId;
    }
}
