<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin\EnsureAdminPermission;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Tests\TestCase;

class AdminSensitiveRoutePermissionCoverageTest extends TestCase
{
    public function test_every_sensitive_admin_route_has_permission_mapping(): void
    {
        $middleware = app(EnsureAdminPermission::class);
        $mapper = new ReflectionMethod($middleware, 'mapRouteToPermission');
        $mapper->setAccessible(true);

        $missing = [];

        foreach (Route::getRoutes() as $route) {
            $name = (string) ($route->getName() ?? '');
            if ($name === '' || ! str_starts_with($name, 'admin.')) {
                continue;
            }

            $methods = array_values(array_diff($route->methods(), ['HEAD']));
            $isSensitiveWrite = count(array_intersect($methods, ['POST', 'PUT', 'PATCH', 'DELETE'])) > 0;
            if (! $isSensitiveWrite) {
                continue;
            }

            $middlewareList = $route->gatherMiddleware();
            if (! in_array('ensure.admin.permission', $middlewareList, true)) {
                continue;
            }

            $permission = $mapper->invoke($middleware, $name);
            if (! is_string($permission) || $permission === '') {
                $missing[] = $name;
            }
        }

        $this->assertSame([], $missing, "Sensitive admin routes missing permission mapping:\n" . implode("\n", $missing));
    }
}
