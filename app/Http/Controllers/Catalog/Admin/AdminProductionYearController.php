<?php

namespace App\Http\Controllers\Catalog\Admin;

use App\Http\Controllers\Controller;
use App\Models\Catalog\ProductionYear;
use App\Http\Requests\Catalog\ProductionYearRequest;
use App\Services\Catalog\ProductionYearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminProductionYearController extends Controller
{
    public function __construct(private readonly ProductionYearService $productionYearService) {}

    public function index(): View
    {
        $productionYears = ProductionYear::query()->orderBy('year')->paginate(30);

        return view('admin.production_years.index', compact('productionYears'));
    }

    public function create(): View
    {
        return view('admin.production_years.create');
    }

    public function store(ProductionYearRequest $request): RedirectResponse
    {
        $this->productionYearService->create($request->validated());

        return redirect()->route('admin.production-years.index')->with('success', 'تمت إضافة موديل السيارة بنجاح.');
    }

    public function edit(ProductionYear $production_year): View
    {
        return view('admin.production_years.edit', ['productionYear' => $production_year]);
    }

    public function update(ProductionYearRequest $request, ProductionYear $production_year): RedirectResponse
    {
        $this->productionYearService->update($production_year, $request->validated());

        return redirect()->route('admin.production-years.index')->with('success', 'تم تعديل موديل السيارة بنجاح.');
    }

    public function destroy(ProductionYear $production_year): RedirectResponse
    {
        $this->productionYearService->delete($production_year);

        return redirect()->route('admin.production-years.index')->with('success', 'تم حذف موديل السيارة بنجاح.');
    }
}






