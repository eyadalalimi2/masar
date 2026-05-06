<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalPaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'portal_type',
        'portal_id',
        'payment_method_id',
        'account_number',
        'account_name',
        'note',
        'is_enabled',
    ];

    protected $casts = [
        'portal_id' => 'integer',
        'payment_method_id' => 'integer',
        'is_enabled' => 'boolean',
    ];

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}
