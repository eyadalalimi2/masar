<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsumerLoyaltyPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'consumer_id',
        'source_type',
        'source_id',
        'points',
        'direction',
        'note',
        'awarded_at',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'awarded_at' => 'datetime',
        ];
    }

    public function consumer()
    {
        return $this->belongsTo(Consumer::class);
    }
}
