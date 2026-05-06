<?php

namespace App\Http\Controllers\Distribution\Distributor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HandlesParallelDashboardSessions;
use App\Models\Catalog\Category;
use App\Models\Catalog\Product;
use App\Models\Finance\Account;
use App\Models\Distribution\DistributorAccount;
use App\Models\Distribution\Distributor;
use App\Models\Orders\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DistributorAuthController extends Controller
{
    use HandlesParallelDashboardSessions;

    public function showLoginForm(): View
    {
        return view('distributor.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'phone.required' => 'رقم الهاتف مطلوب.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        if (Auth::guard('distributor')->attempt([
            'phone' => $credentials['phone'],
            'password' => $credentials['password'],
            'status' => Account::STATUS_ACTIVE,
        ])) {
            $this->regenerateForParallelDashboards($request);

            return redirect()->intended(route('distributor.dashboard'));
        }

        return back()->withErrors([
            'phone' => 'بيانات الدخول غير صحيحة أو لا تملك صلاحية مندوب.',
        ])->onlyInput('phone');
    }

    public function dashboard(): View
    {
        $account = Auth::guard('distributor')->user();

        if (! $account instanceof DistributorAccount) {
            abort(403);
        }

        $distributor = Distributor::query()
            ->with(['branch:id,name', 'supplier:id,owner_name,business_name,logo'])
            ->whereKey($account->distributor_id)
            ->firstOrFail();

        $ordersQuery = $distributor->orders();

        $stats = [
            'orders_count' => (clone $ordersQuery)->count(),
            'pending_orders_count' => (clone $ordersQuery)
                ->whereIn('status', [
                    Order::STATUS_PENDING,
                    Order::STATUS_APPROVED,
                    Order::STATUS_ASSIGNED,
                    Order::STATUS_OUT_FOR_DELIVERY,
                ])
                ->count(),
            'delivered_orders_count' => (clone $ordersQuery)->where('status', Order::STATUS_DELIVERED)->count(),
            'today_collections' => (float) $distributor->payments()
                ->whereDate('created_at', now()->toDateString())
                ->sum('amount'),
        ];

        $recentOrders = (clone $ordersQuery)
            ->latest()
            ->limit(8)
            ->get(['id', 'snapshot_customer_name', 'snapshot_customer_phone', 'total_price', 'status', 'created_at']);

        return view('distributor.dashboard', compact('distributor', 'stats', 'recentOrders'));
    }

    public function products(Request $request): View
    {
        $account = Auth::guard('distributor')->user();

        if (! $account instanceof DistributorAccount) {
            abort(403);
        }

        $distributor = Distributor::query()->whereKey($account->distributor_id)->firstOrFail();

        $search = trim((string) $request->query('search', ''));
        $categoryId = (int) $request->query('category_id', 0);

        $categories = Category::query()
            ->whereIn('id', function ($query) use ($distributor) {
                $query->from('products')
                    ->select('category_id')
                    ->where('supplier_id', $distributor->supplier_id)
                    ->where('status', 'active')
                    ->distinct();
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::with(['category', 'productUnits.unit', 'productVariants.variantValue.type'])
            ->where('supplier_id', $distributor->supplier_id)
            ->where('status', 'active')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%");
                });
            })
            ->when($categoryId > 0, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('distributor.products.index', compact('distributor', 'products', 'categories'));
    }

    public function showProduct(Product $product): View
    {
        $account = Auth::guard('distributor')->user();

        if (! $account instanceof DistributorAccount) {
            abort(403);
        }

        $distributor = Distributor::query()->whereKey($account->distributor_id)->firstOrFail();

        abort_unless($product->supplier_id === $distributor->supplier_id && $product->status === 'active', 404);

        $product->load(['category', 'supplier', 'productUnits.unit', 'productVariants.variantValue.type']);

        return view('distributor.products.show', compact('distributor', 'product'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('distributor')->logout();
        $this->invalidateForParallelDashboards($request);

        return redirect()->route('login');
    }
}
