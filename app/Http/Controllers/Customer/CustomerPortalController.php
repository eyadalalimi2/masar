<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\WorkingHoursRequest;
use App\Models\Catalog\Category;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductUnit;
use App\Models\Catalog\Unit;
use App\Models\Finance\CustomerAccount;
use App\Models\Finance\Payment;
use App\Models\Finance\PaymentMethod;
use App\Models\Finance\PortalPaymentMethod;
use App\Models\Finance\Transaction;
use App\Models\Customer\Customer as CustomerModel;
use App\Models\Orders\Order;
use App\Models\Orders\Order as CustomerOrder;
use App\Models\Orders\OrderStatusHistory;
use App\Services\Customer\CustomerService;
use App\Support\Validation\UniqueUserContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
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

    public function verification(): View
    {
        $customer = $this->resolveWholesaleTrader();

        return view('customer.profile.verification', compact('customer'));
    }

    public function updateVerification(Request $request): RedirectResponse
    {
        $customer = $this->resolveWholesaleTrader();

        if ((bool) $customer->is_verified) {
            return back()->withErrors(['verification' => 'تم توثيق الحساب ولا يمكن تعديل وثائق التوثيق بعد القبول.']);
        }

        $data = $request->validate([
            'national_id_number' => ['required', 'string', 'max:255'],
            'commercial_reg_number' => ['required', 'string', 'max:255'],
            'license_number' => ['required', 'string', 'max:255'],
            'national_id_image' => ['nullable', 'image', 'max:5120'],
            'commercial_reg_image' => ['nullable', 'image', 'max:5120'],
            'license_image' => ['nullable', 'image', 'max:5120'],
        ]);

        $this->customerService->update($customer, $data);

        return back()->with('status', 'تم تحديث وثائق التوثيق بنجاح.');
    }

    public function requestVerification(): RedirectResponse
    {
        $customer = $this->resolveWholesaleTrader();

        if ((bool) $customer->is_verified) {
            return back()->with('status', 'الحساب موثّق بالفعل.');
        }

        if ($customer->verification_requested_at !== null) {
            return back()->with('status', 'تم إرسال طلب التوثيق مسبقًا وهو قيد المراجعة.');
        }

        $missing = [
            'رقم البطاقة الشخصية' => $customer->national_id_number,
            'صورة البطاقة الشخصية' => $customer->national_id_image,
            'رقم السجل التجاري' => $customer->commercial_reg_number,
            'صورة السجل التجاري' => $customer->commercial_reg_image,
            'رقم الرخصة' => $customer->license_number,
            'صورة الرخصة' => $customer->license_image,
        ];

        foreach ($missing as $label => $value) {
            if (! is_string($value) || trim($value) === '') {
                return back()->withErrors([
                    'verification' => 'يرجى استكمال حقل ' . $label . ' قبل إرسال طلب التوثيق.',
                ]);
            }
        }

        $customer->update([
            'verification_requested_at' => now(),
            'verification_requested_by_user_id' => (int) (Auth::guard('customer')->id() ?? 0),
        ]);

        return back()->with('status', 'تم إرسال طلب التوثيق بنجاح.');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30', new UniqueUserContact('phone', [
                UniqueUserContact::ignore('customers', $customer->id),
                UniqueUserContact::ignore('accounts', optional(CustomerAccount::query()->where('owner_id', $customer->id)->first())->id),
            ])],
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

    public function wholesaleProducts(Request $request): View
    {
        $customer = $this->resolveWholesaleTrader();

        $search = trim((string) $request->query('search', ''));
        $categoryId = (int) $request->query('category_id', 0);

        $products = Product::query()
            ->with(['category:id,name', 'productUnits.unit:id,name'])
            ->where('status', Product::STATUS_ACTIVE)
            ->whereHas('orderItems.order', function ($query) use ($customer): void {
                $query->whereIn('seller_type', [CustomerModel::class, 'customer'])
                    ->where('seller_id', (int) $customer->id);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('model', 'like', '%' . $search . '%');
                });
            })
            ->when($categoryId > 0, function ($query) use ($categoryId): void {
                $query->where('category_id', $categoryId);
            })
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $categories = Category::query()->orderBy('name')->get(['id', 'name']);

        return view('customer.wholesale.products.index', compact('customer', 'products', 'categories', 'search', 'categoryId'));
    }

    public function wholesaleProductCreate(): View
    {
        $this->resolveWholesaleTrader();

        $categories = Category::query()->orderBy('name')->get(['id', 'name']);
        $units = Unit::query()->orderBy('name')->get(['id', 'name']);

        return view('customer.wholesale.products.create', compact('categories', 'units'));
    }

    public function wholesaleProductStore(Request $request): RedirectResponse
    {
        $customer = $this->resolveWholesaleTrader();

        $supplierId = $this->resolveWholesaleSupplierId($customer);
        if ($supplierId === null) {
            return back()->withInput()->withErrors([
                'product' => 'لا يمكن إضافة منتج الآن لعدم وجود مورد مرتبط بحسابك. تواصل مع الإدارة أولًا.',
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'model' => ['nullable', 'string', 'max:120'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:4000'],
            'image' => ['nullable', 'image', 'max:5120'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'wholesale_price' => ['required', 'numeric', 'min:0'],
            'retail_price' => ['nullable', 'numeric', 'min:0'],
            'conversion_factor' => ['nullable', 'numeric', 'gt:0'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
        ]);

        $imagePath = $request->hasFile('image')
            ? (string) $request->file('image')->store('products', 'public')
            : null;

        $product = Product::query()->create([
            'supplier_id' => $supplierId,
            'category_id' => (int) $validated['category_id'],
            'name' => (string) $validated['name'],
            'model' => (string) ($validated['model'] ?? ''),
            'description' => $this->normalize($validated['description'] ?? null),
            'image' => $imagePath,
            'status' => Product::STATUS_ACTIVE,
        ]);

        ProductUnit::query()->create([
            'product_id' => (int) $product->id,
            'unit_id' => (int) $validated['unit_id'],
            'wholesale_price' => (float) $validated['wholesale_price'],
            'retail_price' => (float) ($validated['retail_price'] ?? $validated['wholesale_price']),
            'conversion_factor' => (float) ($validated['conversion_factor'] ?? 1),
            'stock_quantity' => (float) ($validated['stock_quantity'] ?? 0),
            'low_stock_threshold' => (float) ($validated['low_stock_threshold'] ?? 0),
        ]);

        return redirect()->route('customer.wholesale.products.index')
            ->with('success', 'تمت إضافة المنتج بنجاح.');
    }

    public function wholesaleCustomers(Request $request): View
    {
        $customer = $this->resolveWholesaleTrader();

        $search = trim((string) $request->query('search', ''));

        $buyers = Order::query()
            ->where('seller_type', CustomerModel::class)
            ->where('seller_id', (int) $customer->id)
            ->whereNotNull('snapshot_customer_phone')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('snapshot_customer_name', 'like', '%' . $search . '%')
                        ->orWhere('snapshot_customer_phone', 'like', '%' . $search . '%')
                        ->orWhere('snapshot_customer_address', 'like', '%' . $search . '%');
                });
            })
            ->selectRaw('snapshot_customer_phone as customer_phone')
            ->selectRaw('MAX(snapshot_customer_name) as customer_name')
            ->selectRaw('MAX(snapshot_customer_address) as customer_address')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('SUM(COALESCE(payable_total, total_price)) as total_spent')
            ->selectRaw('MAX(created_at) as last_order_at')
            ->groupBy('snapshot_customer_phone')
            ->orderByDesc('last_order_at')
            ->paginate(15)
            ->withQueryString();

        return view('customer.wholesale.customers.index', compact('customer', 'buyers', 'search'));
    }

    public function wholesaleOrders(Request $request): View
    {
        $customer = $this->resolveWholesaleTrader();

        $status = trim((string) $request->query('status', ''));
        $search = trim((string) $request->query('search', ''));

        $orders = Order::query()
            ->withCount('items')
            ->with(['latestPayment:id,order_id,status,payment_type'])
            ->with(['statusHistories' => function ($query): void {
                $query->select(['id', 'order_id', 'from_status', 'to_status', 'actor_guard', 'actor_id', 'created_at'])
                    ->latest('id');
            }])
            ->whereIn('seller_type', [CustomerModel::class, 'customer'])
            ->where('seller_id', (int) $customer->id)
            ->when(in_array($status, Order::STATUSES, true), function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('snapshot_customer_name', 'like', '%' . $search . '%')
                        ->orWhere('snapshot_customer_phone', 'like', '%' . $search . '%')
                        ->orWhere('snapshot_customer_address', 'like', '%' . $search . '%');
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('customer.wholesale.orders.index', compact('customer', 'orders', 'status', 'search'));
    }

    public function updateWholesaleOrderStatus(Request $request, Order $order): RedirectResponse
    {
        $customer = $this->resolveWholesaleTrader();

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:approved,out_for_delivery,delivered,cancelled'],
        ]);

        $isOwnedOrder = in_array((string) $order->seller_type, [CustomerModel::class, 'customer'], true)
            && (int) $order->seller_id === (int) $customer->id;

        abort_unless($isOwnedOrder, 404);

        $targetStatus = (string) $validated['status'];
        $currentStatus = (string) $order->status;
        $allowedTargets = $this->allowedWholesaleOrderTransitions((string) $order->status);

        if (! in_array($targetStatus, $allowedTargets, true)) {
            return back()->withErrors(['status' => 'لا يمكن تغيير حالة الطلب بهذا الشكل.']);
        }

        $order->update([
            'status' => $targetStatus,
        ]);

        if ($currentStatus !== $targetStatus && Schema::hasTable('order_status_histories')) {
            OrderStatusHistory::query()->create([
                'order_id' => (int) $order->id,
                'from_status' => $currentStatus,
                'to_status' => $targetStatus,
                'actor_guard' => 'customer',
                'actor_id' => (int) $customer->id,
                'changed_at' => now(),
            ]);
        }

        return back()->with('success', 'تم تحديث حالة الطلب بنجاح.');
    }

    public function wholesalePaymentMethods(): View
    {
        $customer = $this->resolveWholesaleTrader();

        $paymentMethods = PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $configuredMethods = PortalPaymentMethod::query()
            ->where('portal_type', 'customer')
            ->where('portal_id', (int) $customer->id)
            ->get()
            ->keyBy('payment_method_id');

        return view('customer.wholesale.payment-methods.index', compact('customer', 'paymentMethods', 'configuredMethods'));
    }

    public function updateWholesalePaymentMethods(Request $request): RedirectResponse
    {
        $customer = $this->resolveWholesaleTrader();

        $validated = $request->validate([
            'methods' => ['nullable', 'array'],
            'methods.*.account_number' => ['nullable', 'string', 'max:120'],
            'methods.*.account_name' => ['nullable', 'string', 'max:120'],
            'methods.*.note' => ['nullable', 'string', 'max:1000'],
            'methods.*.is_enabled' => ['nullable', 'boolean'],
        ]);

        $paymentMethods = PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($paymentMethods as $method) {
            $input = (array) ($validated['methods'][$method->id] ?? []);
            $isCod = $method->type === 'offline';

            PortalPaymentMethod::query()->updateOrCreate(
                [
                    'portal_type' => 'customer',
                    'portal_id' => (int) $customer->id,
                    'payment_method_id' => (int) $method->id,
                ],
                [
                    'account_number' => $isCod ? null : $this->normalize($input['account_number'] ?? null),
                    'account_name' => $isCod ? null : $this->normalize($input['account_name'] ?? null),
                    'note' => $isCod ? null : $this->normalize($input['note'] ?? null),
                    'is_enabled' => (bool) ($input['is_enabled'] ?? false),
                ]
            );
        }

        return back()->with('success', 'تم حفظ طرق الدفع المعتمدة بنجاح.');
    }

    private function resolveWholesaleTrader(): CustomerModel
    {
        $customer = Auth::guard('customer')->user();

        abort_unless($customer instanceof CustomerModel, 403);
        abort_unless($customer->type === 'wholesale_trader', 403);

        return $customer;
    }

    private function normalize(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function allowedWholesaleOrderTransitions(string $currentStatus): array
    {
        return match ($currentStatus) {
            Order::STATUS_PENDING => [Order::STATUS_APPROVED, Order::STATUS_CANCELLED],
            Order::STATUS_APPROVED => [Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_CANCELLED],
            Order::STATUS_OUT_FOR_DELIVERY => [Order::STATUS_DELIVERED],
            default => [],
        };
    }

    private function resolveWholesaleSupplierId(CustomerModel $customer): ?int
    {
        $supplierId = Order::query()
            ->whereIn('seller_type', [CustomerModel::class, 'customer'])
            ->where('seller_id', (int) $customer->id)
            ->whereNotNull('supplier_id')
            ->latest('id')
            ->value('supplier_id');

        if ($supplierId === null) {
            return null;
        }

        return (int) $supplierId;
    }
}
