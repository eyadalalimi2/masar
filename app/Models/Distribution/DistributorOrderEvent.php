<?php

namespace App\Models\Distribution;

use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DistributorOrderEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'distributor_id',
        'order_id',
        'stage',
        'note',
        'delivery_proof_image',
        'delivery_signature',
        'proof_captured_at',
        'route_sequence',
        'event_source',
    ];

    protected function casts(): array
    {
        return [
            'proof_captured_at' => 'datetime',
            'route_sequence' => 'integer',
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
