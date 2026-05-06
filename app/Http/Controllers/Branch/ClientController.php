<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Distribution\Branch;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $branch = $this->currentBranch();
        $search = trim((string) $request->query('search', ''));

        $clients = Order::query()
            ->where('branch_id', $branch->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('snapshot_customer_name', 'like', "%{$search}%")
                        ->orWhere('snapshot_customer_phone', 'like', "%{$search}%")
                        ->orWhere('snapshot_customer_address', 'like', "%{$search}%");
                });
            })
            ->select(
                'snapshot_customer_name as customer_name',
                'snapshot_customer_phone as customer_phone',
                'snapshot_customer_address as customer_address',
                DB::raw('COUNT(id) as orders_count'),
                DB::raw('SUM(COALESCE(payable_total, total_price)) as total_spent'),
                DB::raw('MAX(created_at) as last_order_at')
            )
            ->groupBy('snapshot_customer_name', 'snapshot_customer_phone', 'snapshot_customer_address')
            ->orderByDesc('orders_count')
            ->paginate(15)
            ->withQueryString();

        return view('branch.clients.index', compact('branch', 'clients'));
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
