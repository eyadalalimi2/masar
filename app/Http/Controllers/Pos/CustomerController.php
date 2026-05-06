<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosCustomer;
use App\Models\PosSale;
use App\Services\Pos\PosContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private readonly PosContextService $posContext) {}

    public function index(Request $request): View
    {
        $pos = $this->posContext->currentPos();
        $search = trim((string) $request->query('search', ''));

        $salesCustomers = PosSale::query()
            ->where('pos_account_id', $pos->id)
            ->where(function ($query) {
                $query->whereNotNull('snapshot_customer_name')
                    ->orWhereNotNull('snapshot_customer_phone');
            })
            ->select(
                'snapshot_customer_name as customer_name',
                'snapshot_customer_phone as customer_phone',
                DB::raw('COUNT(id) as sales_count'),
                DB::raw('SUM(total_amount) as total_spent'),
                DB::raw('MAX(sold_at) as last_sale_at')
            )
            ->groupBy('snapshot_customer_name', 'snapshot_customer_phone');

        $manualCustomers = PosCustomer::query()
            ->where('pos_account_id', $pos->id)
            ->selectRaw('name as customer_name, phone as customer_phone, 0 as sales_count, 0 as total_spent, null as last_sale_at');

        $customers = DB::query()
            ->fromSub($salesCustomers->union($manualCustomers), 'customer_rows')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            })
            ->selectRaw('customer_name, customer_phone, SUM(sales_count) as sales_count, SUM(total_spent) as total_spent, MAX(last_sale_at) as last_sale_at')
            ->groupBy('customer_name', 'customer_phone')
            ->orderByDesc('total_spent')
            ->orderByDesc('last_sale_at')
            ->paginate(20)
            ->withQueryString();

        return view('pos.customers.index', compact('pos', 'customers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $pos = $this->posContext->currentPos();

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        PosCustomer::query()->updateOrCreate(
            [
                'pos_account_id' => $pos->id,
                'phone' => $data['customer_phone'],
            ],
            [
                'name' => $data['customer_name'],
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
            ]
        );

        return redirect()->route('pos.customers.index')->with('success', 'تمت إضافة العميل بنجاح.');
    }
}
