<?php

namespace App\Http\Middleware\Security;

use App\Services\Security\PortalPermissionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalPermission
{
    public function __construct(private readonly PortalPermissionService $portalPermissionService) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if ($permission === '') {
            abort(403, 'الصلاحية المطلوبة غير معرّفة.');
        }

        [$guard, $actor] = $this->resolveGuardAndActor();

        if ($guard === null || ! $this->portalPermissionService->hasPermission($guard, $actor, $permission)) {
            abort(403, 'ليس لديك صلاحية للوصول إلى هذه الشاشة.');
        }

        return $next($request);
    }

    private function resolveGuardAndActor(): array
    {
        foreach (['agent', 'branch', 'distributor', 'customer', 'consumer', 'pos', 'workshop'] as $guard) {
            if (Auth::guard($guard)->check()) {
                return [$guard, Auth::guard($guard)->user()];
            }
        }

        return [null, null];
    }
}
