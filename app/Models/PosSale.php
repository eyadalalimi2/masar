<?php

namespace App\Models;

use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosSale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pos_account_id',
        'pos_local_product_id',
        'order_id',
        'product_name',
        // Historical snapshot fields for invoice integrity.
        'snapshot_customer_name',
        'snapshot_customer_phone',
        'sale_channel',
        'quantity',
        'unit_price',
        'gross_amount',
        'discount_type',
        'discount_value',
        'discount_amount',
        'campaign_code',
        'total_amount',
        'profit_amount',
        'note',
        'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'profit_amount' => 'decimal:2',
            'sold_at' => 'datetime',
        ];
    }

    public function posAccount()
    {
        return $this->belongsTo(Pos::class, 'pos_account_id');
    }

    public function localProduct()
    {
        return $this->belongsTo(PosLocalProduct::class, 'pos_local_product_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getCustomerNameAttribute(): ?string
    {
        return $this->snapshot_customer_name;
    }

    public function setCustomerNameAttribute($value): void
    {
        $this->attributes['snapshot_customer_name'] = $value;
    }

    public function getCustomerPhoneAttribute(): ?string
    {
        return $this->snapshot_customer_phone;
    }

    public function setCustomerPhoneAttribute($value): void
    {
        $this->attributes['snapshot_customer_phone'] = $value;
    }
}
