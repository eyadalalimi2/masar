<?php

namespace App\Http\Controllers\Distribution\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distribution\Branch;
use App\Models\Distribution\Distributor;
use App\Models\Finance\Account;
use App\Http\Requests\Distribution\DistributorRequest;
use App\Services\Distribution\DistributorService;
use App\Models\Supplier\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDistributorController extends Controller
{
    public function __construct(private readonly DistributorService $distributorService) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->get('search', ''));
        $status = (string) $request->get('status', '');
        $supplierId = (int) $request->get('supplier_id', 0);
        $branchId = (int) $request->get('branch_id', 0);
        $trashed = (string) $request->get('trashed', '');

        $supplierFilter = $supplierId > 0
            ? Supplier::query()->find($supplierId, ['id', 'owner_name', 'business_name'])
            : null;

        $branchFilter = $branchId > 0
            ? Branch::query()->with('supplier:id,owner_name,business_name')->find($branchId, ['id', 'name', 'supplier_id'])
            : null;

        $distributors = Distributor::query()
            ->with(['supplier', 'branch'])
            ->when($trashed === 'all', function ($query) {
                $query->withTrashed();
            })
            ->when($trashed === 'only', function ($query) {
                $query->onlyTrashed();
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('vehicle_type', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, [Account::STATUS_ACTIVE, Account::STATUS_INACTIVE], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($supplierFilter !== null, function ($query) use ($supplierFilter) {
                $query->where('supplier_id', $supplierFilter->id);
            })
            ->when($branchFilter !== null, function ($query) use ($branchFilter) {
                $query->where('branch_id', $branchFilter->id);
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.distributors.index', compact('distributors', 'supplierFilter', 'branchFilter'));
    }

    public function create(): View
    {
        $suppliers = Supplier::latest()->get(['id', 'owner_name', 'business_name', 'logo']);
        $branches = Branch::latest()->get(['id', 'name', 'supplier_id']);

        return view('admin.distributors.create', compact('suppliers', 'branches'));
    }

    public function store(DistributorRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        if (! empty($payload['branch_id'])) {
            $belongsToSupplier = Branch::where('id', $payload['branch_id'])
                ->where('supplier_id', $payload['supplier_id'])
                ->exists();

            if (! $belongsToSupplier) {
                return back()->withErrors(['branch_id' => 'الفرع لا يتبع الوكيل المحدد.'])->withInput();
            }
        }

        $this->distributorService->create($payload);

        return redirect()->route('admin.distributors.index')->with('success', 'تم إضافة المندوب بنجاح.');
    }

    public function edit(Distributor $distributor): View
    {
        $suppliers = Supplier::latest()->get(['id', 'owner_name', 'business_name', 'logo']);
        $branches = Branch::latest()->get(['id', 'name', 'supplier_id']);

        return view('admin.distributors.edit', compact('distributor', 'suppliers', 'branches'));
    }

    public function show(Distributor $distributor): View
    {
        $distributor->load(['supplier', 'branch']);

        return view('admin.distributors.show', compact('distributor'));
    }

    public function update(DistributorRequest $request, Distributor $distributor): RedirectResponse
    {
        $payload = $request->validated();

        if (! empty($payload['branch_id'])) {
            $belongsToSupplier = Branch::where('id', $payload['branch_id'])
                ->where('supplier_id', $payload['supplier_id'])
                ->exists();

            if (! $belongsToSupplier) {
                return back()->withErrors(['branch_id' => 'الفرع لا يتبع الوكيل المحدد.'])->withInput();
            }
        }

        $this->distributorService->update($distributor, $payload);

        return redirect()->route('admin.distributors.index')->with('success', 'تم تعديل بيانات المندوب بنجاح.');
    }

    public function destroy(Distributor $distributor): RedirectResponse
    {
        $this->distributorService->delete($distributor);

        return redirect()->route('admin.distributors.index')->with('success', 'تم حذف المندوب بنجاح.');
    }

    public function restore(int $distributor): RedirectResponse
    {
        $model = Distributor::withTrashed()->findOrFail($distributor);
        $this->distributorService->restore($model);

        return redirect()->route('admin.distributors.index')->with('success', 'تم استرجاع المندوب بنجاح.');
    }

    public function forceDelete(int $distributor): RedirectResponse
    {
        $model = Distributor::withTrashed()->findOrFail($distributor);
        $this->distributorService->forceDelete($model);

        return redirect()->route('admin.distributors.index')->with('success', 'تم الحذف النهائي للمندوب بنجاح.');
    }

    public function toggle(Distributor $distributor): RedirectResponse
    {
        $this->distributorService->toggleStatus($distributor);

        return redirect()->route('admin.distributors.index')->with('success', 'تم تحديث حالة المندوب.');
    }
}
