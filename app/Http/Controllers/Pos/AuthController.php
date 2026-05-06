<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HandlesParallelDashboardSessions;
use App\Models\Finance\Account;
use App\Models\Orders\Order;
use App\Models\Orders\Order as CustomerOrder;
use App\Models\PosSale;
use App\Services\Notifications\WebAlertService;
use App\Services\Pos\PosInventoryInsightService;
use App\Services\Pos\PosContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuthController extends Controller
{
    use HandlesParallelDashboardSessions;

    public function __construct(
        private readonly PosContextService $posContext,
        private readonly WebAlertService $webAlertService,
        private readonly PosInventoryInsightService $inventoryInsightService,
    ) {}

    public function showLoginForm(): View
    {
        return view('customer.auth.login', [
            'portalSubtitle' => 'الدخول إلى لوحة المحل التجاري الخاصة بك',
            'submitRoute' => 'pos.login.submit',
            'defaultPhone' => '770450601',
            'defaultPassword' => '123456',
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('pos')->attempt([
            'phone' => $credentials['phone'],
            'password' => $credentials['password'],
            'status' => Account::STATUS_ACTIVE,
        ])) {
            $this->regenerateForParallelDashboards($request);

            return redirect()->route('pos.dashboard');
        }

        return back()->withErrors([
            'phone' => 'بيانات الدخول غير صحيحة أو الحساب غير نشط.',
        ])->onlyInput('phone');
    }

    public function dashboard(): View
    {
        $pos = $this->posContext->currentPos();
        $customer = $this->posContext->resolveOrCreateCustomer($pos);

        $ordersQuery = Order::query()
            ->where('buyer_type', CustomerOrder::BUYER_TYPE_CUSTOMER)
            ->where('buyer_id', $customer->id);

        $stats = [
            'new_orders' => (clone $ordersQuery)->where('status', Order::STATUS_PENDING)->count(),
            'today_sales' => (float) PosSale::query()->where('pos_account_id', $pos->id)->whereDate('sold_at', now()->toDateString())->sum('total_amount'),
            'today_profit' => (float) PosSale::query()->where('pos_account_id', $pos->id)->whereDate('sold_at', now()->toDateString())->sum('profit_amount'),
            'active_orders' => (clone $ordersQuery)->whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_APPROVED,
                Order::STATUS_ASSIGNED,
                Order::STATUS_OUT_FOR_DELIVERY,
            ])->count(),
            'delivered_orders' => (clone $ordersQuery)->where('status', Order::STATUS_DELIVERED)->count(),
        ];

        $inventoryInsights = $this->inventoryInsightService->insightsForPos($pos);
        $stats['predicted_stockout_count'] = (int) $inventoryInsights
            ->filter(fn(array $insight) => (bool) $insight['needs_refill'])
            ->count();

        $topProducts = PosSale::query()
            ->where('pos_account_id', $pos->id)
            ->select('product_name', DB::raw('SUM(quantity) as sold_quantity'))
            ->groupBy('product_name')
            ->orderByDesc('sold_quantity')
            ->limit(5)
            ->get();

        $recentAlerts = $this->webAlertService->getRecent('pos_account', $pos->id, 6);
        $unreadAlertsCount = $this->webAlertService->unreadCount('pos_account', $pos->id);

        return view('pos.dashboard', compact('pos', 'stats', 'topProducts', 'recentAlerts', 'unreadAlertsCount'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('pos')->logout();
        $this->invalidateForParallelDashboards($request);

        return redirect()->route('login');
    }
}
