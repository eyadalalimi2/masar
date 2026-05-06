<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HandlesParallelDashboardSessions;
use App\Http\Requests\Supplier\WorkingHoursRequest;
use App\Models\Customer\Customer;
use App\Models\Finance\Account;
use App\Models\Finance\CustomerAccount;
use App\Models\Orders\Order;
use App\Models\Orders\Order as CustomerOrder;
use App\Models\PosSale;
use App\Services\Notifications\WebAlertService;
use App\Services\Customer\CustomerService;
use App\Services\Pos\PosContextService;
use App\Support\WorkingHoursCodec;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PosPortalAuthController extends Controller
{
    use HandlesParallelDashboardSessions;

    public function __construct(
        private readonly PosContextService $posContext,
        private readonly WebAlertService $webAlertService,
        private readonly CustomerService $customerService,
    ) {}

    public function showLoginForm(): View
    {
        return view('customer.auth.login', [
            'portalTitle' => 'تسجيل دخول المحلات التجارية',
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

    public function profile(): View
    {
        $pos = $this->posContext->currentPos();
        $customer = $this->posContext->resolveOrCreateCustomer($pos);

        return view('pos.profile', compact('pos', 'customer'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $pos = $this->posContext->currentPos();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30', Rule::unique('accounts', 'phone')->where(fn($q) => $q->where('account_type', 'pos'))->ignore($pos->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:500'],
            'gps_location' => ['nullable', 'string', 'max:120'],
            'owner_name' => ['nullable', 'string', 'max:120'],
            'owner_image' => ['nullable', 'image', 'max:5120'],
            'logo' => ['nullable', 'image', 'max:5120'],
            'store_images' => ['nullable', 'array'],
            'store_images.*' => ['image', 'max:5120'],
            'national_id_number' => ['nullable', 'string', 'max:255'],
            'commercial_reg_number' => ['nullable', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'national_id_image' => ['nullable', 'image', 'max:5120'],
            'commercial_reg_image' => ['nullable', 'image', 'max:5120'],
            'license_image' => ['nullable', 'image', 'max:5120'],
        ]);

        $customerMediaPayload = array_filter([
            'owner_image' => $data['owner_image'] ?? null,
            'logo' => $data['logo'] ?? null,
            'store_images' => $data['store_images'] ?? null,
        ], fn($value) => $value !== null);

        unset($data['owner_image'], $data['logo'], $data['store_images']);

        if (is_string($data['password'] ?? null)) {
            $data['password'] = trim($data['password']);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $accountUpdates = [
            'name' => $data['name'],
            'phone' => $data['phone'],
        ];

        if (array_key_exists('password', $data)) {
            $accountUpdates['password'] = $data['password'];
        }

        $pos->update($accountUpdates);
        $pos->refresh();

        $customer = $this->posContext->resolveOrCreateCustomer($pos);

        $customerPayload = [
            'type' => 'retail_store',
            'name' => $pos->name,
            'phone' => $pos->phone,
            'whatsapp' => $pos->whatsapp,
            'address' => $pos->address,
            'gps_location' => $pos->gps_location,
            'owner_name' => $pos->owner_name,
            'national_id_number' => $pos->national_id_number,
            'commercial_reg_number' => $pos->commercial_reg_number,
            'license_number' => $pos->license_number,
            'status' => $pos->status,
        ];

        if (array_key_exists('password', $data)) {
            $customerPayload['password'] = $data['password'];
        }

        if (array_key_exists('national_id_image', $data)) {
            $customerPayload['national_id_image'] = $data['national_id_image'];
        }
        if (array_key_exists('commercial_reg_image', $data)) {
            $customerPayload['commercial_reg_image'] = $data['commercial_reg_image'];
        }
        if (array_key_exists('license_image', $data)) {
            $customerPayload['license_image'] = $data['license_image'];
        }

        $customer = $this->customerService->update($customer, array_merge($customerPayload, $customerMediaPayload));

        CustomerAccount::query()
            ->where('owner_id', $customer->id)
            ->update(['name' => $pos->name]);

        return back()->with('success', 'تم تحديث بيانات المحل بنجاح.');
    }

    public function updateWorkingHours(WorkingHoursRequest $request): RedirectResponse
    {
        $pos = $this->posContext->currentPos();
        $customer = $this->posContext->resolveOrCreateCustomer($pos);

        $workingHours = $request->input('working_hours');
        if (is_array($workingHours)) {
            $workingHours = WorkingHoursCodec::encode($workingHours);
        }

        $customer->update([
            'working_hours' => (string) $workingHours,
        ]);

        return back()->with('success', 'تم تحديث أوقات الدوام بنجاح.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('pos')->logout();
        $this->invalidateForParallelDashboards($request);

        return redirect()->route('login');
    }
}
