<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Distributor extends Authenticatable
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
        static::addGlobalScope('distributor_account_type', fn($query) => $query->where('account_type', 'distributor'));

        static::creating(function (self $model): void {
            $model->account_type = 'distributor';
            $model->owner_type = \App\Models\Distribution\Distributor::class;
        });
    }

    public function getDistributorIdAttribute(): ?int
    {
        return $this->owner_id !== null ? (int) $this->owner_id : null;
    }

    public function setDistributorIdAttribute(?int $value): void
    {
        $this->attributes['owner_id'] = $value;
    }

    public function distributor()
    {
        return $this->belongsTo(\App\Models\Distribution\Distributor::class, 'owner_id');
    }
}
