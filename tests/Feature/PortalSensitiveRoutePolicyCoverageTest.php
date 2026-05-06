<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class PortalSensitiveRoutePolicyCoverageTest extends TestCase
{
    public function test_sensitive_portal_routes_follow_guard_name_policy(): void
    {
        $patternsByGuard = (array) config('operations.security.sensitive_route_name_patterns', []);
        $targetGuards = ['agent', 'branch', 'distributor', 'customer', 'consumer', 'pos', 'workshop'];

        $violations = [];

        foreach (Route::getRoutes() as $route) {
            $methods = array_values(array_diff($route->methods(), ['HEAD']));
            if (count(array_intersect($methods, ['POST', 'PUT', 'PATCH', 'DELETE'])) === 0) {
                continue;
            }

            $middleware = $route->gatherMiddleware();
            $guard = $this->extractGuard($middleware, $targetGuards);
            if ($guard === null) {
                continue;
            }

            $routeName = (string) ($route->getName() ?? '');
            $patterns = (array) ($patternsByGuard[$guard] ?? []);

            if ($routeName === '' || ! $this->matchesAny($routeName, $patterns)) {
                $violations[] = ($routeName !== '' ? $routeName : '[unnamed]') . ' (guard:' . $guard . ')';
            }
        }

        $this->assertSame([], $violations, "Sensitive portal routes violating name policy:\n" . implode("\n", $violations));
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

    private function matchesAny(string $value, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (is_string($pattern) && $pattern !== '' && Str::is($pattern, $value)) {
                return true;
            }
        }

        return false;
    }
}
