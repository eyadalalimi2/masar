<?php

namespace App\Http\Middleware\Security;

use App\Services\Operations\OperationalAlertService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleSensitiveWrites
{
    public function __construct(private readonly OperationalAlertService $alertService) {}

    private const GUARDED_ACTORS = [
        'admin',
        'agent',
        'branch',
        'distributor',
        'customer',
        'consumer',
        'pos',
        'workshop',
        'web',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $identity = $this->resolveIdentity();
        if ($identity === null) {
            return $next($request);
        }

        $maxAttempts = (int) config('operations.thresholds.sensitive_write_rate_per_minute', 120);
        $maxAttempts = $maxAttempts > 0 ? $maxAttempts : 120;

        $key = 'sensitive-write:' . $identity['guard'] . ':' . $identity['id'] . ':' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            Log::channel('security')->warning('Sensitive writes rate limit exceeded.', [
                'guard' => $identity['guard'],
                'actor_id' => $identity['id'],
                'method' => $request->method(),
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            $this->alertService->trigger(
                'sensitive_write_rate_exceeded',
                'Sensitive write rate threshold exceeded.',
                [
                    'guard' => $identity['guard'],
                    'actor_id' => $identity['id'],
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'ip' => $request->ip(),
                    'remaining' => 0,
                    'max_attempts' => $maxAttempts,
                    'severity' => 'critical',
                ]
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'تم تجاوز عدد العمليات المسموح بها مؤقتًا. حاول لاحقًا.',
                ], 429);
            }

            return redirect()->back()->withErrors([
                'rate_limit' => 'تم تجاوز عدد العمليات المسموح بها مؤقتًا. حاول لاحقًا.',
            ]);
        }

        RateLimiter::hit($key, 60);

        $remaining = RateLimiter::remaining($key, $maxAttempts);
        $usedRatio = 1 - ($remaining / max($maxAttempts, 1));
        $warningRatio = (float) config('operations.thresholds.sensitive_write_warning_ratio', 0.85);

        if ($usedRatio >= $warningRatio) {
            $this->alertService->trigger(
                'sensitive_write_rate_warning',
                'Sensitive write rate is approaching threshold.',
                [
                    'guard' => $identity['guard'],
                    'actor_id' => $identity['id'],
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'ip' => $request->ip(),
                    'remaining' => $remaining,
                    'max_attempts' => $maxAttempts,
                    'used_ratio' => round($usedRatio, 3),
                    'severity' => 'warning',
                ],
                120
            );
        }

        return $next($request);
    }

    private function resolveIdentity(): ?array
    {
        foreach (self::GUARDED_ACTORS as $guard) {
            if (Auth::guard($guard)->check()) {
                return [
                    'guard' => $guard,
                    'id' => (int) Auth::guard($guard)->id(),
                ];
            }
        }

        return null;
    }
}
