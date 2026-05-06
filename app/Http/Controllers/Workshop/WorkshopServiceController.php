<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Models\Workshop\WorkshopService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WorkshopServiceController extends Controller
{
    public function index(): View
    {
        $workshopId = Auth::guard('workshop')->id();

        $services = WorkshopService::query()
            ->where('workshop_id', $workshopId)
            ->latest()
            ->get();

        $stats = [
            'active' => (int) $services->where('is_active', true)->count(),
            'avg_duration' => (int) round((float) ($services->avg('duration_minutes') ?? 0)),
            'inactive' => (int) $services->where('is_active', false)->count(),
        ];

        return view('workshop.services.index', compact('services', 'stats'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:720'],
            'requires_products' => ['nullable', 'boolean'],
            'is_package' => ['nullable', 'boolean'],
            'package_items' => ['nullable', 'string', 'max:4000'],
        ]);

        $data['workshop_id'] = Auth::guard('workshop')->id();
        $data['is_active'] = true;
        $data['requires_products'] = (bool) ($data['requires_products'] ?? false);
        $data['is_package'] = (bool) ($data['is_package'] ?? false);
        $data['package_items'] = $data['is_package'] ? ($data['package_items'] ?? null) : null;

        WorkshopService::create($data);

        return back()->with('status', 'تمت إضافة الخدمة بنجاح.');
    }

    public function update(Request $request, WorkshopService $service): RedirectResponse
    {
        $this->authorizeOwnership($service);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:720'],
            'requires_products' => ['nullable', 'boolean'],
            'is_package' => ['nullable', 'boolean'],
            'package_items' => ['nullable', 'string', 'max:4000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['requires_products'] = (bool) ($data['requires_products'] ?? false);
        $data['is_package'] = (bool) ($data['is_package'] ?? false);
        $data['package_items'] = $data['is_package'] ? ($data['package_items'] ?? null) : null;
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $service->update($data);

        return back()->with('status', 'تم تحديث الخدمة بنجاح.');
    }

    public function toggle(WorkshopService $service): RedirectResponse
    {
        $this->authorizeOwnership($service);

        $service->update([
            'is_active' => ! $service->is_active,
        ]);

        return back()->with('status', 'تم تغيير حالة الخدمة.');
    }

    private function authorizeOwnership(WorkshopService $service): void
    {
        abort_unless($service->workshop_id === Auth::guard('workshop')->id(), 403);
    }
}
