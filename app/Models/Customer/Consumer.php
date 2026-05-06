<?php

namespace App\Models\Customer;

use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Consumer extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'password',
        'whatsapp',
        'address',
        'gps_location',
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

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function addresses()
    {
        return $this->hasMany(ConsumerAddress::class);
    }

    public function ratings()
    {
        return $this->hasMany(ConsumerRating::class);
    }

    public function vehicles()
    {
        return $this->hasMany(ConsumerVehicleProfile::class);
    }

    public function loyaltyPoints()
    {
        return $this->hasMany(ConsumerLoyaltyPoint::class);
    }
}
