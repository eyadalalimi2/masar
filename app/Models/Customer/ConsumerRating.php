<?php

namespace App\Models\Customer;

use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsumerRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'consumer_id',
        'store_type',
        'store_id',
        'order_id',
        'rating',
        'review',
    ];

    public function consumer()
    {
        return $this->belongsTo(Consumer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
