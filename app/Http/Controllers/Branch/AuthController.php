<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HandlesParallelDashboardSessions;
use App\Http\Requests\Supplier\WorkingHoursRequest;
use App\Models\Catalog\Category;
use App\Models\Catalog\Product;
use App\Models\Finance\Account;
use App\Models\Distribution\Branch;
use App\Models\Distribution\BranchProductStock;
use App\Services\Notifications\WebAlertService;
use App\Services\Distribution\BranchService;
use App\Models\Orders\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    use HandlesParallelDashboardSessions;

    public function __construct(
        private readonly BranchService $branchService,
        private readonly WebAlertService $webAlertService,
    ) {}

    public function showLoginForm(): View
    {
        return view('branch.auth.login');
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

        if (Auth::guard('branch')->attempt([
            'phone' => $credentials['phone'],
            'password' => $credentials['password'],
            'status' => Account::STATUS_ACTIVE,
        ])) {
            $this->regenerateForParallelDashboards($request);

            return redirect()->route('branch.dashboard');
        }

        return back()->withErrors([
            'phone' => 'بيانات الدخول غير صحيحة أو لا تملك صلاحية فرع.',
        ])->onlyInput('phone');
    }

    public function dashboard(): View
    {
        $branchAccount = Auth::guard('branch')->user();
        $branch = $this->currentBranch();

        $ordersQuery = Order::query()->where('branch_id', $branch->id);

        $stats = [
            'orders_count' => (clone $ordersQuery)->count(),
            'new_orders_count' => (clone $ordersQuery)->where('status', Order::STATUS_PENDING)->count(),
            'pending_count' => (clone $ordersQuery)->whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_APPROVED,
                Order::STATUS_ASSIGNED,
                Order::STATUS_OUT_FOR_DELIVERY,
            ])->count(),
            'out_for_delivery_count' => (clone $ordersQuery)->where('status', Order::STATUS_OUT_FOR_DELIVERY)->count(),
            'delivered_count' => (clone $ordersQuery)->where('status', Order::STATUS_DELIVERED)->count(),
            'today_sales' => (clone $ordersQuery)->whereDate('created_at', now()->toDateString())->sum(DB::raw('COALESCE(payable_total, total_price)')),
        ];

        $lowStockAlerts = BranchProductStock::query()
            ->with(['product:id,name,model', 'productUnit.unit:id,name'])
            ->where('branch_id', $branch->id)
            ->where('quantity', '<=', 0)
            ->limit(8)
            ->get();

        $topDistributorPerformance = DB::table('orders')
            ->join('distributors', 'distributors.id', '=', 'orders.distributor_id')
            ->where('orders.branch_id', $branch->id)
            ->where('orders.status', Order::STATUS_DELIVERED)
            ->select('distributors.name', DB::raw('COUNT(orders.id) as delivered_orders'))
            ->groupBy('distributors.name')
            ->orderByDesc('delivered_orders')
            ->limit(5)
            ->get();

        $recentOrders = (clone $ordersQuery)
            ->latest()
            ->limit(10)
            ->get();

        $branchDistributors = $branch->distributors()
            ->latest()
            ->limit(12)
            ->get(['id', 'name', 'phone', 'image', 'distribution_points', 'status']);

        $recentAlerts = collect();
        $unreadAlertsCount = 0;

        $branchAccountId = isset($branchAccount->id) ? (int) $branchAccount->id : 0;
        if ($branchAccountId > 0) {
            $recentAlerts = $this->webAlertService->getRecent('branch_account', $branchAccountId, 8);
            $unreadAlertsCount = $this->webAlertService->unreadCount('branch_account', $branchAccountId);
        }

        return view('branch.dashboard', compact(
            'branch',
            'stats',
            'recentOrders',
            'branchDistributors',
            'lowStockAlerts',
            'topDistributorPerformance',
            'recentAlerts',
            'unreadAlertsCount'
        ));
    }

    public function products(Request $request): View
    {
        $branch = $this->currentBranch();

        $search = trim((string) $request->query('search', ''));
        $categoryId = (int) $request->query('category_id', 0);

        $categories = Category::query()
            ->whereIn('id', function ($query) use ($branch) {
                $query->from('products')
                    ->select('category_id')
                    ->where('supplier_id', $branch->supplier_id)
                    ->where('status', 'active')
                    ->distinct();
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::with(['category', 'productUnits.unit', 'productVariants.variantValue.type'])
            ->where('supplier_id', $branch->supplier_id)
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

        return view('branch.products.index', compact('branch', 'products', 'categories'));
    }

    public function showProduct(Product $product): View
    {
        $branch = $this->currentBranch();

        abort_unless($product->supplier_id === $branch->supplier_id && $product->status === 'active', 404);

        $product->load(['category', 'supplier', 'productUnits.unit', 'productVariants.variantValue.type']);

        return view('branch.products.show', compact('branch', 'product'));
    }

    public function profile(): View
    {
        $branch = $this->currentBranch();

        return view('branch.profile', compact('branch'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $branch = $this->currentBranch();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'branch_manager_name' => ['nullable', 'string', 'max:255'],
            'branch_manager_image' => ['nullable', 'image', 'max:4096'],
            'branch_manager_password' => ['nullable', 'string', 'min:6', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'gps_location' => ['nullable', 'string', 'max:255', 'regex:/^\s*-?\d{1,2}(?:\.\d+)?\s*,\s*-?\d{1,3}(?:\.\d+)?\s*$/'],
        ], [
            'name.required' => 'اسم الفرع مطلوب.',
            'branch_manager_image.image' => 'صورة مدير الفرع يجب أن تكون صورة.',
            'branch_manager_image.max' => 'صورة مدير الفرع يجب ألا تتجاوز 4MB.',
            'branch_manager_password.min' => 'كلمة مرور مدير الفرع يجب أن تكون 6 أحرف على الأقل.',
            'gps_location.regex' => 'الموقع يجب أن يكون بصيغة latitude,longitude.',
        ]);

        if ($request->hasFile('branch_manager_image')) {
            $validated['branch_manager_image'] = $request->file('branch_manager_image');
        }

        $this->branchService->update($branch, $validated);

        return redirect()->route('branch.profile')->with('success', 'تم تحديث بيانات البروفايل بنجاح.');
    }

    public function updateWorkingHours(WorkingHoursRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();

        $workingHours = $request->input('working_hours');
        if (is_array($workingHours)) {
            $workingHours = json_encode([
                'days' => $workingHours,
            ], JSON_UNESCAPED_UNICODE);
        }

        $this->branchService->update($branch, [
            'working_hours' => (string) $workingHours,
        ]);

        return redirect()->route('branch.profile')->with('success', 'تم تحديث أوقات الدوام بنجاح.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('branch')->logout();
        $this->invalidateForParallelDashboards($request);

        return redirect()->route('login');
    }

    private function currentBranch(): Branch
    {
        $account = Auth::guard('branch')->user();

        $query = Branch::query()->with(['supplier:id,owner_name,business_name,logo']);

        if ($account && isset($account->branch_id) && (int) $account->branch_id > 0) {
            return $query->whereKey((int) $account->branch_id)->firstOrFail();
        }

        $phone = trim((string) ($account->phone ?? ''));
        if ($phone === '') {
            abort(403);
        }

        return $query->where('phone', $phone)->firstOrFail();
    }
}
