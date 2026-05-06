<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    use HasFactory;

    protected $table = 'admin_broadcasts';

    protected $fillable = [
        'title',
        'message',
        'target_type',
        'is_active',
        'scheduled_for',
        'dispatched_at',
        'created_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'scheduled_for' => 'datetime',
            'dispatched_at' => 'datetime',
        ];
    }
}
