<?php

namespace App\Http\Controllers\Distribution\Agent;

use App\Http\Controllers\Controller;
use App\Models\Distribution\Branch;
use App\Models\Distribution\Distributor;
use App\Http\Requests\Distribution\DistributorRequest;
use App\Services\Distribution\DistributorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AgentDistributorController extends Controller
{
    public function __construct(private readonly DistributorService $distributorService) {}

    public function index(Request $request): View
    {
        $supplierId = Auth::user()->supplier->id;
        $search = trim((string) $request->get('search', ''));

        $distributors = Distributor::with(['branch:id,name', 'supplier:id,business_name,logo'])
            ->where('supplier_id', $supplierId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('vehicle_type', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('agent.distributors.index', compact('distributors'));
    }

    public function create(): View
    {
        $supplierId = Auth::user()->supplier->id;
        $branches = Branch::where('supplier_id', $supplierId)->latest()->get(['id', 'name']);

        return view('agent.distributors.create', compact('branches'));
    }

    public function store(DistributorRequest $request): RedirectResponse
    {
        $supplierId = Auth::user()->supplier->id;
        $payload = array_merge($request->validated(), [
            'supplier_id' => $supplierId,
        ]);

        if (! empty($payload['branch_id'])) {
            $branchExists = Branch::where('supplier_id', $supplierId)->whereKey($payload['branch_id'])->exists();
            if (! $branchExists) {
                return back()->withErrors(['branch_id' => 'الفرع المحدد غير متاح.'])->withInput();
            }
        }

        $this->distributorService->create($payload);

        return redirect()->route('agent.distributors.index')->with('success', 'تم إضافة المندوب بنجاح.');
    }

    public function edit(Distributor $distributor): View
    {
        $supplierId = Auth::user()->supplier->id;
        abort_unless($distributor->supplier_id === $supplierId, 404);
        $branches = Branch::where('supplier_id', $supplierId)->latest()->get(['id', 'name']);

        return view('agent.distributors.edit', compact('distributor', 'branches'));
    }

    public function show(Distributor $distributor): View
    {
        $supplierId = Auth::user()->supplier->id;
        abort_unless($distributor->supplier_id === $supplierId, 404);

        $distributor->load('branch');

        return view('agent.distributors.show', compact('distributor'));
    }

    public function update(DistributorRequest $request, Distributor $distributor): RedirectResponse
    {
        $supplierId = Auth::user()->supplier->id;
        abort_unless($distributor->supplier_id === $supplierId, 404);
        $payload = array_merge($request->validated(), ['supplier_id' => $supplierId]);

        if (! empty($payload['branch_id'])) {
            $branchExists = Branch::where('supplier_id', $supplierId)->whereKey($payload['branch_id'])->exists();
            if (! $branchExists) {
                return back()->withErrors(['branch_id' => 'الفرع المحدد غير متاح.'])->withInput();
            }
        }

        $this->distributorService->update($distributor, $payload);

        return redirect()->route('agent.distributors.index')->with('success', 'تم تعديل بيانات المندوب بنجاح.');
    }

    public function destroy(Distributor $distributor): RedirectResponse
    {
        $supplierId = Auth::user()->supplier->id;
        abort_unless($distributor->supplier_id === $supplierId, 404);

        $this->distributorService->delete($distributor);

        return redirect()->route('agent.distributors.index')->with('success', 'تم حذف المندوب بنجاح.');
    }

    public function toggle(Distributor $distributor): RedirectResponse
    {
        $supplierId = Auth::user()->supplier->id;
        abort_unless($distributor->supplier_id === $supplierId, 404);

        $this->distributorService->toggleStatus($distributor);

        return redirect()->route('agent.distributors.index')->with('success', 'تم تحديث حالة المندوب.');
    }
}






