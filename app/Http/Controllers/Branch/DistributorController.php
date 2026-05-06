<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Distribution\DistributorRequest;
use App\Models\Distribution\Branch;
use App\Models\Distribution\Distributor;
use App\Services\Distribution\DistributorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DistributorController extends Controller
{
    public function __construct(private readonly DistributorService $distributorService) {}

    public function index(Request $request): View
    {
        $branch = $this->currentBranch();
        $search = trim((string) $request->query('search', ''));

        $distributors = Distributor::query()
            ->where('branch_id', $branch->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('distribution_points', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('branch.distributors.index', compact('branch', 'distributors'));
    }

    public function store(DistributorRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();

        $payload = array_merge($request->validated(), [
            'supplier_id' => $branch->supplier_id,
            'branch_id' => $branch->id,
        ]);

        $this->distributorService->create($payload);

        return back()->with('success', 'تم إضافة مندوب جديد بنجاح.');
    }

    public function update(DistributorRequest $request, Distributor $distributor): RedirectResponse
    {
        $branch = $this->currentBranch();
        abort_unless((int) $distributor->branch_id === (int) $branch->id, 404);

        $payload = array_merge($request->validated(), [
            'supplier_id' => $branch->supplier_id,
            'branch_id' => $branch->id,
        ]);

        $this->distributorService->update($distributor, $payload);

        return back()->with('success', 'تم تحديث بيانات المندوب بنجاح.');
    }

    public function toggle(Distributor $distributor): RedirectResponse
    {
        $branch = $this->currentBranch();
        abort_unless((int) $distributor->branch_id === (int) $branch->id, 404);

        $this->distributorService->toggleStatus($distributor);

        return back()->with('success', 'تم تحديث حالة المندوب.');
    }

    private function currentBranch(): Branch
    {
        $account = Auth::guard('branch')->user();

        if ($account && isset($account->branch_id) && (int) $account->branch_id > 0) {
            return Branch::query()->whereKey((int) $account->branch_id)->firstOrFail();
        }

        $phone = trim((string) ($account->phone ?? ''));
        if ($phone === '') {
            abort(403);
        }

        return Branch::query()->where('phone', $phone)->firstOrFail();
    }
}
