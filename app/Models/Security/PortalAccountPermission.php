<?php

namespace App\Models\Security;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalAccountPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'guard_name',
        'account_id',
        'permission',
        'is_granted',
    ];

    protected function casts(): array
    {
        return [
            'is_granted' => 'boolean',
        ];
    }
}
