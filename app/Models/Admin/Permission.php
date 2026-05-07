<?php

namespace App\Models\Admin;

use App\Services\Security\PermissionCacheService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $table = 'admin_permissions';

    protected $fillable = [
        'name',
        'slug',
        'group_key',
    ];

    protected function casts(): array
    {
        return [
            'group_key' => 'string',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            app(PermissionCacheService::class)->bumpVersion();
        });

        static::deleted(function (): void {
            app(PermissionCacheService::class)->bumpVersion();
        });
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'admin_permission_role', 'permission_id', 'role_id')->withTimestamps();
    }
}
