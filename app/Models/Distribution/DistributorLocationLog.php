<?php

namespace App\Models\Distribution;

use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistributorLocationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'distributor_id',
        'order_id',
        'latitude',
        'longitude',
        'accuracy_meters',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy_meters' => 'float',
        ];
    }

    public function distributor()
    {
        return $this->belongsTo(Distributor::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
