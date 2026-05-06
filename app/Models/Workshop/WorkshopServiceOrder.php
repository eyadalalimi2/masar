<?php

namespace App\Models\Workshop;

use App\Models\Consumer;
use App\Models\Customer\Workshop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkshopServiceOrder extends Model
{
    use HasFactory;

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_REQUESTED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'workshop_id',
        'service_id',
        'appointment_id',
        'order_number',
        // Historical snapshot fields for customer identity at service time.
        'snapshot_customer_name',
        'snapshot_customer_phone',
        'consumer_id',
        'vehicle_plate_number',
        'vehicle_brand',
        'vehicle_model',
        'vehicle_production_year',
        'odometer_km',
        'service_fee',
        'products_total',
        'total_amount',
        'commission_rule_id',
        'commission_percent',
        'commission_value',
        'platform_service_fee',
        'platform_fixed_fee',
        'payable_total',
        'status',
        'notes',
        'used_products',
    ];

    protected function casts(): array
    {
        return [
            'service_fee' => 'decimal:2',
            'products_total' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'consumer_id' => 'integer',
            'vehicle_production_year' => 'integer',
            'odometer_km' => 'integer',
            'commission_percent' => 'decimal:2',
            'commission_value' => 'decimal:2',
            'platform_service_fee' => 'decimal:2',
            'platform_fixed_fee' => 'decimal:2',
            'payable_total' => 'decimal:2',
            'used_products' => 'array',
        ];
    }

    public function workshop()
    {
        return $this->belongsTo(Workshop::class, 'workshop_id');
    }

    public function service()
    {
        return $this->belongsTo(WorkshopService::class, 'service_id');
    }

    public function consumer()
    {
        return $this->belongsTo(Consumer::class, 'consumer_id');
    }

    public function appointment()
    {
        return $this->belongsTo(WorkshopAppointment::class, 'appointment_id');
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
