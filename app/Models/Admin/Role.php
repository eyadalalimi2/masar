<?php

namespace App\Models\Admin;

use App\Models\Admin;
use App\Services\Security\PermissionCacheService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $table = 'admin_roles';

    protected $fillable = [
        'name',
        'slug',
        'parent_role_id',
        'hierarchy_level',
    ];

    protected static function booted(): void
    {
        static::saved(function (): void {
            app(PermissionCacheService::class)->bumpVersion();
        });

        static::deleted(function (): void {
            app(PermissionCacheService::class)->bumpVersion();
        });
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, 'admin_role_admin', 'role_id', 'admin_id')->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'admin_permission_role', 'role_id', 'permission_id')->withTimestamps();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_role_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_role_id');
    }

    /**
     * @return array<int, string>
     */
    public function allPermissionSlugs(): array
    {
        $visited = [];
        $slugs = [];
        $current = $this;

        while ($current !== null) {
            $currentId = (int) ($current->id ?? 0);
            if ($currentId > 0) {
                if (in_array($currentId, $visited, true)) {
                    break;
                }

                $visited[] = $currentId;
            }

            $permissionSlugs = $current->permissions
                ->pluck('slug')
                ->filter(fn($slug) => is_string($slug) && trim($slug) !== '')
                ->map(fn($slug) => strtolower((string) $slug))
                ->all();

            $slugs = array_merge($slugs, $permissionSlugs);

            if (! $current->relationLoaded('parent')) {
                $current->load('parent.permissions');
            }

            $current = $current->parent;
        }

        return collect($slugs)->unique()->values()->all();
    }
}
