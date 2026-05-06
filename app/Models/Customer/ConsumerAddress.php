<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsumerAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'consumer_id',
        'label',
        'contact_name',
        'phone',
        'address_line',
        'gps_location',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function consumer()
    {
        return $this->belongsTo(Consumer::class);
    }
}
