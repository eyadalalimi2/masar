<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $table = 'admin_subscription_plans';

    protected $fillable = [
        'name',
        'slug',
        'price',
        'billing_cycle',
        'orders_limit',
        'users_limit',
        'features',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'orders_limit' => 'integer',
            'users_limit' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
