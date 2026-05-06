<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Models\Workshop\WorkshopAppointment;
use App\Models\Workshop\WorkshopService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WorkshopAppointmentController extends Controller
{
    public function index(): View
    {
        $workshopId = Auth::guard('workshop')->id();

        $services = WorkshopService::query()
            ->where('workshop_id', $workshopId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $appointments = WorkshopAppointment::query()
            ->with('service')
            ->where('workshop_id', $workshopId)
            ->orderBy('appointment_at')
            ->limit(40)
            ->get();

        $suggestedSlot = $this->resolveSuggestedSlot($workshopId, 30);

        return view('workshop.appointments.index', compact('services', 'appointments', 'suggestedSlot'));
    }

    public function store(Request $request): RedirectResponse
    {
        $workshopId = Auth::guard('workshop')->id();

        $data = $request->validate([
            'service_id' => ['nullable', 'integer', 'exists:workshop_services,id'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'vehicle_details' => ['nullable', 'string', 'max:180'],
            'appointment_at' => ['nullable', 'date'],
            'estimated_minutes' => ['required', 'integer', 'min:10', 'max:480'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'auto_schedule' => ['nullable', 'boolean'],
        ]);

        $service = null;
        if (! empty($data['service_id'])) {
            $service = WorkshopService::query()
                ->where('workshop_id', $workshopId)
                ->findOrFail($data['service_id']);
        }

        $duration = (int) ($service?->duration_minutes ?? $data['estimated_minutes']);
        $startAt = ! empty($data['appointment_at'])
            ? Carbon::parse($data['appointment_at'])
            : null;

        if ((bool) ($data['auto_schedule'] ?? false) || $startAt === null) {
            $startAt = $this->resolveSuggestedSlot($workshopId, $duration);
        }

        if (! $startAt) {
            return back()->withErrors([
                'appointment_at' => 'تعذر اقتراح موعد ذكي حاليا، يرجى اختيار وقت يدوي.',
            ])->withInput();
        }

        if ($this->hasTimeConflict($workshopId, $startAt, $duration)) {
            return back()->withErrors([
                'appointment_at' => 'هذا الموعد متعارض مع حجز آخر. اختر وقتًا مختلفًا.',
            ])->withInput();
        }

        WorkshopAppointment::create([
            'workshop_id' => $workshopId,
            'service_id' => $service?->id,
            'snapshot_customer_name' => $data['customer_name'],
            'snapshot_customer_phone' => $data['customer_phone'],
            'vehicle_details' => $data['vehicle_details'] ?? null,
            'appointment_at' => $startAt,
            'estimated_minutes' => $duration,
            'status' => WorkshopAppointment::STATUS_SCHEDULED,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('status', 'تم إضافة الموعد بنجاح.');
    }

    public function updateStatus(Request $request, WorkshopAppointment $appointment): RedirectResponse
    {
        $this->authorizeOwnership($appointment);

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', WorkshopAppointment::STATUSES)],
        ]);

        $appointment->update([
            'status' => $data['status'],
        ]);

        return back()->with('status', 'تم تحديث حالة الموعد.');
    }

    private function hasTimeConflict(int $workshopId, Carbon $startAt, int $duration): bool
    {
        $endAt = (clone $startAt)->addMinutes($duration);

        $candidates = WorkshopAppointment::query()
            ->where('workshop_id', $workshopId)
            ->whereIn('status', [
                WorkshopAppointment::STATUS_SCHEDULED,
                WorkshopAppointment::STATUS_IN_PROGRESS,
            ])
            ->whereBetween('appointment_at', [
                (clone $startAt)->subHours(12),
                (clone $endAt)->addHours(12),
            ])
            ->get();

        foreach ($candidates as $candidate) {
            $candidateStart = Carbon::parse($candidate->appointment_at);
            $candidateEnd = (clone $candidateStart)->addMinutes((int) $candidate->estimated_minutes);

            $overlap = $startAt < $candidateEnd && $endAt > $candidateStart;
            if ($overlap) {
                return true;
            }
        }

        return false;
    }

    private function authorizeOwnership(WorkshopAppointment $appointment): void
    {
        abort_unless($appointment->workshop_id === Auth::guard('workshop')->id(), 403);
    }

    private function resolveSuggestedSlot(int $workshopId, int $estimatedMinutes): ?Carbon
    {
        $candidate = Carbon::now()->addMinutes(30)->seconds(0);
        $estimatedMinutes = max($estimatedMinutes, 10);

        for ($i = 0; $i < 120; $i++) {
            if (! $this->hasTimeConflict($workshopId, $candidate, $estimatedMinutes)) {
                return $candidate;
            }

            $candidate->addMinutes(30);
        }

        return null;
    }
}
