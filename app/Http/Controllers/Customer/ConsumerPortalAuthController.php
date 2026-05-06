<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HandlesParallelDashboardSessions;
use App\Models\Finance\Account;
use App\Models\Orders\Order;
use App\Models\Orders\Order as CustomerOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ConsumerPortalAuthController extends Controller
{
    use HandlesParallelDashboardSessions;

    public function showLoginForm(): View
    {
        return view('consumer.auth.login');
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

        if (Auth::guard('consumer')->attempt([
            'phone' => $credentials['phone'],
            'password' => $credentials['password'],
            'status' => Account::STATUS_ACTIVE,
        ])) {
            $this->regenerateForParallelDashboards($request);

            return redirect()->route('consumer.dashboard');
        }

        return back()->withErrors([
            'phone' => 'بيانات الدخول غير صحيحة أو الحساب غير نشط.',
        ])->onlyInput('phone');
    }

    public function dashboard(): View
    {
        $consumer = Auth::guard('consumer')->user();

        $ordersQuery = Order::query()
            ->where('buyer_type', CustomerOrder::BUYER_TYPE_CONSUMER)
            ->where('buyer_id', $consumer->id);

        $stats = [
            'orders_count' => (clone $ordersQuery)->count(),
            'pending_count' => (clone $ordersQuery)->whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_APPROVED,
                Order::STATUS_ASSIGNED,
                Order::STATUS_OUT_FOR_DELIVERY,
            ])->count(),
            'delivered_count' => (clone $ordersQuery)->where('status', Order::STATUS_DELIVERED)->count(),
            'total_spend' => (float) (clone $ordersQuery)->sum(DB::raw('COALESCE(payable_total, total_price)')),
        ];

        $recentOrders = (clone $ordersQuery)
            ->latest()
            ->limit(10)
            ->get(['id', 'supplier_id', 'seller_type', 'seller_id', 'total_price', 'payable_total', 'status', 'created_at']);

        return view('consumer.dashboard', compact('consumer', 'stats', 'recentOrders'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('consumer')->logout();
        $this->invalidateForParallelDashboards($request);

        return redirect()->route('login');
    }
}
