<?php

namespace App\Http\Controllers\Finance\Agent;

use App\Http\Controllers\Controller;
use App\Models\Finance\PaymentMethod;
use App\Models\Finance\PortalPaymentMethod;
use App\Models\Supplier\Agent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AgentPaymentMethodController extends Controller
{
    public function index(): View
    {
        $portalId = $this->resolvePortalId();

        $paymentMethods = PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $configuredMethods = PortalPaymentMethod::query()
            ->where('portal_type', 'agent')
            ->where('portal_id', $portalId)
            ->get()
            ->keyBy('payment_method_id');

        return view('agent.finance.payment-methods', compact('paymentMethods', 'configuredMethods'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'methods' => ['nullable', 'array'],
            'methods.*.account_number' => ['nullable', 'string', 'max:120'],
            'methods.*.account_name' => ['nullable', 'string', 'max:120'],
            'methods.*.note' => ['nullable', 'string', 'max:1000'],
            'methods.*.is_enabled' => ['nullable', 'boolean'],
        ]);

        $portalId = $this->resolvePortalId();
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
                    'portal_type' => 'agent',
                    'portal_id' => $portalId,
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

    private function resolvePortalId(): int
    {
        $agent = Auth::guard('agent')->user();

        abort_unless($agent instanceof Agent, 403);

        return (int) ($agent->supplier_id ?? 0);
    }

    private function normalize(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
