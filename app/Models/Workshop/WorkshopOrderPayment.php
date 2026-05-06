<?php

namespace App\Models\Workshop;

use App\Models\Finance\Account;
use App\Models\Finance\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WorkshopOrderPayment extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const TYPE_CASH = 'cash';
    public const TYPE_CREDIT = 'credit';

    public const STATUS_PAID = 'paid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_REFUNDED = 'refunded';

    protected $table = 'workshop_order_payments';

    protected $fillable = [
        'uuid',
        'purchase_order_id',
        'payment_method_id',
        'account_id',
        'amount',
        'currency',
        'status',
        'transaction_reference',
        'notes',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $payment): void {
            if (! is_string($payment->uuid) || trim($payment->uuid) === '') {
                $payment->uuid = (string) Str::uuid();
            }

            if (! is_string($payment->currency) || trim($payment->currency) === '') {
                $payment->currency = 'YER';
            }

            if (! is_string($payment->status) || trim($payment->status) === '') {
                $payment->status = self::STATUS_UNPAID;
            }
        });
    }

    public function order()
    {
        return $this->belongsTo(WorkshopPurchaseOrder::class, 'purchase_order_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id')->withTrashed();
    }

    public function getPaymentTypeAttribute(): string
    {
        $reference = strtolower((string) ($this->transaction_reference ?? ''));
        if (str_starts_with($reference, 'type:cash|')) {
            return self::TYPE_CASH;
        }

        if (str_starts_with($reference, 'type:credit|')) {
            return self::TYPE_CREDIT;
        }

        return (string) ($this->paymentMethod?->type === 'offline' ? self::TYPE_CASH : self::TYPE_CREDIT);
    }
}
