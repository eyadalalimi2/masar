<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsumerVehicleProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'consumer_id',
        'nickname',
        'plate_number',
        'brand',
        'model',
        'production_year',
        'last_odometer_km',
        'notes',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'production_year' => 'integer',
            'last_odometer_km' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function consumer()
    {
        return $this->belongsTo(Consumer::class);
    }
}
