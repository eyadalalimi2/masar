<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distribution\BranchAccount;
use App\Models\Distribution\Branch;
use App\Models\Distribution\DistributorAccount;
use App\Models\Distribution\Distributor;
use App\Models\Supplier\Agent;
use App\Models\Supplier\Supplier;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->get('search', ''));
        $role = (string) $request->get('role', '');
        $allowedRoles = ['supplier', 'branch', 'distributor'];

        $users = collect()
            ->concat(Agent::query()->get(['id', 'supplier_id as entity_id', 'name', 'phone', 'created_at'])->map(fn($u) => (object) [
                'id' => $u->id,
                'entity_id' => $u->entity_id,
                'name' => $u->name,
                'phone' => $u->phone,
                'role' => 'supplier',
                'created_at' => $u->created_at,
            ]))
            ->concat(BranchAccount::query()->get(['id', 'branch_id as entity_id', 'name', 'phone', 'created_at'])->map(fn($u) => (object) [
                'id' => $u->id,
                'entity_id' => $u->entity_id,
                'name' => $u->name,
                'phone' => $u->phone,
                'role' => 'branch',
                'created_at' => $u->created_at,
            ]))
            ->concat(DistributorAccount::query()->get(['id', 'distributor_id as entity_id', 'name', 'phone', 'created_at'])->map(fn($u) => (object) [
                'id' => $u->id,
                'entity_id' => $u->entity_id,
                'name' => $u->name,
                'phone' => $u->phone,
                'role' => 'distributor',
                'created_at' => $u->created_at,
            ]));

        if ($search !== '') {
            $users = $users->filter(function ($u) use ($search) {
                return str_contains((string) $u->name, $search)
                    || str_contains((string) $u->phone, $search)
                    || (ctype_digit($search) && (int) $u->id === (int) $search);
            })->values();
        }

        if (in_array($role, $allowedRoles, true)) {
            $users = $users->where('role', $role)->values();
        }

        $users = $users->sortByDesc('created_at')->values();

        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $total = $users->count();
        $items = $users->slice(($page - 1) * $perPage, $perPage)->values();
        $users = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);

        $supplierProfiles = Supplier::query()
            ->whereIn('id', $users->getCollection()->where('role', 'supplier')->pluck('entity_id')->all())
            ->get(['id', 'owner_name', 'business_name'])
            ->keyBy('id');

        $branchProfiles = Branch::query()
            ->whereIn('id', $users->getCollection()->where('role', 'branch')->pluck('entity_id')->all())
            ->get(['id', 'name'])
            ->keyBy('id');

        $distributorProfiles = Distributor::query()
            ->whereIn('id', $users->getCollection()->where('role', 'distributor')->pluck('entity_id')->all())
            ->get(['id', 'name'])
            ->keyBy('id');

        return view('admin.users.index', compact('users', 'supplierProfiles', 'branchProfiles', 'distributorProfiles'));
    }
}






