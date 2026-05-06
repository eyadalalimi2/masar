<?php

namespace App\Models\Customer;

use App\Support\WorkingHoursCodec;
use App\Traits\HasWorkingHoursSchedule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;

class Workshop extends Authenticatable
{
    use HasFactory;
    use HasWorkingHoursSchedule;

    protected $table = 'accounts';

    protected $fillable = [
        'owner_type',
        'owner_id',
        'name',
        'phone',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('workshop_account_type', fn($query) => $query->where('account_type', 'workshop'));

        static::creating(function (self $model): void {
            $model->account_type = 'workshop';
            $model->owner_type = Customer::class;
        });

        static::saving(function (self $workshop): void {
            $value = $workshop->getAttribute('working_hours');

            if (is_array($value)) {
                $workshop->setAttribute('working_hours', WorkingHoursCodec::encode($value));

                return;
            }

            if (! is_string($value) || trim($value) === '') {
                $workshop->setAttribute('working_hours', WorkingHoursCodec::encode(WorkingHoursCodec::defaultSchedule()));
            }
        });
    }

    public function getCustomerIdAttribute(): ?int
    {
        return $this->owner_id !== null ? (int) $this->owner_id : null;
    }

    public function setCustomerIdAttribute(?int $value): void
    {
        $this->attributes['owner_id'] = $value;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'owner_id');
    }

    public function getWhatsappAttribute(): ?string
    {
        return $this->customer?->whatsapp;
    }

    public function getAddressAttribute(): ?string
    {
        return $this->customer?->address;
    }

    public function getGpsLocationAttribute(): ?string
    {
        return $this->customer?->gps_location;
    }

    public function getWorkingHoursAttribute(): ?string
    {
        return $this->customer?->working_hours;
    }

    public function getOwnerNameAttribute(): ?string
    {
        return $this->customer?->owner_name;
    }

    public function getNationalIdNumberAttribute(): ?string
    {
        return $this->customer?->national_id_number;
    }

    public function getNationalIdImageAttribute(): ?string
    {
        return $this->customer?->national_id_image;
    }

    public function getCommercialRegNumberAttribute(): ?string
    {
        return $this->customer?->commercial_reg_number;
    }

    public function getCommercialRegImageAttribute(): ?string
    {
        return $this->customer?->commercial_reg_image;
    }

    public function getLicenseNumberAttribute(): ?string
    {
        return $this->customer?->license_number;
    }

    public function getLicenseImageAttribute(): ?string
    {
        return $this->customer?->license_image;
    }

    public function getNationalIdImageUrlAttribute(): ?string
    {
        return $this->resolveSensitiveImageUrl($this->national_id_image);
    }

    public function getCommercialRegImageUrlAttribute(): ?string
    {
        return $this->resolveSensitiveImageUrl($this->commercial_reg_image);
    }

    public function getLicenseImageUrlAttribute(): ?string
    {
        return $this->resolveSensitiveImageUrl($this->license_image);
    }

    private function canViewSensitiveDocuments(): bool
    {
        if (Auth::guard('admin')->check()) {
            return true;
        }

        if (Auth::guard('workshop')->check() && (int) Auth::guard('workshop')->id() === (int) $this->id) {
            return true;
        }

        if (Auth::guard('customer')->check()) {
            return (int) (Auth::guard('customer')->id() ?? 0) === (int) ($this->customer_id ?? 0);
        }

        return false;
    }

    private function resolveSensitiveImageUrl(?string $path): ?string
    {
        if (! $this->canViewSensitiveDocuments()) {
            return null;
        }

        return $this->resolveImageUrl($path);
    }

    private function resolveImageUrl(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $imagePath = str_starts_with($path, 'storage/') ? $path : 'storage/' . $path;

        return asset($imagePath);
    }
}
