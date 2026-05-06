<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Models\Workshop\WorkshopAppointment;
use App\Models\Workshop\WorkshopService;
use App\Models\Workshop\WorkshopServiceOrder;
use App\Support\OptionLists;
use App\Services\Pricing\CommissionEngineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WorkshopServiceOrderController extends Controller
{
    public function __construct(private readonly CommissionEngineService $commissionEngineService) {}

    public function index(): View
    {
        $workshopId = Auth::guard('workshop')->id();

        $orders = WorkshopServiceOrder::query()
            ->with(['service', 'appointment'])
            ->where('workshop_id', $workshopId)
            ->latest()
            ->get();

        $services = WorkshopService::query()
            ->where('workshop_id', $workshopId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $appointments = WorkshopAppointment::query()
            ->where('workshop_id', $workshopId)
            ->whereIn('status', [
                WorkshopAppointment::STATUS_SCHEDULED,
                WorkshopAppointment::STATUS_IN_PROGRESS,
            ])
            ->orderBy('appointment_at')
            ->get();

        return view('workshop.orders.service', compact('orders', 'services', 'appointments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $workshopId = Auth::guard('workshop')->id();

        $data = $request->validate([
            'service_id' => ['nullable', 'integer', 'exists:workshop_services,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:workshop_appointments,id'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'vehicle_plate_number' => ['nullable', 'string', 'max:80'],
            'vehicle_brand' => ['nullable', 'string', 'max:80'],
            'vehicle_model' => ['nullable', 'string', 'max:80'],
            'vehicle_production_year' => ['nullable', 'integer', 'digits:4', 'between:1950,2100'],
            'odometer_km' => ['nullable', 'integer', 'min:0'],
            'service_fee' => ['required', 'numeric', 'min:0'],
            'products_total' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! empty($data['service_id'])) {
            WorkshopService::query()
                ->where('workshop_id', $workshopId)
                ->findOrFail($data['service_id']);
        }

        if (! empty($data['appointment_id'])) {
            WorkshopAppointment::query()
                ->where('workshop_id', $workshopId)
                ->findOrFail($data['appointment_id']);
        }

        $serviceFee = (float) $data['service_fee'];
        $productsTotal = (float) $data['products_total'];
        $baseTotal = $serviceFee + $productsTotal;
        $commission = $this->commissionEngineService->calculate($baseTotal, 'workshop', (int) $workshopId, null);

        WorkshopServiceOrder::create([
            'workshop_id' => $workshopId,
            'service_id' => $data['service_id'] ?? null,
            'appointment_id' => $data['appointment_id'] ?? null,
            'order_number' => $this->generateOrderNumber(),
            'snapshot_customer_name' => $data['customer_name'],
            'snapshot_customer_phone' => $data['customer_phone'],
            'vehicle_plate_number' => $data['vehicle_plate_number'] ?? null,
            'vehicle_brand' => $data['vehicle_brand'] ?? null,
            'vehicle_model' => $data['vehicle_model'] ?? null,
            'vehicle_production_year' => $data['vehicle_production_year'] ?? null,
            'odometer_km' => $data['odometer_km'] ?? null,
            'service_fee' => $serviceFee,
            'products_total' => $productsTotal,
            'total_amount' => $baseTotal,
            'commission_rule_id' => $commission['rule_id'],
            'commission_percent' => $commission['commission_percent'],
            'commission_value' => $commission['commission_value'],
            'platform_service_fee' => $commission['service_fee'],
            'platform_fixed_fee' => $commission['fixed_fee'],
            'payable_total' => $commission['final_amount'],
            'status' => WorkshopServiceOrder::STATUS_REQUESTED,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('status', 'تم إنشاء طلب الخدمة بنجاح.');
    }

    public function updateStatus(Request $request, WorkshopServiceOrder $order): RedirectResponse
    {
        $this->authorizeOwnership($order);

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', OptionLists::WORKSHOP_SERVICE_ORDER_STATUSES)],
        ]);

        $order->update(['status' => $data['status']]);

        return back()->with('status', 'تم تحديث حالة طلب الخدمة.');
    }

    public function updateUsedProducts(Request $request, WorkshopServiceOrder $order): RedirectResponse
    {
        $this->authorizeOwnership($order);

        $data = $request->validate([
            'used_products_text' => ['nullable', 'string', 'max:5000'],
        ]);

        $rows = preg_split('/\r\n|\r|\n/', trim((string) ($data['used_products_text'] ?? '')));
        $usedProducts = [];
        $productsTotal = 0.0;

        foreach ($rows as $row) {
            $line = trim((string) $row);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 3 || $parts[0] === '' || ! is_numeric($parts[1]) || ! is_numeric($parts[2])) {
                return back()->withErrors([
                    'used_products_text' => 'صيغة المنتجات غير صحيحة. استخدم: اسم المنتج | الكمية | سعر الوحدة.',
                ])->withInput();
            }

            $quantity = (float) $parts[1];
            $unitCost = (float) $parts[2];

            if ($quantity <= 0 || $unitCost < 0) {
                return back()->withErrors([
                    'used_products_text' => 'الكمية يجب أن تكون أكبر من صفر والسعر لا يكون سالبا.',
                ])->withInput();
            }

            $lineTotal = round($quantity * $unitCost, 2);
            $productsTotal += $lineTotal;

            $usedProducts[] = [
                'product_name' => $parts[0],
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'line_total' => $lineTotal,
            ];
        }

        $baseTotal = round((float) $order->service_fee + $productsTotal, 2);
        $commission = $this->commissionEngineService->calculate($baseTotal, 'workshop', (int) $order->workshop_id, null);

        $order->update([
            'used_products' => $usedProducts,
            'products_total' => round($productsTotal, 2),
            'total_amount' => $baseTotal,
            'commission_rule_id' => $commission['rule_id'],
            'commission_percent' => $commission['commission_percent'],
            'commission_value' => $commission['commission_value'],
            'platform_service_fee' => $commission['service_fee'],
            'platform_fixed_fee' => $commission['fixed_fee'],
            'payable_total' => $commission['final_amount'],
        ]);

        return back()->with('status', 'تم تحديث المنتجات المستخدمة بنجاح.');
    }

    public function maintenanceHistory(): View
    {
        $workshopId = Auth::guard('workshop')->id();

        $history = WorkshopServiceOrder::query()
            ->with('service')
            ->where('workshop_id', $workshopId)
            ->where('status', WorkshopServiceOrder::STATUS_COMPLETED)
            ->where(function ($query) {
                $query->whereNotNull('vehicle_plate_number')
                    ->orWhereNotNull('vehicle_brand')
                    ->orWhereNotNull('vehicle_model');
            })
            ->latest('updated_at')
            ->get();

        return view('workshop.maintenance.history', compact('history'));
    }

    private function authorizeOwnership(WorkshopServiceOrder $order): void
    {
        abort_unless($order->workshop_id === Auth::guard('workshop')->id(), 403);
    }

    private function generateOrderNumber(): string
    {
        do {
            $candidate = 'WSO-' . strtoupper(str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT));
        } while (WorkshopServiceOrder::query()->where('order_number', $candidate)->exists());

        return $candidate;
    }
}
