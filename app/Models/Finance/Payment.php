<?php

namespace App\Models\Finance;

use App\Models\Distribution\Distributor;
use App\Models\Orders\Order;
use App\Models\Supplier\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'order_payments';

    public const TYPE_CASH = 'cash';
    public const TYPE_CREDIT = 'credit';
    public const STATUS_PAID = 'paid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_REFUNDED = 'refunded';

    public const PAYMENT_TYPES = [self::TYPE_CASH, self::TYPE_CREDIT];
    public const PAYMENT_STATUSES = [self::STATUS_PAID, self::STATUS_PARTIAL, self::STATUS_UNPAID, self::STATUS_REFUNDED];

    protected $fillable = [
        'uuid',
        'order_id',
        'payment_method_id',
        'account_id',
        'amount',
        'currency',
        'status',
        'transaction_reference',
        'paid_at',
        'notes',
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
        return $this->belongsTo(Order::class)->withTrashed();
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id')->withTrashed();
    }

    public function scopeOfPaymentType(Builder $query, string $type): Builder
    {
        $type = strtolower(trim($type));

        if (! in_array($type, self::PAYMENT_TYPES, true)) {
            return $query;
        }

        if ($type === self::TYPE_CASH) {
            return $query->where(function (Builder $inner): void {
                $inner->whereHas('paymentMethod', fn(Builder $methodQuery) => $methodQuery->where('type', 'offline'))
                    ->orWhere('transaction_reference', 'like', 'TYPE:cash|%');
            });
        }

        return $query->where(function (Builder $inner): void {
            $inner->whereHas('paymentMethod', fn(Builder $methodQuery) => $methodQuery->where('type', 'online'))
                ->orWhere('transaction_reference', 'like', 'TYPE:credit|%');
        });
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

    public function getSupplierAttribute(): ?Supplier
    {
        return $this->order?->supplier;
    }

    public function getDistributorAttribute(): ?Distributor
    {
        return $this->order?->distributor;
    }
}
