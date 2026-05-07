<?php

namespace App\Http\Controllers\Distributor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HandlesParallelDashboardSessions;
use App\Models\Catalog\Category;
use App\Models\Catalog\Product;
use App\Models\Distribution\DistributorOrderEvent;
use App\Models\Distribution\Distributor;
use App\Models\Distribution\DistributorAccount;
use App\Models\Finance\Account;
use App\Models\Notifications\WebAlert;
use App\Models\Orders\Order;
use App\Services\Distribution\DistributorService;
use App\Services\Notifications\WebAlertService;
use App\Support\Validation\UniqueUserContact;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    use HandlesParallelDashboardSessions;

    public function __construct(
        private readonly WebAlertService $webAlertService,
        private readonly DistributorService $distributorService,
    ) {}

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

            return redirect()->route('distributor.dashboard');
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

        $today = now()->toDateString();

        $stats = [
            'orders_count' => (clone $ordersQuery)->count(),
            'new_assigned_count' => (clone $ordersQuery)
                ->where('distributor_stage', Order::STATUS_ASSIGNED)
                ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_DELIVERED])
                ->count(),
            'in_delivery_count' => (clone $ordersQuery)
                ->whereIn('distributor_stage', [Order::STATUS_ACCEPTED, Order::STATUS_PICKED_UP, Order::STATUS_OUT_FOR_DELIVERY])
                ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_DELIVERED])
                ->count(),
            'delivered_orders_count' => (clone $ordersQuery)->where('status', Order::STATUS_DELIVERED)->count(),
            'today_orders_count' => (clone $ordersQuery)->whereDate('created_at', $today)->count(),
            'today_collections' => (float) $distributor->payments()
                ->whereDate('order_payments.created_at', $today)
                ->sum('amount'),
        ];

        $dailyActivity = DistributorOrderEvent::query()
            ->where('distributor_id', $distributor->id)
            ->whereDate('created_at', $today)
            ->selectRaw('MIN(created_at) as first_at, MAX(created_at) as last_at')
            ->first();

        $completedToday = DistributorOrderEvent::query()
            ->where('distributor_id', $distributor->id)
            ->where('stage', Order::STATUS_DELIVERED)
            ->whereDate('created_at', $today)
            ->distinct('order_id')
            ->count('order_id');

        $acceptedToday = DistributorOrderEvent::query()
            ->where('distributor_id', $distributor->id)
            ->where('stage', Order::STATUS_ACCEPTED)
            ->whereDate('created_at', $today)
            ->distinct('order_id')
            ->count('order_id');

        $workHours = 0.0;
        if (! empty($dailyActivity?->first_at) && ! empty($dailyActivity?->last_at)) {
            $minutes = max(0, Carbon::parse($dailyActivity->last_at)->diffInMinutes(Carbon::parse($dailyActivity->first_at)));
            $workHours = round($minutes / 60, 2);
        }

        $performanceRate = $acceptedToday > 0
            ? round(($completedToday / $acceptedToday) * 100, 1)
            : 0.0;

        $onTimeMinutes = max((int) env('DISTRIBUTOR_ON_TIME_MINUTES', 120), 1);
        $deliveriesToday = DistributorOrderEvent::query()
            ->where('distributor_id', $distributor->id)
            ->where('stage', 'delivered')
            ->whereDate('created_at', $today)
            ->get(['order_id', 'created_at']);

        $onTimeDeliveriesToday = 0;
        $lateDeliveriesToday = 0;

        foreach ($deliveriesToday as $deliveredEvent) {
            $outForDeliveryEvent = DistributorOrderEvent::query()
                ->where('distributor_id', $distributor->id)
                ->where('order_id', $deliveredEvent->order_id)
                ->where('stage', Order::STATUS_OUT_FOR_DELIVERY)
                ->latest('id')
                ->first(['created_at']);

            if (! $outForDeliveryEvent) {
                $lateDeliveriesToday++;
                continue;
            }

            $diffMinutes = Carbon::parse($deliveredEvent->created_at)
                ->diffInMinutes(Carbon::parse($outForDeliveryEvent->created_at));

            if ($diffMinutes <= $onTimeMinutes) {
                $onTimeDeliveriesToday++;
            } else {
                $lateDeliveriesToday++;
            }
        }

        $deliverySamples = $onTimeDeliveriesToday + $lateDeliveriesToday;
        $onTimeRateToday = $deliverySamples > 0
            ? round(($onTimeDeliveriesToday / $deliverySamples) * 100, 1)
            : 0.0;

        $activity = [
            'completed_today' => $completedToday,
            'accepted_today' => $acceptedToday,
            'work_hours' => $workHours,
            'performance_rate' => $performanceRate,
            'on_time_deliveries_today' => $onTimeDeliveriesToday,
            'late_deliveries_today' => $lateDeliveriesToday,
            'on_time_rate_today' => $onTimeRateToday,
        ];

        $lowPerformanceThreshold = (float) env('DISTRIBUTOR_LOW_PERFORMANCE_RATE', 70);
        if ($deliverySamples >= 3 && $onTimeRateToday < $lowPerformanceThreshold) {
            $alreadyAlertedToday = WebAlert::query()
                ->where('recipient_type', 'distributor_account')
                ->where('recipient_id', (int) $account->id)
                ->whereDate('created_at', $today)
                ->where('title', 'تنبيه أداء المندوب')
                ->exists();

            if (! $alreadyAlertedToday) {
                $this->webAlertService->create(
                    'distributor_account',
                    (int) $account->id,
                    'تنبيه أداء المندوب',
                    'انخفض معدل التسليم ضمن الوقت اليوم إلى ' . $onTimeRateToday . '%. يلزم تحسين زمن التسليم.',
                    [
                        'type' => 'distributor_performance_alert',
                        'on_time_rate_today' => $onTimeRateToday,
                        'threshold' => $lowPerformanceThreshold,
                        'sample_deliveries' => $deliverySamples,
                    ]
                );
            }
        }

        $recentAlerts = $this->webAlertService->getRecent('distributor_account', (int) $account->id, 8);
        $unreadAlertsCount = $this->webAlertService->unreadCount('distributor_account', (int) $account->id);

        $recentOrders = (clone $ordersQuery)
            ->latest()
            ->limit(8)
            ->get(['id', 'snapshot_customer_name', 'snapshot_customer_phone', 'snapshot_customer_address', 'total_price', 'status', 'distributor_stage', 'created_at']);

        return view('distributor.dashboard', compact('distributor', 'stats', 'recentOrders', 'activity', 'recentAlerts', 'unreadAlertsCount'));
    }

    public function profile(): View
    {
        $account = Auth::guard('distributor')->user();

        if (! $account instanceof DistributorAccount) {
            abort(403);
        }

        $distributor = Distributor::query()
            ->with(['branch:id,name', 'supplier:id,owner_name,business_name,logo'])
            ->whereKey($account->distributor_id)
            ->firstOrFail();

        return view('distributor.profile', compact('distributor'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $account = Auth::guard('distributor')->user();

        if (! $account instanceof DistributorAccount) {
            abort(403);
        }

        $distributor = Distributor::query()->whereKey($account->distributor_id)->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:20',
                new UniqueUserContact('phone', [
                    UniqueUserContact::ignore('accounts', $account->id),
                    UniqueUserContact::ignore('distributors', $distributor->id),
                ]),
            ],
            'password' => ['nullable', 'string', 'min:6'],
            'image' => ['nullable', 'image', 'max:4096'],
            'vehicle_type' => ['nullable', 'string', 'max:255'],
            'distribution_points' => ['nullable', 'string', 'max:2000'],
        ], [
            'phone.unique' => 'رقم الهاتف مستخدم مسبقًا.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 6 أحرف.',
            'image.image' => 'الصورة يجب أن تكون صورة صحيحة.',
            'image.max' => 'الصورة يجب ألا تتجاوز 4MB.',
        ]);

        $this->distributorService->update($distributor, array_merge($data, [
            'supplier_id' => $distributor->supplier_id,
            'branch_id' => $distributor->branch_id,
            'status' => $distributor->status,
        ]));

        return redirect()->route('distributor.profile')->with('success', 'تم تحديث بيانات الحساب بنجاح.');
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
