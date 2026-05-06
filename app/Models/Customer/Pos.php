<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Pos extends Authenticatable
{
    use HasFactory;

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
        static::addGlobalScope('pos_account_type', fn($query) => $query->where('account_type', 'pos'));

        static::creating(function (self $model): void {
            $model->account_type = 'pos';
            $model->owner_type = Customer::class;
        });
    }

    public function getCustomerIdAttribute(): ?int
    {
        return $this->owner_id !== null ? (int) $this->owner_id : null;
    }

    public function setCustomerIdAttribute(?int $value): void
    {
        $this->attributes['owner_id'] = $value;
    }
}
