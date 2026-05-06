<?php

namespace App\Models;

use App\Models\Customer\Customer;
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

    public function localProducts()
    {
        return $this->hasMany(PosLocalProduct::class, 'pos_account_id');
    }

    public function sales()
    {
        return $this->hasMany(PosSale::class, 'pos_account_id');
    }

    public function customerProfile()
    {
        return $this->belongsTo(Customer::class, 'owner_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'owner_id');
    }

    public function getWhatsappAttribute(): ?string
    {
        return $this->customer?->whatsapp;
    }

    public function getAddressAttribute(): ?string
    {
        return $this->customer?->address;
    }

    public function getGpsLocationAttribute(): ?string
    {
        return $this->customer?->gps_location;
    }

    public function getOwnerNameAttribute(): ?string
    {
        return $this->customer?->owner_name;
    }

    public function getNationalIdNumberAttribute(): ?string
    {
        return $this->customer?->national_id_number;
    }

    public function getNationalIdImageAttribute(): ?string
    {
        return $this->customer?->national_id_image;
    }

    public function getCommercialRegNumberAttribute(): ?string
    {
        return $this->customer?->commercial_reg_number;
    }

    public function getCommercialRegImageAttribute(): ?string
    {
        return $this->customer?->commercial_reg_image;
    }

    public function getLicenseNumberAttribute(): ?string
    {
        return $this->customer?->license_number;
    }

    public function getLicenseImageAttribute(): ?string
    {
        return $this->customer?->license_image;
    }

    public function getNationalIdImageUrlAttribute(): ?string
    {
        return $this->resolveImageUrl($this->national_id_image);
    }

    public function getCommercialRegImageUrlAttribute(): ?string
    {
        return $this->resolveImageUrl($this->commercial_reg_image);
    }

    public function getLicenseImageUrlAttribute(): ?string
    {
        return $this->resolveImageUrl($this->license_image);
    }

    private function resolveImageUrl(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $imagePath = str_starts_with($path, 'storage/') ? $path : 'storage/' . $path;

        return asset($imagePath);
    }
}
