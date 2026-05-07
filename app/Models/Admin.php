<?php

namespace App\Models;

use App\Models\Admin\Role;
use App\Services\Security\PermissionCacheService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'admins';

    protected $fillable = [
        'name',
        'phone',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'admin_role_admin', 'admin_id', 'role_id')->withTimestamps();
    }

    public function hasRole(string $roleSlug): bool
    {
        $roleSlug = trim(strtolower($roleSlug));
        if ($roleSlug === '') {
            return false;
        }

        return in_array($roleSlug, $this->resolvedRoleSlugs(), true);
    }

    public function hasPermission(string $permissionSlug): bool
    {
        $permissionSlug = trim(strtolower($permissionSlug));
        if ($permissionSlug === '') {
            return false;
        }

        $roleSlugs = $this->resolvedRoleSlugs();
        if ($roleSlugs === []) {
            // Security hardening: deny access until explicit roles are assigned.
            return false;
        }

        if (in_array('super-admin', $roleSlugs, true)) {
            return true;
        }

        return in_array($permissionSlug, $this->resolvedPermissionSlugs(), true);
    }

    /**
     * @return array<int, string>
     */
    private function resolvedRoleSlugs(): array
    {
        $cache = app(PermissionCacheService::class);
        $cacheKey = $cache->key('rbac:admin:roles', (string) $this->id);

        return Cache::remember($cacheKey, $cache->ttlSeconds(), function (): array {
            return $this->roles()
                ->pluck('slug')
                ->filter(fn($slug) => is_string($slug) && trim($slug) !== '')
                ->map(fn($slug) => strtolower((string) $slug))
                ->unique()
                ->values()
                ->all();
        });
    }

    /**
     * @return array<int, string>
     */
    private function resolvedPermissionSlugs(): array
    {
        $cache = app(PermissionCacheService::class);
        $cacheKey = $cache->key('rbac:admin:permissions', (string) $this->id);

        return Cache::remember($cacheKey, $cache->ttlSeconds(), function (): array {
            $roles = $this->roles()
                ->with(['permissions:id,slug', 'parent.permissions:id,slug', 'parent.parent.permissions:id,slug'])
                ->get();

            $permissions = [];
            foreach ($roles as $role) {
                foreach ($role->allPermissionSlugs() as $slug) {
                    $permissions[] = $slug;
                }
            }

            return collect($permissions)
                ->filter(fn($slug) => is_string($slug) && trim($slug) !== '')
                ->map(fn($slug) => strtolower((string) $slug))
                ->unique()
                ->values()
                ->all();
        });
    }
}
