<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderStatusHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'actor_guard',
        'actor_id',
        'note',
    ];

    /**
     * Backward-compatibility alias for legacy reads.
     */
    public function getChangedAtAttribute()
    {
        return $this->created_at;
    }

    /**
     * Backward-compatibility alias for legacy writes.
     */
    public function setChangedAtAttribute($value): void
    {
        $this->attributes['created_at'] = $value;
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
