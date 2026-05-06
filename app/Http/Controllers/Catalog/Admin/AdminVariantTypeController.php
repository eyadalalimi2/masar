<?php

namespace App\Http\Controllers\Catalog\Admin;

use App\Http\Controllers\Controller;
use App\Models\Catalog\VariantType;
use App\Http\Requests\Catalog\VariantTypeRequest;
use App\Services\Catalog\VariantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminVariantTypeController extends Controller
{
    public function __construct(private readonly VariantService $variantService) {}

    public function index(): View
    {
        $variantTypes = VariantType::query()->withCount('values')->latest()->paginate(15);

        return view('admin.variant_types.index', compact('variantTypes'));
    }

    public function create(): View
    {
        return view('admin.variant_types.create');
    }

    public function store(VariantTypeRequest $request): RedirectResponse
    {
        $this->variantService->createType($request->validated());

        return redirect()->route('admin.variant-types.index')->with('success', 'تم إضافة نوع المواصفة بنجاح.');
    }

    public function edit(VariantType $variantType): View
    {
        return view('admin.variant_types.edit', compact('variantType'));
    }

    public function update(VariantTypeRequest $request, VariantType $variantType): RedirectResponse
    {
        $this->variantService->updateType($variantType, $request->validated());

        return redirect()->route('admin.variant-types.index')->with('success', 'تم تعديل نوع المواصفة بنجاح.');
    }

    public function destroy(VariantType $variantType): RedirectResponse
    {
        $this->variantService->deleteType($variantType);

        return redirect()->route('admin.variant-types.index')->with('success', 'تم حذف نوع المواصفة بنجاح.');
    }
}
