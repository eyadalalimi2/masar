<?php

namespace App\Http\Middleware\Audit;

use App\Models\Audit\AuditTrail;
use App\Services\Operations\OperationalAlertService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogSensitiveAction
{
    public function __construct(private readonly OperationalAlertService $alertService) {}

    private const GUARDED_ACTORS = [
        'admin' => 'admin',
        'agent' => 'supplier_agent',
        'branch' => 'branch',
        'distributor' => 'distributor',
        'customer' => 'customer',
        'consumer' => 'consumer',
        'pos' => 'pos',
        'workshop' => 'workshop',
        'web' => 'user',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $response;
        }

        [$guard, $actorType, $actorId] = $this->resolveActor();
        if ($actorId === null) {
            return $response;
        }

        $routeMiddlewares = $request->route()?->gatherMiddleware() ?? [];
        if ($guard === 'admin' && in_array('admin.audit', $routeMiddlewares, true)) {
            return $response;
        }

        $routeName = $request->route()?->getName();
        $auditPayload = [
            'status' => $response->getStatusCode(),
            'payload' => $request->except([
                'password',
                'password_confirmation',
                'current_password',
                'new_password',
                'token',
                '_token',
            ]),
            'query' => $request->query(),
        ];

        AuditTrail::query()->create([
            'actor_type' => $actorType,
            'actor_id' => (int) $actorId,
            'guard' => $guard,
            'action' => $routeName ?? ($guard . '.action'),
            'route_name' => $routeName,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status_code' => $response->getStatusCode(),
            'meta' => $auditPayload,
        ]);

        if ($response->getStatusCode() >= 500) {
            Log::channel('operations')->error('Operational failure on sensitive action.', [
                'guard' => $guard,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'route_name' => $routeName,
                'method' => $request->method(),
                'path' => $request->path(),
                'status_code' => $response->getStatusCode(),
            ]);

            $this->alertService->trigger(
                'sensitive_action_failed',
                'Sensitive action returned server error.',
                [
                    'guard' => $guard,
                    'actor_type' => $actorType,
                    'actor_id' => $actorId,
                    'route_name' => $routeName,
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'status_code' => $response->getStatusCode(),
                    'severity' => 'critical',
                ]
            );
        }

        return $response;
    }

    private function resolveActor(): array
    {
        foreach (self::GUARDED_ACTORS as $guard => $actorType) {
            if (Auth::guard($guard)->check()) {
                return [$guard, $actorType, Auth::guard($guard)->id()];
            }
        }

        return [null, null, null];
    }
}
