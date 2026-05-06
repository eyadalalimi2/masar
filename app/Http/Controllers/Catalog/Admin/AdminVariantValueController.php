<?php

namespace App\Http\Controllers\Catalog\Admin;

use App\Http\Controllers\Controller;
use App\Models\Catalog\VariantType;
use App\Models\Catalog\VariantValue;
use App\Http\Requests\Catalog\VariantValueRequest;
use App\Services\Catalog\VariantService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminVariantValueController extends Controller
{
    public function __construct(private readonly VariantService $variantService) {}

    public function index(Request $request): View
    {
        $variantTypes = VariantType::query()->orderBy('name')->get(['id', 'name']);

        $variantValues = VariantValue::query()
            ->with('type')
            ->when(
                $request->filled('variant_type_id'),
                fn($query) => $query->where('variant_type_id', $request->integer('variant_type_id'))
            )
            ->when(
                $request->filled('q'),
                fn($query) => $query->where('value', 'like', '%' . trim((string) $request->input('q')) . '%')
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.variant_values.index', compact('variantValues', 'variantTypes'));
    }

    public function create(): View
    {
        $variantTypes = VariantType::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.variant_values.create', compact('variantTypes'));
    }

    public function store(VariantValueRequest $request): RedirectResponse
    {
        $this->variantService->createValue($request->validated());

        return redirect()->route('admin.variant-values.index')->with('success', 'تم إضافة قيمة المواصفة بنجاح.');
    }

    public function edit(VariantValue $variantValue): View
    {
        $variantTypes = VariantType::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.variant_values.edit', compact('variantValue', 'variantTypes'));
    }

    public function update(VariantValueRequest $request, VariantValue $variantValue): RedirectResponse
    {
        $this->variantService->updateValue($variantValue, $request->validated());

        return redirect()->route('admin.variant-values.index')->with('success', 'تم تعديل قيمة المواصفة بنجاح.');
    }

    public function destroy(VariantValue $variantValue): RedirectResponse
    {
        $this->variantService->deleteValue($variantValue);

        return redirect()->route('admin.variant-values.index')->with('success', 'تم حذف قيمة المواصفة بنجاح.');
    }
}
