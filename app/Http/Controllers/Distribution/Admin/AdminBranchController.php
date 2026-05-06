<?php

namespace App\Http\Controllers\Distribution\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distribution\Branch;
use App\Models\Finance\Account;
use App\Http\Requests\Distribution\BranchRequest;
use App\Services\Distribution\BranchService;
use App\Models\Supplier\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminBranchController extends Controller
{
    public function __construct(private readonly BranchService $branchService) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->get('search', ''));
        $status = (string) $request->get('status', '');
        $supplierId = (int) $request->get('supplier_id', 0);
        $trashed = (string) $request->get('trashed', '');

        $supplierFilter = $supplierId > 0
            ? Supplier::query()->find($supplierId, ['id', 'owner_name', 'business_name'])
            : null;

        $branches = Branch::query()
            ->with('supplier')
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
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, [Account::STATUS_ACTIVE, Account::STATUS_INACTIVE], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($supplierFilter !== null, function ($query) use ($supplierFilter) {
                $query->where('supplier_id', $supplierFilter->id);
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.branches.index', compact('branches', 'supplierFilter'));
    }

    public function create(): View
    {
        $suppliers = Supplier::latest()->get(['id', 'owner_name', 'business_name', 'logo']);

        return view('admin.branches.create', compact('suppliers'));
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        $this->branchService->create($request->validated());

        return redirect()->route('admin.branches.index')->with('success', 'تم إضافة الفرع بنجاح.');
    }

    public function edit(Branch $branch): View
    {
        $suppliers = Supplier::latest()->get(['id', 'owner_name', 'business_name', 'logo']);

        return view('admin.branches.edit', compact('branch', 'suppliers'));
    }

    public function show(Branch $branch): View
    {
        $branch->load(['supplier', 'distributors']);

        return view('admin.branches.show', compact('branch'));
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        $this->branchService->update($branch, $request->validated());

        return redirect()->route('admin.branches.index')->with('success', 'تم تعديل الفرع بنجاح.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->branchService->delete($branch);

        return redirect()->route('admin.branches.index')->with('success', 'تم حذف الفرع بنجاح.');
    }

    public function restore(int $branch): RedirectResponse
    {
        $model = Branch::withTrashed()->findOrFail($branch);
        $this->branchService->restore($model);

        return redirect()->route('admin.branches.index')->with('success', 'تم استرجاع الفرع بنجاح.');
    }

    public function forceDelete(int $branch): RedirectResponse
    {
        $model = Branch::withTrashed()->findOrFail($branch);
        $this->branchService->forceDelete($model);

        return redirect()->route('admin.branches.index')->with('success', 'تم الحذف النهائي للفرع بنجاح.');
    }

    public function toggle(Branch $branch): RedirectResponse
    {
        $this->branchService->toggleStatus($branch);

        return redirect()->route('admin.branches.index')->with('success', 'تم تحديث حالة الفرع.');
    }
}
