<?php

namespace App\Http\Controllers\Catalog\Admin;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Unit;
use App\Http\Requests\Catalog\UnitRequest;
use App\Services\Catalog\UnitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminUnitController extends Controller
{
    public function __construct(private readonly UnitService $unitService) {}

    public function index(): View
    {
        $units = Unit::latest()->paginate(15);

        return view('admin.units.index', compact('units'));
    }

    public function create(): View
    {
        return view('admin.units.create');
    }

    public function store(UnitRequest $request): RedirectResponse
    {
        $this->unitService->create($request->validated());

        return redirect()->route('admin.units.index')->with('success', 'تم إضافة الوحدة بنجاح.');
    }

    public function edit(Unit $unit): View
    {
        return view('admin.units.edit', compact('unit'));
    }

    public function update(UnitRequest $request, Unit $unit): RedirectResponse
    {
        $this->unitService->update($unit, $request->validated());

        return redirect()->route('admin.units.index')->with('success', 'تم تعديل الوحدة بنجاح.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->unitService->delete($unit);

        return redirect()->route('admin.units.index')->with('success', 'تم حذف الوحدة بنجاح.');
    }
}






