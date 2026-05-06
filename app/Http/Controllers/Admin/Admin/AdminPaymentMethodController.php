<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\SystemSetting;
use App\Models\Finance\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminPaymentMethodController extends Controller
{
    public function index(): View
    {
        $settings = [
            'payment' => array_merge($this->defaultPayment(), SystemSetting::getValue('payment', [])),
        ];

        $paymentMethods = Schema::hasTable('payment_methods')
            ? PaymentMethod::query()->orderBy('sort_order')->orderBy('id')->get()
            : collect();

        return view('admin.payment-methods.index', compact('settings', 'paymentMethods'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $iconPath = $request->hasFile('icon')
            ? $request->file('icon')->store('payment-methods', 'public')
            : null;

        PaymentMethod::query()->create([
            'name' => $data['name'],
            'icon' => $iconPath,
            'type' => 'online',
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return back()->with('success', 'تمت إضافة طريقة الدفع بنجاح.');
    }

    public function update(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        if ($paymentMethod->type === 'offline') {
            $data = $request->validate([
                'is_active' => ['nullable', 'boolean'],
            ]);

            $paymentMethod->update([
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]);

            return back()->with('success', 'تم تحديث حالة الدفع عند الاستلام.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $iconPath = $paymentMethod->icon;
        if ($request->hasFile('icon')) {
            if (is_string($paymentMethod->icon) && $paymentMethod->icon !== '') {
                Storage::disk('public')->delete($paymentMethod->icon);
            }

            $iconPath = $request->file('icon')->store('payment-methods', 'public');
        }

        $paymentMethod->update([
            'name' => $data['name'],
            'icon' => $iconPath,
            'type' => 'online',
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return back()->with('success', 'تم تحديث طريقة الدفع بنجاح.');
    }

    public function toggle(PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->update([
            'is_active' => ! $paymentMethod->is_active,
        ]);

        return back()->with('success', 'تم تحديث حالة طريقة الدفع.');
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        if ($paymentMethod->type === 'offline') {
            return back()->withErrors(['payment_method' => 'لا يمكن حذف طريقة الدفع عند الاستلام.']);
        }

        if (is_string($paymentMethod->icon) && $paymentMethod->icon !== '') {
            Storage::disk('public')->delete($paymentMethod->icon);
        }

        $paymentMethod->delete();

        return back()->with('success', 'تم حذف طريقة الدفع بنجاح.');
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
