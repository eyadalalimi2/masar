<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\WorkingHoursRequest;
use App\Models\Finance\CustomerAccount;
use App\Models\Finance\Payment;
use App\Models\Finance\Transaction;
use App\Models\Orders\Order;
use App\Models\Orders\Order as CustomerOrder;
use App\Services\Customer\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerPortalController extends Controller
{
    public function __construct(private readonly CustomerService $customerService) {}

    public function orders(Request $request): View
    {
        $customer = Auth::guard('customer')->user();

        $status = (string) $request->query('status', '');
        $sellerType = (string) $request->query('seller_type', '');

        $orders = Order::query()
            ->with(['items.product:id,name', 'branch:id,name', 'distributor:id,name'])
            ->where('buyer_type', CustomerOrder::BUYER_TYPE_CUSTOMER)
            ->where('buyer_id', $customer->id)
            ->when(in_array($status, Order::STATUSES, true), function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->when(in_array($sellerType, ['supplier', 'branch', 'distributor', 'customer'], true), function ($query) use ($sellerType): void {
                $query->where('seller_type', $sellerType);
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('customer.orders.index', compact('customer', 'orders', 'status', 'sellerType'));
    }

    public function payments(Request $request): View
    {
        $customer = Auth::guard('customer')->user();

        $paymentStatus = (string) $request->query('payment_status', '');
        $paymentType = (string) $request->query('payment_type', '');

        $payments = Payment::query()
            ->with(['order:id,buyer_type,buyer_id,total_price,status,created_at', 'distributor:id,name'])
            ->whereHas('order', function ($query) use ($customer): void {
                $query->where('buyer_type', CustomerOrder::BUYER_TYPE_CUSTOMER)
                    ->where('buyer_id', $customer->id);
            })
            ->when(in_array($paymentStatus, Payment::PAYMENT_STATUSES, true), function ($query) use ($paymentStatus): void {
                $query->where('status', $paymentStatus);
            })
            ->when(in_array($paymentType, Payment::PAYMENT_TYPES, true), function ($query) use ($paymentType): void {
                $query->ofPaymentType($paymentType);
            })
            ->latest('paid_at')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $summary = [
            'paid_total' => (float) Payment::query()
                ->whereHas('order', fn($q) => $q->where('buyer_type', CustomerOrder::BUYER_TYPE_CUSTOMER)->where('buyer_id', $customer->id))
                ->where('status', Payment::STATUS_PAID)
                ->sum('amount'),
            'partial_total' => (float) Payment::query()
                ->whereHas('order', fn($q) => $q->where('buyer_type', CustomerOrder::BUYER_TYPE_CUSTOMER)->where('buyer_id', $customer->id))
                ->where('status', Payment::STATUS_PARTIAL)
                ->sum('amount'),
            'records_count' => (int) Payment::query()
                ->whereHas('order', fn($q) => $q->where('buyer_type', CustomerOrder::BUYER_TYPE_CUSTOMER)->where('buyer_id', $customer->id))
                ->count(),
        ];

        return view('customer.payments.index', compact('customer', 'payments', 'paymentStatus', 'paymentType', 'summary'));
    }

    public function profile(): View
    {
        $customer = Auth::guard('customer')->user();

        $account = CustomerAccount::query()->where('owner_id', $customer->id)->first();

        $transactions = collect();
        if ($account) {
            $transactions = Transaction::query()
                ->where('customer_account_id', $account->id)
                ->latest()
                ->limit(12)
                ->get();
        }

        return view('customer.profile.index', compact('customer', 'account', 'transactions'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30', 'unique:customers,phone,' . $customer->id],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:500'],
            'gps_location' => ['nullable', 'string', 'max:120'],
            'owner_name' => ['nullable', 'string', 'max:120'],
            'owner_image' => ['nullable', 'image', 'max:5120'],
            'logo' => ['nullable', 'image', 'max:5120'],
            'store_images' => ['nullable', 'array'],
            'store_images.*' => ['image', 'max:5120'],
        ]);

        $customer = $this->customerService->update($customer, $data);

        CustomerAccount::query()
            ->where('owner_id', $customer->id)
            ->update(['name' => $data['name']]);

        return back()->with('status', 'تم تحديث الملف الشخصي بنجاح.');
    }

    public function updateWorkingHours(WorkingHoursRequest $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $workingHours = $request->input('working_hours');
        if (is_array($workingHours)) {
            $workingHours = json_encode([
                'days' => $workingHours,
            ], JSON_UNESCAPED_UNICODE);
        }

        $customer->update([
            'working_hours' => (string) $workingHours,
        ]);

        return back()->with('status', 'تم تحديث أوقات الدوام بنجاح.');
    }

    public function destroyStoreImage(int $imageIndex): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $removed = $this->customerService->removeStoreImageByIndex($customer, $imageIndex);

        if (! $removed) {
            return back()->withErrors(['store_images' => 'الصورة المحددة غير موجودة.']);
        }

        return back()->with('status', 'تم حذف صورة المحل بنجاح.');
    }
}
