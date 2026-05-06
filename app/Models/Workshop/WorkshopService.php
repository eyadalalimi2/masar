<?php

namespace App\Models\Workshop;

use App\Models\Customer\Workshop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkshopService extends Model
{
    use HasFactory;

    protected $fillable = [
        'workshop_id',
        'name',
        'description',
        'price',
        'duration_minutes',
        'requires_products',
        'is_package',
        'package_items',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'requires_products' => 'boolean',
            'is_package' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function workshop()
    {
        return $this->belongsTo(Workshop::class, 'workshop_id');
    }
}
