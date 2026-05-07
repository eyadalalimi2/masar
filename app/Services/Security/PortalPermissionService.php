<?php

namespace App\Services\Security;

use App\Models\Security\PortalAccountPermission;
use Illuminate\Support\Facades\Cache;

class PortalPermissionService
{
    public function hasPermission(string $guard, mixed $actor, string $permission): bool
    {
        $guard = strtolower(trim($guard));
        $permission = strtolower(trim($permission));

        if (! is_object($actor) || $guard === '' || $permission === '') {
            return false;
        }

        if (method_exists($actor, 'hasPermission') && $actor->hasPermission($permission)) {
            return true;
        }

        $actorId = (int) ($actor->id ?? 0);
        if ($actorId <= 0) {
            return false;
        }

        $cache = app(PermissionCacheService::class);
        $cacheKey = $cache->key('rbac:portal:permission', $guard . ':' . $actorId . ':' . $permission);

        return (bool) Cache::remember($cacheKey, $cache->ttlSeconds(), function () use ($guard, $actorId, $permission): bool {
            $explicitGrant = PortalAccountPermission::query()
                ->where('guard_name', $guard)
                ->where('account_id', $actorId)
                ->where('permission', $permission)
                ->first();

            if ($explicitGrant) {
                return (bool) $explicitGrant->is_granted;
            }

            if (! (bool) config('operations.security.use_default_grants_fallback', true)) {
                return false;
            }

            $defaultGrants = (array) config('operations.security.default_grants.' . $guard, []);
            $normalized = collect($defaultGrants)
                ->filter(fn($entry) => is_string($entry) && trim($entry) !== '')
                ->map(fn($entry) => strtolower(trim((string) $entry)))
                ->unique()
                ->values()
                ->all();

            return in_array('*', $normalized, true) || in_array($permission, $normalized, true);
        });
    }
}
