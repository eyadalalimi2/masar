<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HandlesParallelDashboardSessions;
use App\Http\Requests\Supplier\WorkingHoursRequest;
use App\Models\Customer\Customer;
use App\Models\Workshop\WorkshopAppointment;
use App\Models\Workshop\WorkshopPurchaseOrder;
use App\Models\Workshop\WorkshopServiceOrder;
use App\Services\Customer\CustomerService;
use App\Support\Validation\UniqueUserContact;
use App\Support\WorkingHoursCodec;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkshopPortalAuthController extends Controller
{
    use HandlesParallelDashboardSessions;

    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    public function showLoginForm(): View
    {
        return view('workshop.auth.login', [
            'defaultPhone' => '770450401',
            'defaultPassword' => '123456',
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('workshop')->attempt([
            'phone' => $credentials['phone'],
            'password' => $credentials['password'],
            'status' => 'active',
        ])) {
            $this->regenerateForParallelDashboards($request);

            return redirect()->route('workshop.dashboard');
        }

        return back()->withErrors([
            'phone' => 'بيانات الدخول غير صحيحة أو الحساب غير نشط.',
        ])->onlyInput('phone');
    }

    public function dashboard(): View
    {
        $workshop = Auth::guard('workshop')->user();

        $workshopId = (int) $workshop->id;

        $metrics = [
            'new_service_orders' => WorkshopServiceOrder::query()
                ->where('workshop_id', $workshopId)
                ->where('status', WorkshopServiceOrder::STATUS_REQUESTED)
                ->count(),
            'today_appointments' => WorkshopAppointment::query()
                ->where('workshop_id', $workshopId)
                ->whereDate('appointment_at', now()->toDateString())
                ->count(),
            'today_completed_services' => WorkshopServiceOrder::query()
                ->where('workshop_id', $workshopId)
                ->where('status', WorkshopServiceOrder::STATUS_COMPLETED)
                ->whereDate('updated_at', now()->toDateString())
                ->count(),
            'today_revenue' => (float) WorkshopServiceOrder::query()
                ->where('workshop_id', $workshopId)
                ->where('status', WorkshopServiceOrder::STATUS_COMPLETED)
                ->whereDate('updated_at', now()->toDateString())
                ->sum(DB::raw('COALESCE(payable_total, total_amount)')),
            'pending_purchase_orders' => WorkshopPurchaseOrder::query()
                ->where('workshop_id', $workshopId)
                ->whereIn('status', [
                    WorkshopPurchaseOrder::STATUS_PENDING,
                    WorkshopPurchaseOrder::STATUS_APPROVED,
                    WorkshopPurchaseOrder::STATUS_IN_TRANSIT,
                ])
                ->count(),
        ];

        $upcomingAppointments = WorkshopAppointment::query()
            ->with('service')
            ->where('workshop_id', $workshopId)
            ->whereIn('status', [
                WorkshopAppointment::STATUS_SCHEDULED,
                WorkshopAppointment::STATUS_IN_PROGRESS,
            ])
            ->orderBy('appointment_at')
            ->limit(8)
            ->get();

        return view('workshop.dashboard', compact('workshop', 'metrics', 'upcomingAppointments'));
    }

    public function profile(): View
    {
        $workshop = Auth::guard('workshop')->user();

        return view('workshop.profile', compact('workshop'));
    }

    public function verification(): View
    {
        $workshop = Auth::guard('workshop')->user();
        $customer = $this->resolveWorkshopCustomer($workshop);

        return view('workshop.verification', compact('workshop', 'customer'));
    }

    public function updateVerification(Request $request): RedirectResponse
    {
        $workshop = Auth::guard('workshop')->user();
        $customer = $this->resolveWorkshopCustomer($workshop);

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
        $workshop = Auth::guard('workshop')->user();
        $customer = $this->resolveWorkshopCustomer($workshop);

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
            'verification_requested_by_user_id' => (int) ($workshop->id ?? 0),
        ]);

        return back()->with('status', 'تم إرسال طلب التوثيق بنجاح.');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $workshop = Auth::guard('workshop')->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30', new UniqueUserContact('phone', [
                UniqueUserContact::ignore('accounts', $workshop->id),
                UniqueUserContact::ignore('customers', (int) ($workshop->customer_id ?? 0) ?: null),
            ])],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:500'],
            'gps_location' => ['nullable', 'string', 'max:120'],
            'owner_name' => ['nullable', 'string', 'max:120'],
        ]);

        $accountUpdates = [
            'name' => $data['name'],
            'phone' => $data['phone'],
        ];

        $workshop->update($accountUpdates);

        $customer = null;

        if ((int) ($workshop->customer_id ?? 0) > 0) {
            $customer = Customer::query()
                ->whereKey((int) $workshop->customer_id)
                ->where('type', 'workshop')
                ->first();
        }

        if (! $customer) {
            $customer = Customer::query()->firstOrCreate(
                [
                    'phone' => $workshop->phone,
                ],
                [
                    'type' => 'workshop',
                    'name' => $workshop->name,
                    'whatsapp' => $workshop->whatsapp,
                    'address' => $workshop->address ?: 'غير محدد',
                    'gps_location' => $workshop->gps_location,
                    'owner_name' => $workshop->owner_name,
                    'status' => $workshop->status,
                ]
            );
        }

        $customer->update([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'whatsapp' => $data['whatsapp'] ?? null,
            'address' => $data['address'],
            'gps_location' => $data['gps_location'] ?? null,
            'owner_name' => $data['owner_name'] ?? null,
            'status' => $workshop->status,
        ]);

        if ((int) ($workshop->customer_id ?? 0) !== (int) $customer->id) {
            $workshop->update(['customer_id' => $customer->id]);
        }

        return back()->with('status', 'تم تحديث الملف الشخصي بنجاح.');
    }

    public function updateWorkingHours(WorkingHoursRequest $request): RedirectResponse
    {
        $workshop = Auth::guard('workshop')->user();

        $workingHours = $request->input('working_hours');
        if (is_array($workingHours)) {
            $workingHours = WorkingHoursCodec::encode($workingHours);
        }

        $customer = null;

        if ((int) ($workshop->customer_id ?? 0) > 0) {
            $customer = Customer::query()
                ->whereKey((int) $workshop->customer_id)
                ->where('type', 'workshop')
                ->first();
        }

        if (! $customer) {
            $customer = Customer::query()->firstOrCreate(
                [
                    'phone' => $workshop->phone,
                ],
                [
                    'type' => 'workshop',
                    'name' => $workshop->name,
                    'whatsapp' => $workshop->whatsapp,
                    'address' => $workshop->address ?: 'غير محدد',
                    'gps_location' => $workshop->gps_location,
                    'owner_name' => $workshop->owner_name,
                    'status' => $workshop->status,
                ]
            );
        }

        $customer->update([
            'working_hours' => (string) $workingHours,
        ]);

        if ((int) ($workshop->customer_id ?? 0) !== (int) $customer->id) {
            $workshop->update(['customer_id' => $customer->id]);
        }

        return back()->with('status', 'تم تحديث أوقات الدوام بنجاح.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('workshop')->logout();
        $this->invalidateForParallelDashboards($request);

        return redirect()->route('login');
    }

    private function resolveWorkshopCustomer(mixed $workshop): Customer
    {
        if ((int) ($workshop->customer_id ?? 0) > 0) {
            $customer = Customer::query()
                ->whereKey((int) $workshop->customer_id)
                ->where('type', 'workshop')
                ->first();

            if ($customer) {
                return $customer;
            }
        }

        $customer = Customer::query()->firstOrCreate(
            [
                'phone' => $workshop->phone,
            ],
            [
                'type' => 'workshop',
                'name' => $workshop->name,
                'whatsapp' => $workshop->whatsapp,
                'address' => $workshop->address ?: 'غير محدد',
                'gps_location' => $workshop->gps_location,
                'owner_name' => $workshop->owner_name,
                'status' => $workshop->status,
            ]
        );

        if ((int) ($workshop->customer_id ?? 0) !== (int) $customer->id) {
            $workshop->update(['customer_id' => $customer->id]);
        }

        return $customer;
    }
}
