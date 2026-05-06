<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Branch extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'accounts';

    protected $fillable = [
        'owner_type',
        'owner_id',
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

    protected static function booted(): void
    {
        static::addGlobalScope('branch_account_type', fn($query) => $query->where('account_type', 'branch'));

        static::creating(function (self $model): void {
            $model->account_type = 'branch';
            $model->owner_type = \App\Models\Distribution\Branch::class;
        });
    }

    public function getBranchIdAttribute(): ?int
    {
        return $this->owner_id !== null ? (int) $this->owner_id : null;
    }

    public function setBranchIdAttribute(?int $value): void
    {
        $this->attributes['owner_id'] = $value;
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Distribution\Branch::class, 'owner_id');
    }
}
