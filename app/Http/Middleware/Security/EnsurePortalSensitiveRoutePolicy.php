<?php

namespace App\Http\Middleware\Security;

use App\Services\Security\PortalPermissionService;
use App\Services\Operations\OperationalAlertService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalSensitiveRoutePolicy
{
    public function __construct(
        private readonly OperationalAlertService $alertService,
        private readonly PortalPermissionService $portalPermissionService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningUnitTests() && ! (bool) config('operations.security.enforce_in_tests', true)) {
            return $next($request);
        }

        if (! $this->isSensitiveWrite($request)) {
            return $next($request);
        }

        $guard = $this->resolvePortalGuard();
        if ($guard === null) {
            return $next($request);
        }

        $actor = Auth::guard($guard)->user();

        $routeName = (string) ($request->route()?->getName() ?? '');
        $patterns = (array) config('operations.security.sensitive_route_name_patterns.' . $guard, []);

        if (! $this->isAllowedRouteName($routeName, $patterns)) {
            $context = [
                'guard' => $guard,
                'route_name' => $routeName,
                'method' => $request->method(),
                'path' => $request->path(),
                'ip' => $request->ip(),
                'severity' => 'critical',
            ];

            Log::channel('security_alerts')->critical('Denied sensitive write by portal route policy.', $context);

            $this->alertService->trigger(
                'sensitive_route_policy_denied',
                'Sensitive write denied due to route policy mismatch.',
                $context,
                60
            );

            abort(403, 'صلاحية هذا المسار غير معتمدة للعمليات الحساسة.');
        }

        $requiredPermission = $this->resolveRequiredPermission($guard, $routeName);

        if ($requiredPermission === null) {
            $context = [
                'guard' => $guard,
                'route_name' => $routeName,
                'method' => $request->method(),
                'path' => $request->path(),
                'ip' => $request->ip(),
                'severity' => 'critical',
            ];

            Log::channel('security_alerts')->critical('Denied sensitive write due to missing portal permission map.', $context);

            $this->alertService->trigger(
                'sensitive_permission_missing',
                'Sensitive write denied: no required permission was mapped.',
                $context,
                60
            );

            abort(403, 'صلاحية العملية غير معرفة بشكل صحيح.');
        }

        if (! $this->actorHasPermission($guard, $actor, $requiredPermission)) {
            $context = [
                'guard' => $guard,
                'route_name' => $routeName,
                'required_permission' => $requiredPermission,
                'actor_id' => is_object($actor) ? ($actor->id ?? null) : null,
                'method' => $request->method(),
                'path' => $request->path(),
                'ip' => $request->ip(),
                'severity' => 'critical',
            ];

            Log::channel('security_alerts')->critical('Denied sensitive write due to missing permission grant.', $context);

            $this->alertService->trigger(
                'sensitive_permission_denied',
                'Sensitive write denied due to missing permission grant.',
                $context,
                60
            );

            abort(403, 'ليس لديك صلاحية لتنفيذ هذه العملية.');
        }

        return $next($request);
    }

    private function isSensitiveWrite(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    private function resolvePortalGuard(): ?string
    {
        foreach (['agent', 'branch', 'distributor', 'customer', 'consumer', 'pos', 'workshop'] as $guard) {
            if (Auth::guard($guard)->check()) {
                return $guard;
            }
        }

        return null;
    }

    private function isAllowedRouteName(string $routeName, array $patterns): bool
    {
        if ($routeName === '') {
            return false;
        }

        if ($patterns === []) {
            $patterns = $this->fallbackRoutePatterns();
        }

        foreach ($patterns as $pattern) {
            if (is_string($pattern) && $pattern !== '' && Str::is($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }

    private function resolveRequiredPermission(string $guard, string $routeName): ?string
    {
        $map = (array) config('operations.security.permission_map.' . $guard, []);
        if ($map === []) {
            $map = (array) ($this->fallbackPermissionMap()[$guard] ?? []);
        }

        if ($routeName === '' || $map === []) {
            return null;
        }

        foreach ($map as $pattern => $permission) {
            if (is_string($pattern) && is_string($permission) && $permission !== '' && Str::is($pattern, $routeName)) {
                return $permission;
            }
        }

        return null;
    }

    private function actorHasPermission(string $guard, mixed $actor, string $permission): bool
    {
        return $this->portalPermissionService->hasPermission($guard, $actor, $permission);
    }

    private function fallbackRoutePatterns(): array
    {
        return ['agent.*', 'branch.*', 'distributor.*', 'customer.*', 'consumer.*', 'pos.*', 'workshop.*', 'api.v1.*'];
    }

    private function fallbackPermissionMap(): array
    {
        return [
            'agent' => [
                'agent.logout' => 'session.logout',
                'agent.profile.*' => 'profile.manage',
                'agent.alerts.*' => 'alerts.manage',
                'agent.branches.*' => 'branches.manage',
                'agent.distributors.*' => 'distributors.manage',
                'agent.users.*' => 'users.manage',
                'agent.commercial-stores.*' => 'customers.commercial.manage',
                'agent.workshops.*' => 'customers.workshop.manage',
                'agent.products.*' => 'catalog.manage',
                'agent.inventory.*' => 'inventory.manage',
                'agent.orders.*' => 'orders.manage',
                'agent.replenishment.*' => 'replenishment.manage',
                'agent.reports.alerts.*' => 'reports.alerts.manage',
                'agent.payments.*' => 'finance.manage',
            ],
            'branch' => [
                'branch.logout' => 'session.logout',
                'branch.profile.*' => 'profile.manage',
                'branch.users.*' => 'users.manage',
                'branch.distributors.*' => 'distributors.manage',
                'branch.inventory.*' => 'inventory.manage',
                'branch.orders.*' => 'orders.manage',
                'branch.replenishment.*' => 'replenishment.manage',
                'branch.alerts.*' => 'alerts.manage',
                'branch.payments.*' => 'finance.manage',
            ],
            'distributor' => [
                'distributor.logout' => 'session.logout',
                'distributor.profile.*' => 'profile.manage',
                'distributor.orders.*' => 'orders.manage',
                'distributor.alerts.*' => 'alerts.manage',
                'distributor.payments.*' => 'finance.manage',
            ],
            'customer' => [
                'customer.logout' => 'session.logout',
                'customer.profile.*' => 'profile.manage',
                'customer.wholesale.products.*' => 'profile.manage',
                'customer.wholesale.orders.*' => 'profile.manage',
                'customer.wholesale.customers.*' => 'profile.manage',
                'customer.payment-methods.*' => 'profile.manage',
            ],
            'consumer' => [
                'consumer.logout' => 'session.logout',
                'consumer.profile.*' => 'profile.manage',
                'consumer.addresses.*' => 'addresses.manage',
                'consumer.orders.*' => 'orders.manage',
                'consumer.ratings.*' => 'ratings.manage',
                'consumer.history.*' => 'history.actions.manage',
                'consumer.alerts.*' => 'alerts.manage',
            ],
            'pos' => [
                'pos.logout' => 'session.logout',
                'pos.profile.*' => 'profile.manage',
                'pos.catalog.*' => 'catalog.manage',
                'pos.customers.*' => 'customers.manage',
                'pos.marketplace.*' => 'marketplace.manage',
                'pos.sales.*' => 'sales.manage',
                'pos.alerts.*' => 'alerts.manage',
                'api.v1.pos.*' => 'api.access',
            ],
            'workshop' => [
                'workshop.logout' => 'session.logout',
                'workshop.profile.*' => 'profile.manage',
                'workshop.services.*' => 'services.manage',
                'workshop.marketplace.*' => 'marketplace.manage',
                'workshop.orders.*' => 'orders.manage',
                'workshop.appointments.*' => 'appointments.manage',
                'workshop.execution.*' => 'execution.manage',
                'api.v1.workshop.*' => 'api.access',
            ],
        ];
    }
}
