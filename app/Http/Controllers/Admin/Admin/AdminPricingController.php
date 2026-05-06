<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\CommissionRule;
use App\Models\Admin\SubscriptionPlan;
use App\Services\Pricing\CommissionEngineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPricingController extends Controller
{
    public function __construct(private readonly CommissionEngineService $commissionEngineService) {}

    public function index(): View
    {
        $rules = CommissionRule::query()->latest()->paginate(10, ['*'], 'rules_page');
        $plans = SubscriptionPlan::query()->latest()->paginate(10, ['*'], 'plans_page');

        return view('admin.pricing.index', compact('rules', 'plans'));
    }

    public function storeRule(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'entity_type' => ['required', 'in:' . implode(',', CommissionRule::ENTITY_TYPES)],
            'entity_id' => ['nullable', 'integer', 'min:1'],
            'region_key' => ['nullable', 'string', 'max:120'],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'service_fee' => ['nullable', 'numeric', 'min:0'],
            'fixed_fee' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        CommissionRule::query()->create([
            'name' => $data['name'],
            'entity_type' => $data['entity_type'],
            'entity_id' => $data['entity_type'] === 'global' ? null : ($data['entity_id'] ?? null),
            'region_key' => $this->normalizeRegion($data['region_key'] ?? null),
            'commission_percent' => $data['commission_percent'],
            'service_fee' => $data['service_fee'] ?? 0,
            'fixed_fee' => $data['fixed_fee'] ?? 0,
            'priority' => $data['priority'] ?? 100,
            'effective_from' => $data['effective_from'] ?? null,
            'effective_to' => $data['effective_to'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'تمت إضافة قاعدة العمولة بنجاح.');
    }

    public function updateRule(Request $request, CommissionRule $rule): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'entity_type' => ['required', 'in:' . implode(',', CommissionRule::ENTITY_TYPES)],
            'entity_id' => ['nullable', 'integer', 'min:1'],
            'region_key' => ['nullable', 'string', 'max:120'],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'service_fee' => ['nullable', 'numeric', 'min:0'],
            'fixed_fee' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $rule->update([
            'name' => $data['name'],
            'entity_type' => $data['entity_type'],
            'entity_id' => $data['entity_type'] === 'global' ? null : ($data['entity_id'] ?? null),
            'region_key' => $this->normalizeRegion($data['region_key'] ?? null),
            'commission_percent' => $data['commission_percent'],
            'service_fee' => $data['service_fee'] ?? 0,
            'fixed_fee' => $data['fixed_fee'] ?? 0,
            'priority' => $data['priority'] ?? 100,
            'effective_from' => $data['effective_from'] ?? null,
            'effective_to' => $data['effective_to'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('success', 'تم تحديث قاعدة العمولة بنجاح.');
    }

    public function destroyRule(CommissionRule $rule): RedirectResponse
    {
        $rule->delete();

        return back()->with('success', 'تم حذف قاعدة العمولة بنجاح.');
    }

    public function storePlan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'alpha_dash', 'max:255', 'unique:admin_subscription_plans,slug'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'in:monthly,quarterly,yearly'],
            'orders_limit' => ['nullable', 'integer', 'min:1'],
            'users_limit' => ['nullable', 'integer', 'min:1'],
            'features' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        SubscriptionPlan::query()->create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'price' => $data['price'],
            'billing_cycle' => $data['billing_cycle'],
            'orders_limit' => $data['orders_limit'] ?? null,
            'users_limit' => $data['users_limit'] ?? null,
            'features' => $this->explodeFeatures($data['features'] ?? null),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'تمت إضافة الخطة بنجاح.');
    }

    public function updatePlan(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'in:monthly,quarterly,yearly'],
            'orders_limit' => ['nullable', 'integer', 'min:1'],
            'users_limit' => ['nullable', 'integer', 'min:1'],
            'features' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $plan->update([
            'name' => $data['name'],
            'price' => $data['price'],
            'billing_cycle' => $data['billing_cycle'],
            'orders_limit' => $data['orders_limit'] ?? null,
            'users_limit' => $data['users_limit'] ?? null,
            'features' => $this->explodeFeatures($data['features'] ?? null),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('success', 'تم تحديث الخطة بنجاح.');
    }

    public function destroyPlan(SubscriptionPlan $plan): RedirectResponse
    {
        $plan->delete();

        return back()->with('success', 'تم حذف الخطة بنجاح.');
    }

    public function previewCommission(Request $request): JsonResponse
    {
        $data = $request->validate([
            'base_amount' => ['required', 'numeric', 'min:0'],
            'entity_type' => ['nullable', 'in:' . implode(',', CommissionRule::ENTITY_TYPES)],
            'entity_id' => ['nullable', 'integer', 'min:1'],
            'region_key' => ['nullable', 'string', 'max:120'],
        ]);

        $entityType = (string) ($data['entity_type'] ?? 'global');
        $entityId = isset($data['entity_id']) ? (int) $data['entity_id'] : null;
        $regionKey = $this->normalizeRegion($data['region_key'] ?? null);

        $result = $this->commissionEngineService->calculate(
            (float) $data['base_amount'],
            $entityType,
            $entityId,
            $regionKey
        );

        return response()->json([
            'success' => true,
            'result' => $result,
        ]);
    }

    private function explodeFeatures(?string $text): array
    {
        if (! is_string($text) || trim($text) === '') {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $text) ?: [])
            ->map(fn($line) => trim($line))
            ->filter(fn($line) => $line !== '')
            ->values()
            ->all();
    }

    private function normalizeRegion(?string $region): ?string
    {
        if (! is_string($region)) {
            return null;
        }

        $region = trim($region);

        return $region === '' ? null : mb_strtolower($region);
    }
}
