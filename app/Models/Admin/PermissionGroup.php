<?php

namespace App\Models\Admin;

use App\Services\Security\PermissionCacheService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionGroup extends Model
{
    use HasFactory;

    protected $table = 'admin_permission_groups';

    protected $fillable = [
        'group_key',
        'name',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
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
}
