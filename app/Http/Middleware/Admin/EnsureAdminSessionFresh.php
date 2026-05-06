<?php

namespace App\Http\Middleware\Admin;

use App\Models\Admin\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminSessionFresh
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('admin')->check()) {
            return $next($request);
        }

        $now = now()->timestamp;
        $key = 'admin_last_activity_at';
        $lastActivity = (int) $request->session()->get($key, 0);
        $securitySettings = SystemSetting::getValue('security', []);
        $timeoutMinutes = (int) ($securitySettings['session_timeout_minutes'] ?? env('ADMIN_IDLE_TIMEOUT_MINUTES', 30));
        $timeoutSeconds = max(1, $timeoutMinutes) * 60;

        if ($lastActivity > 0 && ($now - $lastActivity) > $timeoutSeconds) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->with('error', 'انتهت الجلسة بسبب عدم النشاط. يرجى تسجيل الدخول مجددًا.');
        }

        $request->session()->put($key, $now);

        return $next($request);
    }
}
