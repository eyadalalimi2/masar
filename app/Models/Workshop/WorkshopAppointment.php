<?php

namespace App\Models\Workshop;

use App\Models\Customer\Workshop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkshopAppointment extends Model
{
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'workshop_id',
        'service_id',
        // Historical snapshot fields for appointment customer details.
        'snapshot_customer_name',
        'snapshot_customer_phone',
        'vehicle_details',
        'appointment_at',
        'estimated_minutes',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'appointment_at' => 'datetime',
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
