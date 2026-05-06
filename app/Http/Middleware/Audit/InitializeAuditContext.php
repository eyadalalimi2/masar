<?php

namespace App\Http\Middleware\Audit;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class InitializeAuditContext
{
    public function handle(Request $request, Closure $next): Response
    {
        \App\Services\Audit\AuditLogService::setRequestContext([
            'user_id' => $this->resolveUserId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device' => $this->resolveDevice($request->userAgent()),
        ]);

        try {
            return $next($request);
        } finally {
            \App\Services\Audit\AuditLogService::clearRequestContext();
        }
    }

    private function resolveUserId(): ?int
    {
        foreach (['admin', 'agent', 'branch', 'distributor', 'customer', 'consumer', 'pos', 'workshop', 'web'] as $guard) {
            if (Auth::guard($guard)->check()) {
                return (int) Auth::guard($guard)->id();
            }
        }

        return null;
    }

    private function resolveDevice(?string $userAgent): ?string
    {
        if (! is_string($userAgent) || trim($userAgent) === '') {
            return null;
        }

        $ua = strtolower($userAgent);

        if (str_contains($ua, 'android') || str_contains($ua, 'iphone') || str_contains($ua, 'mobile')) {
            return 'mobile';
        }

        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            return 'tablet';
        }

        return 'desktop';
    }
}
