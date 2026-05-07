<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Cache;

class PermissionCacheService
{
    private const VERSION_KEY = 'rbac:permissions:version';

    public function getVersion(): int
    {
        $value = Cache::get(self::VERSION_KEY);

        if (! is_numeric($value)) {
            Cache::forever(self::VERSION_KEY, 1);

            return 1;
        }

        return max(1, (int) $value);
    }

    public function bumpVersion(): int
    {
        $current = $this->getVersion();
        $next = $current + 1;

        Cache::forever(self::VERSION_KEY, $next);

        return $next;
    }

    public function key(string $prefix, string $suffix): string
    {
        return $prefix . ':v' . $this->getVersion() . ':' . $suffix;
    }

    public function ttlSeconds(): int
    {
        $ttl = (int) config('operations.security.permission_cache_ttl_seconds', 300);

        return max(60, $ttl);
    }
}
