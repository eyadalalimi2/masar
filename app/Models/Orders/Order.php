<?php

namespace App\Models\Orders;

use App\Models\Customer\Consumer;
use App\Models\Customer\Customer;
use App\Models\Distribution\DistributorLocationLog;
use App\Models\Distribution\DistributorOrderEvent;
use App\Models\Finance\Payment;
use App\Models\Distribution\Branch;
use App\Models\Distribution\Distributor;
use App\Models\Concerns\HasPublicUuid;
use App\Models\Supplier\Agent;
use App\Models\Supplier\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use HasPublicUuid;
    use SoftDeletes;

    public const BUYER_TYPE_CUSTOMER = Customer::class;
    public const BUYER_TYPE_CONSUMER = Consumer::class;
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_ASSIGNED,
        self::STATUS_ACCEPTED,
        self::STATUS_PICKED_UP,
        self::STATUS_OUT_FOR_DELIVERY,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'uuid',
        'supplier_id',
        'branch_id',
        'distributor_id',
        'buyer_type',
        'buyer_id',
        'seller_type',
        'seller_id',
        // Historical snapshot fields: keep customer details as recorded at order time.
        'snapshot_customer_name',
        'snapshot_customer_phone',
        'snapshot_customer_address',
        'total_price',
        'commission_rule_id',
        'commission_percent',
        'commission_value',
        'platform_service_fee',
        'platform_fixed_fee',
        'payable_total',
        'status',
        'distributor_stage',
        'payment_method_id',
        'created_by_agent_id',
    ];

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
            'commission_percent' => 'decimal:2',
            'commission_value' => 'decimal:2',
            'platform_service_fee' => 'decimal:2',
            'platform_fixed_fee' => 'decimal:2',
            'payable_total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $order): void {
            $order->ensureUuidAssigned();
        });
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function distributor()
    {
        return $this->belongsTo(Distributor::class)->withTrashed();
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer()
    {
        $relation = $this->belongsTo(Customer::class, 'buyer_id')->withTrashed();

        if ((string) ($this->buyer_type ?? '') !== self::BUYER_TYPE_CUSTOMER) {
            $relation->whereRaw('1 = 0');
        }

        return $relation;
    }

    public function consumer()
    {
        $relation = $this->belongsTo(Consumer::class, 'buyer_id');

        if ((string) ($this->buyer_type ?? '') !== self::BUYER_TYPE_CONSUMER) {
            $relation->whereRaw('1 = 0');
        }

        return $relation;
    }

    public function buyer(): MorphTo
    {
        return $this->morphTo('buyer');
    }

    public function creator()
    {
        return $this->belongsTo(Agent::class, 'created_by_agent_id');
    }

    /**
     * Backward-compatibility alias for legacy reads.
     */
    public function getCreatedByAttribute(): ?int
    {
        return $this->created_by_agent_id;
    }

    /**
     * Backward-compatibility alias for legacy writes.
     */
    public function setCreatedByAttribute($value): void
    {
        $this->attributes['created_by_agent_id'] = $value;
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function distributorEvents()
    {
        return $this->hasMany(DistributorOrderEvent::class);
    }

    public function locationLogs()
    {
        return $this->hasMany(DistributorLocationLog::class)->withCoordinates();
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('buyer_type', self::BUYER_TYPE_CUSTOMER)
            ->where('buyer_id', $customerId);
    }

    public function scopeForConsumer($query, int $consumerId)
    {
        return $query->where('buyer_type', self::BUYER_TYPE_CONSUMER)
            ->where('buyer_id', $consumerId);
    }

    public function isBusinessBuyer(): bool
    {
        return (string) $this->buyer_type === self::BUYER_TYPE_CUSTOMER;
    }

    public function isConsumerBuyer(): bool
    {
        return (string) $this->buyer_type === self::BUYER_TYPE_CONSUMER;
    }

    public function getCustomerTypeAttribute(): ?string
    {
        if ($this->isBusinessBuyer()) {
            return 'b2b';
        }

        if ($this->isConsumerBuyer()) {
            return 'b2c';
        }

        return null;
    }

    /**
     * Backward-compatibility alias for legacy reads.
     */
    public function getCustomerNameAttribute(): ?string
    {
        return $this->snapshot_customer_name;
    }

    /**
     * Backward-compatibility alias for legacy writes.
     */
    public function setCustomerNameAttribute($value): void
    {
        $this->attributes['snapshot_customer_name'] = $value;
    }

    /**
     * Backward-compatibility alias for legacy reads.
     */
    public function getCustomerPhoneAttribute(): ?string
    {
        return $this->snapshot_customer_phone;
    }

    /**
     * Backward-compatibility alias for legacy writes.
     */
    public function setCustomerPhoneAttribute($value): void
    {
        $this->attributes['snapshot_customer_phone'] = $value;
    }

    /**
     * Backward-compatibility alias for legacy reads.
     */
    public function getCustomerAddressAttribute(): ?string
    {
        return $this->snapshot_customer_address;
    }

    /**
     * Backward-compatibility alias for legacy writes.
     */
    public function setCustomerAddressAttribute($value): void
    {
        $this->attributes['snapshot_customer_address'] = $value;
    }

    public function getCustomerIdAttribute(): ?int
    {
        return $this->isBusinessBuyer() ? (int) $this->buyer_id : null;
    }

    public function getConsumerIdAttribute(): ?int
    {
        return $this->isConsumerBuyer() ? (int) $this->buyer_id : null;
    }

    public function setCustomerTypeAttribute($value): void
    {
        if ($value === 'b2b') {
            $this->attributes['buyer_type'] = self::BUYER_TYPE_CUSTOMER;
        }

        if ($value === 'b2c') {
            $this->attributes['buyer_type'] = self::BUYER_TYPE_CONSUMER;
        }
    }

    public function setCustomerIdAttribute($value): void
    {
        if ((int) $value > 0) {
            $this->attributes['buyer_type'] = self::BUYER_TYPE_CUSTOMER;
            $this->attributes['buyer_id'] = (int) $value;
        }
    }

    public function setConsumerIdAttribute($value): void
    {
        if ((int) $value > 0) {
            $this->attributes['buyer_type'] = self::BUYER_TYPE_CONSUMER;
            $this->attributes['buyer_id'] = (int) $value;
        }
    }

    public function getPaymentMethodNameAttribute(): ?string
    {
        $payment = $this->relationLoaded('latestPayment')
            ? $this->latestPayment
            : $this->latestPayment()->with('paymentMethod')->first();

        return $payment?->paymentMethod?->name;
    }

    public function getPaymentAccountNumberAttribute(): ?string
    {
        $payment = $this->relationLoaded('latestPayment')
            ? $this->latestPayment
            : $this->latestPayment()->with('account')->first();

        $phone = trim((string) ($payment?->account?->phone ?? ''));
        if ($phone !== '') {
            return $phone;
        }

        return $this->extractPaymentNoteLine((string) ($payment?->notes ?? null), 'رقم الحساب:');
    }

    public function getPaymentAccountNameAttribute(): ?string
    {
        $payment = $this->relationLoaded('latestPayment')
            ? $this->latestPayment
            : $this->latestPayment()->with('account')->first();

        $name = trim((string) ($payment?->account?->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        return $this->extractPaymentNoteLine((string) ($payment?->notes ?? null), 'اسم الحساب:');
    }

    public function getPaymentNoteAttribute(): ?string
    {
        $payment = $this->relationLoaded('latestPayment')
            ? $this->latestPayment
            : $this->latestPayment()->first();

        $notes = trim((string) ($payment?->notes ?? ''));

        return $notes !== '' ? $notes : null;
    }

    private function extractPaymentNoteLine(string $notes, string $prefix): ?string
    {
        if ($notes === '') {
            return null;
        }

        foreach (preg_split('/\r\n|\r|\n/', $notes) as $line) {
            $line = trim((string) $line);
            if ($line === '' || ! str_starts_with($line, $prefix)) {
                continue;
            }

            $value = trim(substr($line, strlen($prefix)));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
