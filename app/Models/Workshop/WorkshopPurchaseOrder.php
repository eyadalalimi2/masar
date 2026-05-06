<?php

namespace App\Models\Workshop;

use App\Models\Customer\Workshop;
use App\Models\Distribution\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkshopPurchaseOrder extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_IN_TRANSIT,
        self::STATUS_RECEIVED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'workshop_id',
        'supplier_branch_id',
        'order_number',
        'supplier_branch_name',
        'total_amount',
        'status',
        'payment_method_id',
        'stock_deducted_at',
        'stock_restored_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'stock_deducted_at' => 'datetime',
            'stock_restored_at' => 'datetime',
        ];
    }

    public function workshop()
    {
        return $this->belongsTo(Workshop::class, 'workshop_id');
    }

    public function supplierBranch()
    {
        return $this->belongsTo(Branch::class, 'supplier_branch_id');
    }

    public function items()
    {
        return $this->hasMany(WorkshopPurchaseOrderItem::class, 'purchase_order_id');
    }

    public function payments()
    {
        return $this->hasMany(WorkshopOrderPayment::class, 'purchase_order_id');
    }

    public function latestPayment()
    {
        return $this->hasOne(WorkshopOrderPayment::class, 'purchase_order_id')->latestOfMany();
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
