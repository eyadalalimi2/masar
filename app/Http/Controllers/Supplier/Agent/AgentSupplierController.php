<?php

namespace App\Http\Controllers\Supplier\Agent;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HandlesParallelDashboardSessions;
use App\Models\Catalog\Product;
use App\Models\Distribution\Branch;
use App\Models\Distribution\Distributor;
use App\Models\Finance\Payment;
use App\Models\Orders\Order;
use App\Http\Requests\Supplier\SupplierRequest;
use App\Http\Requests\Supplier\WorkingHoursRequest;
use App\Services\Supplier\SupplierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgentSupplierController extends Controller
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

    public function __construct(private readonly SupplierService $supplierService) {}

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
            'status' => 'active',
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
        $supplier = Auth::guard('agent')->user()->supplier;

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
            'paid_payments' => (float) Payment::query()
                ->where('status', Payment::STATUS_PAID)
                ->whereHas('order', function ($query) use ($supplier): void {
                    $query->where('supplier_id', $supplier->id);
                })
                ->sum('amount'),
        ];

        $recentOrders = Order::query()
            ->where('supplier_id', $supplier->id)
            ->latest()
            ->limit(8)
            ->get(['id', 'snapshot_customer_name', 'snapshot_customer_phone', 'total_price', 'payable_total', 'status', 'created_at']);

        return view('agent.dashboard', compact('supplier', 'stats', 'recentOrders'));
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

    public function updateProfile(SupplierRequest $request): RedirectResponse
    {
        $supplier = Auth::guard('agent')->user()->supplier;

        if ($supplier->is_verified) {
            return redirect()->route('agent.profile')->withErrors([
                'profile' => 'تم توثيق بياناتك من الإدارة، ولا يمكن تعديلها بعد التوثيق.',
            ]);
        }

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

        $this->supplierService->requestVerification($supplier->id, (int) Auth::guard('agent')->id());

        return redirect()->route('agent.profile')->with('success', 'تم إرسال طلب التوثيق بنجاح.');
    }
}
