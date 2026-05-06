<?php

namespace App\Http\Controllers\Distribution\Agent;

use App\Http\Controllers\Controller;
use App\Models\Distribution\Branch;
use App\Http\Requests\Distribution\BranchRequest;
use App\Services\Distribution\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AgentBranchController extends Controller
{
    public function __construct(private readonly BranchService $branchService) {}

    public function index(Request $request): View
    {
        $supplierId = Auth::user()->supplier->id;
        $search = trim((string) $request->get('search', ''));

        $branches = Branch::query()
            ->with('supplier:id,business_name,logo')
            ->where('supplier_id', $supplierId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('agent.branches.index', compact('branches'));
    }

    public function create(): View
    {
        return view('agent.branches.create');
    }

    public function show(Branch $branch): View
    {
        $supplierId = Auth::user()->supplier->id;
        abort_unless($branch->supplier_id === $supplierId, 404);

        return view('agent.branches.show', compact('branch'));
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        $payload = array_merge($request->validated(), [
            'supplier_id' => Auth::user()->supplier->id,
        ]);

        $this->branchService->create($payload);

        return redirect()->route('agent.branches.index')->with('success', 'تم إضافة الفرع بنجاح.');
    }

    public function edit(Branch $branch): View
    {
        $supplierId = Auth::user()->supplier->id;
        abort_unless($branch->supplier_id === $supplierId, 404);

        return view('agent.branches.edit', compact('branch'));
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        $supplierId = Auth::user()->supplier->id;
        abort_unless($branch->supplier_id === $supplierId, 404);

        $payload = array_merge($request->validated(), [
            'supplier_id' => $supplierId,
        ]);

        $this->branchService->update($branch, $payload);

        return redirect()->route('agent.branches.index')->with('success', 'تم تعديل الفرع بنجاح.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $supplierId = Auth::user()->supplier->id;
        abort_unless($branch->supplier_id === $supplierId, 404);

        $this->branchService->delete($branch);

        return redirect()->route('agent.branches.index')->with('success', 'تم حذف الفرع بنجاح.');
    }

    public function toggle(Branch $branch): RedirectResponse
    {
        $supplierId = Auth::user()->supplier->id;
        abort_unless($branch->supplier_id === $supplierId, 404);

        $this->branchService->toggleStatus($branch);

        return redirect()->route('agent.branches.index')->with('success', 'تم تحديث حالة الفرع.');
    }
}






