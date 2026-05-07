<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HandlesParallelDashboardSessions;
use App\Models\Catalog\Product;
use App\Models\Distribution\Branch;
use App\Models\Distribution\Distributor;
use App\Models\Finance\Account;
use App\Models\Finance\Payment;
use App\Models\Orders\Order;
use App\Http\Requests\Supplier\SupplierRequest;
use App\Http\Requests\Supplier\WorkingHoursRequest;
use App\Models\Notifications\WebAlert;
use App\Services\Notifications\WebAlertService;
use App\Services\Supplier\SupplierService;
use App\Support\Validation\UniqueUserContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    use HandlesParallelDashboardSessions;

    private const CHANGEABLE_FIELDS = [
        'owner_name',
        'branch_manager_name',
        'email',
        'phone',
        'whatsapp',
        'national_id_number',
        'business_name',
        'gps_location',
        'address',
        'commercial_reg_number',
        'license_number',
        'logo',
        'agent_image',
        'branch_manager_image',
        'national_id_image',
        'commercial_reg_image',
        'license_image',
    ];

    private const IMAGE_CHANGEABLE_FIELDS = [
        'logo',
        'agent_image',
        'branch_manager_image',
        'national_id_image',
        'commercial_reg_image',
        'license_image',
    ];

    public function __construct(
        private readonly SupplierService $supplierService,
        private readonly WebAlertService $webAlertService,
    ) {}

    public function showLoginForm(): View
    {
        return view('agent.auth.login');
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

        if (Auth::guard('agent')->attempt([
            'phone' => $credentials['phone'],
            'password' => $credentials['password'],
            'status' => Account::STATUS_ACTIVE,
        ])) {
            $this->regenerateForParallelDashboards($request);

            return redirect()->route('agent.dashboard');
        }

        return back()->withErrors([
            'phone' => 'بيانات الدخول غير صحيحة أو لا تملك صلاحية وكيل.',
        ])->onlyInput('phone');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('agent')->logout();
        $this->invalidateForParallelDashboards($request);

        return redirect()->route('login');
    }

    public function dashboard(): View
    {
        $agentId = (int) (Auth::guard('agent')->id() ?? 0);
        $supplier = Auth::guard('agent')->user()->supplier;
        $last30Days = now()->subDays(30);
        $delayHoursThreshold = max((int) env('AGENT_ORDER_DELAY_HOURS', 8), 1);

        $delayedOrdersCount = (int) Order::query()
            ->where('supplier_id', $supplier->id)
            ->whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_APPROVED,
                Order::STATUS_ASSIGNED,
                Order::STATUS_OUT_FOR_DELIVERY,
            ])
            ->where('updated_at', '<=', now()->subHours($delayHoursThreshold))
            ->count();

        $stats = [
            'products_count' => Product::query()->where('supplier_id', $supplier->id)->count(),
            'branches_count' => Branch::query()->where('supplier_id', $supplier->id)->count(),
            'distributors_count' => Distributor::query()->where('supplier_id', $supplier->id)->count(),
            'orders_count' => Order::query()->where('supplier_id', $supplier->id)->count(),
            'pending_orders_count' => Order::query()->where('supplier_id', $supplier->id)
                ->whereIn('status', [
                    Order::STATUS_PENDING,
                    Order::STATUS_APPROVED,
                    Order::STATUS_ASSIGNED,
                    Order::STATUS_OUT_FOR_DELIVERY,
                ])
                ->count(),
            'today_sales' => (float) Order::query()->where('supplier_id', $supplier->id)
                ->whereDate('created_at', now()->toDateString())
                ->sum(DB::raw('COALESCE(payable_total, total_price)')),
            'month_sales' => (float) Order::query()->where('supplier_id', $supplier->id)
                ->where('status', Order::STATUS_DELIVERED)
                ->where('created_at', '>=', $last30Days)
                ->sum(DB::raw('COALESCE(payable_total, total_price)')),
            'paid_payments' => (float) Payment::query()
                ->where('status', Payment::STATUS_PAID)
                ->whereHas('order', function ($query) use ($supplier): void {
                    $query->where('supplier_id', $supplier->id);
                })
                ->sum('amount'),
            'delayed_orders_count' => $delayedOrdersCount,
        ];

        $lowStockAlerts = DB::table('product_units')
            ->join('products', 'products.id', '=', 'product_units.product_id')
            ->leftJoin('units', 'units.id', '=', 'product_units.unit_id')
            ->where('products.supplier_id', $supplier->id)
            ->where('product_units.low_stock_threshold', '>', 0)
            ->whereColumn('product_units.stock_quantity', '<=', 'product_units.low_stock_threshold')
            ->select(
                'products.name as product_name',
                'units.name as unit_name',
                'product_units.stock_quantity',
                'product_units.low_stock_threshold'
            )
            ->orderBy('product_units.stock_quantity')
            ->limit(6)
            ->get();

        $lowSalesProducts = Product::query()
            ->where('supplier_id', $supplier->id)
            ->whereNotExists(function ($query) use ($last30Days) {
                $query->select(DB::raw(1))
                    ->from('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->whereColumn('order_items.product_id', 'products.id')
                    ->where('orders.status', Order::STATUS_DELIVERED)
                    ->where('orders.created_at', '>=', $last30Days);
            })
            ->orderBy('name')
            ->limit(6)
            ->get(['id', 'name', 'model']);

        $activeAreas = Order::query()
            ->where('supplier_id', $supplier->id)
            ->where('status', Order::STATUS_DELIVERED)
            ->whereNotNull('snapshot_customer_address')
            ->where('snapshot_customer_address', '!=', '')
            ->select('snapshot_customer_address as customer_address', DB::raw('COUNT(id) as orders_count'))
            ->groupBy('snapshot_customer_address')
            ->orderByDesc('orders_count')
            ->limit(5)
            ->get();

        $recentOrders = Order::query()
            ->where('supplier_id', $supplier->id)
            ->latest()
            ->limit(8)
            ->get(['id', 'snapshot_customer_name', 'snapshot_customer_phone', 'total_price', 'payable_total', 'status', 'created_at']);

        $recentAlerts = collect();
        $unreadAlertsCount = 0;
        $delayAlertsTodayCount = 0;

        if ($agentId > 0) {
            if ($delayedOrdersCount > 0) {
                $title = 'تنبيه تأخير على مستوى الوكيل';
                $body = 'يوجد ' . $delayedOrdersCount . ' طلب متأخر يحتاج تدخل سريع.';

                $existsToday = WebAlert::query()
                    ->where('recipient_type', 'agent')
                    ->where('recipient_id', $agentId)
                    ->whereDate('created_at', now()->toDateString())
                    ->where('title', $title)
                    ->where('body', $body)
                    ->exists();

                if (! $existsToday) {
                    $this->webAlertService->create('agent', $agentId, $title, $body, [
                        'type' => 'agent_dashboard_delay_alert',
                        'delayed_orders_count' => $delayedOrdersCount,
                        'delay_hours_threshold' => $delayHoursThreshold,
                    ]);
                }
            }

            $recentAlerts = $this->webAlertService->getRecent('agent', $agentId, 8);
            $unreadAlertsCount = $this->webAlertService->unreadCount('agent', $agentId);
            $delayAlertsTodayCount = (int) WebAlert::query()
                ->where('recipient_type', 'agent')
                ->where('recipient_id', $agentId)
                ->whereDate('created_at', now()->toDateString())
                ->whereIn('title', ['تنبيه تأخير على مستوى الوكيل', 'تنبيه تأخير طلبات الوكيل'])
                ->count();
        }

        return view('agent.dashboard', compact(
            'supplier',
            'stats',
            'recentOrders',
            'lowStockAlerts',
            'lowSalesProducts',
            'activeAreas',
            'recentAlerts',
            'unreadAlertsCount',
            'delayAlertsTodayCount'
        ));
    }

    public function profile(): View
    {
        $supplier = Auth::guard('agent')->user()->supplier->load([
            'fieldChangeRequests' => function ($query) {
                $query->latest();
            },
        ]);

        return view('agent.profile', compact('supplier'));
    }

    public function verification(): View
    {
        $supplier = Auth::guard('agent')->user()->supplier;

        return view('agent.verification', compact('supplier'));
    }

    public function updateVerification(Request $request): RedirectResponse
    {
        $supplier = Auth::guard('agent')->user()->supplier;

        if ($supplier->is_verified) {
            return redirect()->route('agent.profile.verification')->withErrors([
                'verification' => 'تم توثيق الحساب ولا يمكن تعديل وثائق التوثيق بعد القبول.',
            ]);
        }

        $data = $request->validate([
            'national_id_number' => ['required', 'string', 'max:255'],
            'commercial_reg_number' => ['required', 'string', 'max:255'],
            'license_number' => ['required', 'string', 'max:255'],
            'national_id_image' => ['nullable', 'image', 'max:4096'],
            'commercial_reg_image' => ['nullable', 'image', 'max:4096'],
            'license_image' => ['nullable', 'image', 'max:4096'],
        ]);

        $updates = [
            'national_id_number' => $data['national_id_number'],
            'commercial_reg_number' => $data['commercial_reg_number'],
            'license_number' => $data['license_number'],
        ];

        if ($request->hasFile('national_id_image')) {
            $updates['national_id_image'] = $this->replaceSupplierDocument(
                $request->file('national_id_image'),
                (string) ($supplier->national_id_image ?? ''),
                'suppliers/national-id'
            );
        }

        if ($request->hasFile('commercial_reg_image')) {
            $updates['commercial_reg_image'] = $this->replaceSupplierDocument(
                $request->file('commercial_reg_image'),
                (string) ($supplier->commercial_reg_image ?? ''),
                'suppliers/commercial'
            );
        }

        if ($request->hasFile('license_image')) {
            $updates['license_image'] = $this->replaceSupplierDocument(
                $request->file('license_image'),
                (string) ($supplier->license_image ?? ''),
                'suppliers/license'
            );
        }

        $supplier->update($updates);

        return redirect()->route('agent.profile.verification')->with('success', 'تم تحديث بيانات التوثيق بنجاح.');
    }

    public function updateProfile(SupplierRequest $request): RedirectResponse
    {
        $supplier = Auth::guard('agent')->user()->supplier;

        if ($supplier->is_verified) {
            return redirect()->route('agent.profile')->withErrors([
                'profile' => 'تم توثيق بياناتك من الإدارة، ولا يمكن تعديلها بعد التوثيق.',
            ]);
        }

        $request->validate([
            'phone' => [
                'required',
                'string',
                'max:20',
                new UniqueUserContact('phone', [
                    UniqueUserContact::ignore('agents', Auth::guard('agent')->id()),
                    UniqueUserContact::ignore('suppliers', $supplier->id),
                ]),
            ],
        ], [
            'phone.unique' => 'رقم الهاتف مستخدم مسبقًا.',
        ]);

        $this->supplierService->updateSupplier(array_merge($request->all(), [
            'supplier_id' => $supplier->id,
            'status' => $supplier->status,
        ]));

        return redirect()->route('agent.profile')->with('success', 'تم تحديث الملف الشخصي بنجاح.');
    }

    public function updateWorkingHours(WorkingHoursRequest $request): RedirectResponse
    {
        $supplier = Auth::guard('agent')->user()->supplier;

        $this->supplierService->updateSupplierWorkingHours($supplier->id, (string) $request->input('working_hours'));

        return redirect()->route('agent.profile')->with('success', 'تم تحديث أوقات الدوام بنجاح.');
    }

    public function updateSecurity(Request $request): RedirectResponse
    {
        $agent = Auth::guard('agent')->user();
        $supplier = $agent->supplier;

        $data = $request->validate([
            'agent_image' => ['nullable', 'image', 'max:4096'],
            'new_password' => ['nullable', 'string', 'min:6'],
        ], [
            'agent_image.image' => 'صورة الوكيل يجب أن تكون صورة صحيحة.',
            'agent_image.max' => 'صورة الوكيل يجب ألا تتجاوز 4MB.',
            'new_password.min' => 'كلمة المرور الجديدة يجب ألا تقل عن 6 أحرف.',
        ]);

        if (! $request->hasFile('agent_image') && empty($data['new_password'])) {
            return redirect()->route('agent.profile')->withErrors([
                'security' => 'يرجى اختيار صورة جديدة أو إدخال كلمة مرور جديدة.',
            ]);
        }

        $this->supplierService->updateAgentSecurity(
            (int) $supplier->id,
            (int) $agent->id,
            $request->file('agent_image'),
            $data['new_password'] ?? null,
        );

        return redirect()->route('agent.profile')->with('success', 'تم تحديث صورة الوكيل/كلمة المرور بنجاح.');
    }

    public function requestFieldChange(Request $request): RedirectResponse
    {
        $supplier = Auth::guard('agent')->user()->supplier;

        if (! $supplier->is_verified) {
            return redirect()->route('agent.profile')->withErrors([
                'change_request' => 'يمكنك تعديل الحقول مباشرة قبل التوثيق، ولا حاجة لطلب تعديل.',
            ]);
        }

        $fieldKey = (string) $request->input('field_key');
        $isImageField = in_array($fieldKey, self::IMAGE_CHANGEABLE_FIELDS, true);

        $validated = $request->validate([
            'field_key' => ['required', Rule::in(self::CHANGEABLE_FIELDS)],
            'requested_value' => [Rule::requiredIf(! $isImageField), 'nullable', 'string', 'max:1000'],
            'requested_image' => [Rule::requiredIf($isImageField), 'nullable', 'image', 'max:4096'],
            'note' => ['nullable', 'string', 'max:1000'],
            'document' => ['nullable', 'file', 'max:4096', 'mimes:pdf,jpg,jpeg,png,webp'],
        ], [
            'field_key.required' => 'يرجى اختيار الحقل المطلوب تعديله.',
            'field_key.in' => 'الحقل المحدد غير متاح لطلب التعديل.',
            'requested_value.required' => 'يرجى إدخال القيمة الجديدة المطلوبة.',
            'requested_image.required' => 'يرجى رفع الصورة المطلوبة لهذا الحقل.',
            'requested_image.image' => 'الملف المرفوع يجب أن يكون صورة.',
            'requested_image.max' => 'حجم الصورة المطلوبة يجب ألا يتجاوز 4MB.',
            'document.mimes' => 'نوع الملف غير مدعوم. الأنواع المسموحة: PDF أو صورة.',
            'document.max' => 'حجم المستند يجب ألا يتجاوز 4MB.',
        ]);

        try {
            $this->supplierService->createFieldChangeRequest(
                $supplier->id,
                (int) Auth::guard('agent')->id(),
                $validated['field_key'],
                $validated['requested_value'] ?? null,
                $validated['note'] ?? null,
                $request->file('document'),
                $request->file('requested_image'),
            );
        } catch (\DomainException $exception) {
            return redirect()->route('agent.profile')->withErrors([
                'change_request' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('agent.profile')->with('success', 'تم إرسال طلب تعديل الحقل إلى الإدارة بنجاح.');
    }

    public function requestVerification(): RedirectResponse
    {
        $supplier = Auth::guard('agent')->user()->supplier;

        if ($supplier->is_verified) {
            return redirect()->route('agent.profile')->with('success', 'حسابك موثّق بالفعل.');
        }

        if ($supplier->verification_requested_at) {
            return redirect()->route('agent.profile')->with('success', 'تم إرسال طلب التوثيق مسبقًا وهو قيد المراجعة.');
        }

        $missing = [
            'رقم البطاقة الشخصية' => $supplier->national_id_number,
            'صورة البطاقة الشخصية' => $supplier->national_id_image,
            'رقم السجل التجاري' => $supplier->commercial_reg_number,
            'صورة السجل التجاري' => $supplier->commercial_reg_image,
            'رقم الرخصة' => $supplier->license_number,
            'صورة الرخصة' => $supplier->license_image,
        ];

        foreach ($missing as $label => $value) {
            if (! is_string($value) || trim($value) === '') {
                return redirect()->route('agent.profile.verification')->withErrors([
                    'verification' => 'يرجى استكمال حقل ' . $label . ' قبل إرسال طلب التوثيق.',
                ]);
            }
        }

        $this->supplierService->requestVerification($supplier->id, (int) Auth::guard('agent')->id());

        return redirect()->route('agent.profile')->with('success', 'تم إرسال طلب التوثيق بنجاح.');
    }

    private function replaceSupplierDocument(UploadedFile $file, string $oldPath, string $folder): string
    {
        if ($oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return (string) $file->store($folder, 'public');
    }
}
