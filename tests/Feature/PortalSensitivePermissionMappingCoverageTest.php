<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class PortalSensitivePermissionMappingCoverageTest extends TestCase
{
    public function test_sensitive_portal_routes_have_required_permission_mapping(): void
    {
        $permissionMapByGuard = (array) config('operations.security.permission_map', []);
        $targetGuards = ['agent', 'branch', 'distributor', 'customer', 'consumer', 'pos', 'workshop'];

        $violations = [];

        foreach (Route::getRoutes() as $route) {
            $methods = array_values(array_diff($route->methods(), ['HEAD']));
            if (count(array_intersect($methods, ['POST', 'PUT', 'PATCH', 'DELETE'])) === 0) {
                continue;
            }

            $guard = $this->extractGuard($route->gatherMiddleware(), $targetGuards);
            if ($guard === null) {
                continue;
            }

            $routeName = (string) ($route->getName() ?? '');
            $map = (array) ($permissionMapByGuard[$guard] ?? []);

            if ($routeName === '' || ! $this->isMapped($routeName, $map)) {
                $violations[] = ($routeName !== '' ? $routeName : '[unnamed]') . ' (guard:' . $guard . ')';
            }
        }

        $this->assertSame([], $violations, "Sensitive portal routes missing permission mapping:\n" . implode("\n", $violations));
    }

    public function test_mapped_permissions_exist_in_default_grants(): void
    {
        $permissionMapByGuard = (array) config('operations.security.permission_map', []);
        $grantsByGuard = (array) config('operations.security.default_grants', []);
        $violations = [];

        foreach ($permissionMapByGuard as $guard => $map) {
            $grants = (array) ($grantsByGuard[$guard] ?? []);
            if (in_array('*', $grants, true)) {
                continue;
            }

            foreach ((array) $map as $pattern => $permission) {
                if (! is_string($permission) || $permission === '') {
                    $violations[] = $guard . ':' . $pattern . ' => [invalid-permission]';
                    continue;
                }

                if (! in_array($permission, $grants, true)) {
                    $violations[] = $guard . ':' . $pattern . ' => ' . $permission;
                }
            }
        }

        $this->assertSame([], $violations, "Mapped permissions missing in default grants:\n" . implode("\n", $violations));
    }

    private function extractGuard(array $middleware, array $targetGuards): ?string
    {
        foreach ($middleware as $entry) {
            if (! is_string($entry) || ! str_starts_with($entry, 'auth:')) {
                continue;
            }

            $guards = array_map('trim', explode(',', substr($entry, 5)));
            foreach ($guards as $guard) {
                if (in_array($guard, $targetGuards, true)) {
                    return $guard;
                }
            }
        }

        return null;
    }

    private function isMapped(string $routeName, array $map): bool
    {
        foreach ($map as $pattern => $permission) {
            if (is_string($pattern) && is_string($permission) && $permission !== '' && Str::is($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }
}
