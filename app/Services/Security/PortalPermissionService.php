<?php

namespace App\Services\Security;

use App\Models\Security\PortalAccountPermission;

class PortalPermissionService
{
    public function hasPermission(string $guard, mixed $actor, string $permission): bool
    {
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

        return in_array('*', $defaultGrants, true) || in_array($permission, $defaultGrants, true);
    }
}
