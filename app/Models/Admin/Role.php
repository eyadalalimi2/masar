<?php

namespace App\Models\Admin;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $table = 'admin_roles';

    protected $fillable = [
        'name',
        'slug',
    ];

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, 'admin_role_admin', 'role_id', 'admin_id')->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'admin_permission_role', 'role_id', 'permission_id')->withTimestamps();
    }
}
