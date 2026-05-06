<?php

namespace App\Models;

use App\Models\Admin\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    public function hasPermission(string $permissionSlug): bool
    {
        $roles = $this->roles()->with('permissions')->get();

        // Security hardening: deny access until explicit roles are assigned.
        if ($roles->isEmpty()) {
            return false;
        }

        if ($roles->contains(fn(Role $role) => $role->slug === 'super-admin')) {
            return true;
        }

        return $roles->contains(function (Role $role) use ($permissionSlug) {
            return $role->permissions->contains('slug', $permissionSlug);
        });
    }
}
