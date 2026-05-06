<?php

namespace App\Models\Finance;

use App\Models\Customer\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAccount extends Model
{
    use HasFactory;

    protected $table = 'accounts';

    protected $fillable = [
        'owner_type',
        'owner_id',
        'name',
        'balance',
        'currency',
        'status',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('customer_account_type', fn($query) => $query->where('account_type', 'customer'));

        static::creating(function (self $account): void {
            $account->account_type = 'customer';
            $account->owner_type = Customer::class;
        });

        static::saving(function (self $account): void {
            $name = trim((string) ($account->name ?? ''));
            if ($name !== '') {
                return;
            }

            $resolvedName = trim((string) ($account->customer?->name ?? ''));
            if ($resolvedName !== '') {
                $account->name = $resolvedName;

                return;
            }

            $account->name = 'عميل';
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

    public function getCustomerNameAttribute(): ?string
    {
        return $this->name;
    }

    public function setCustomerNameAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'owner_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
