<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSystemSettingsController extends Controller
{
    public function index(): View
    {
        $settings = [
            'general' => array_merge($this->defaultGeneral(), SystemSetting::getValue('general', [])),
            'security' => array_merge($this->defaultSecurity(), SystemSetting::getValue('security', [])),
            'delivery' => array_merge($this->defaultDelivery(), SystemSetting::getValue('delivery', [])),
            'payment' => array_merge($this->defaultPayment(), SystemSetting::getValue('payment', [])),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request, string $section): RedirectResponse
    {
        $allowed = ['general', 'security', 'delivery', 'payment'];
        if (! in_array($section, $allowed, true)) {
            abort(404);
        }

        $data = match ($section) {
            'general' => $this->validateGeneral($request),
            'security' => $this->validateSecurity($request),
            'delivery' => $this->validateDelivery($request),
            'payment' => $this->validatePayment($request),
            default => [],
        };

        SystemSetting::putValue($section, $data);

        return back()->with('success', 'تم تحديث إعدادات القسم بنجاح.');
    }

    private function validateGeneral(Request $request): array
    {
        return $request->validate([
            'platform_name' => ['required', 'string', 'max:150'],
            'default_currency' => ['required', 'string', 'max:10'],
            'default_language' => ['required', 'string', 'max:10'],
        ]);
    }

    private function validateSecurity(Request $request): array
    {
        $validated = $request->validate([
            'password_min_length' => ['required', 'integer', 'min:6', 'max:32'],
            'password_require_mixed_case' => ['nullable', 'boolean'],
            'password_require_numbers' => ['nullable', 'boolean'],
            'password_require_symbols' => ['nullable', 'boolean'],
            'session_timeout_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'enable_2fa' => ['nullable', 'boolean'],
        ]);

        return [
            'password_min_length' => (int) $validated['password_min_length'],
            'password_require_mixed_case' => (bool) ($validated['password_require_mixed_case'] ?? false),
            'password_require_numbers' => (bool) ($validated['password_require_numbers'] ?? false),
            'password_require_symbols' => (bool) ($validated['password_require_symbols'] ?? false),
            'session_timeout_minutes' => (int) $validated['session_timeout_minutes'],
            'enable_2fa' => (bool) ($validated['enable_2fa'] ?? false),
        ];
    }

    private function validateDelivery(Request $request): array
    {
        $validated = $request->validate([
            'service_radius_km' => ['required', 'numeric', 'min:1', 'max:500'],
            'allow_manual_reassign' => ['nullable', 'boolean'],
            'auto_assign_distributor' => ['nullable', 'boolean'],
        ]);

        return [
            'service_radius_km' => (float) $validated['service_radius_km'],
            'allow_manual_reassign' => (bool) ($validated['allow_manual_reassign'] ?? false),
            'auto_assign_distributor' => (bool) ($validated['auto_assign_distributor'] ?? false),
        ];
    }

    private function validatePayment(Request $request): array
    {
        $validated = $request->validate([
            'payment_gateway_enabled' => ['nullable', 'boolean'],
            'payment_mode' => ['required', 'in:disabled,sandbox,live'],
            'cash_on_delivery_enabled' => ['nullable', 'boolean'],
        ]);

        return [
            'payment_gateway_enabled' => (bool) ($validated['payment_gateway_enabled'] ?? false),
            'payment_mode' => $validated['payment_mode'],
            'cash_on_delivery_enabled' => (bool) ($validated['cash_on_delivery_enabled'] ?? false),
        ];
    }

    private function defaultGeneral(): array
    {
        return [
            'platform_name' => config('app.name', 'Masar'),
            'default_currency' => 'IQD',
            'default_language' => 'ar',
        ];
    }

    private function defaultSecurity(): array
    {
        return [
            'password_min_length' => 8,
            'password_require_mixed_case' => false,
            'password_require_numbers' => true,
            'password_require_symbols' => false,
            'session_timeout_minutes' => (int) env('ADMIN_IDLE_TIMEOUT_MINUTES', 30),
            'enable_2fa' => false,
        ];
    }

    private function defaultDelivery(): array
    {
        return [
            'service_radius_km' => 25,
            'allow_manual_reassign' => true,
            'auto_assign_distributor' => false,
        ];
    }

    private function defaultPayment(): array
    {
        return [
            'payment_gateway_enabled' => false,
            'payment_mode' => 'disabled',
            'cash_on_delivery_enabled' => true,
        ];
    }
}
