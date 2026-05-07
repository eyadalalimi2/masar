<?php

namespace App\Models\Distribution;

use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DistributorLocationLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'distributor_id',
        'order_id',
        'location',
        'accuracy_meters',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'accuracy_meters' => 'float',
        ];
    }

    public function scopeWithCoordinates($query)
    {
        return $query
            ->select('distributor_location_logs.*')
            ->selectRaw('ST_Y(distributor_location_logs.location) as latitude')
            ->selectRaw('ST_X(distributor_location_logs.location) as longitude');
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
