<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionRule extends Model
{
    use HasFactory;

    public const ENTITY_TYPES = [
        'global',
        'supplier',
        'branch',
        'distributor',
        'customer',
        'consumer',
        'pos',
        'workshop',
    ];

    protected $table = 'admin_commission_rules';

    protected $fillable = [
        'name',
        'entity_type',
        'entity_id',
        'region_key',
        'commission_percent',
        'service_fee',
        'fixed_fee',
        'priority',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'commission_percent' => 'float',
            'service_fee' => 'float',
            'fixed_fee' => 'float',
            'priority' => 'integer',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
