<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HandlesParallelDashboardSessions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    use HandlesParallelDashboardSessions;

    public function showLoginForm(): View
    {
        return view('admin.auth.login');
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

        if (Auth::guard('admin')->attempt([
            'phone' => $credentials['phone'],
            'password' => $credentials['password'],
            'status' => 'active',
        ])) {
            $this->regenerateForParallelDashboards($request);
            $request->session()->put('admin_last_activity_at', now()->timestamp);

            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors([
            'phone' => 'بيانات الدخول غير صحيحة أو لا تملك صلاحية الأدمن.',
        ])->onlyInput('phone');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->forget('admin_last_activity_at');
        $this->invalidateForParallelDashboards($request);

        return redirect()->route('login');
    }
}
