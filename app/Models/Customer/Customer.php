<?php

namespace App\Models\Customer;

use App\Models\Concerns\HasPublicUuid;
use App\Models\Finance\CustomerAccount;
use App\Models\Orders\Order;
use App\Support\WorkingHoursCodec;
use App\Traits\HasWorkingHoursSchedule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Customer extends Authenticatable
{
    use HasFactory;
    use HasPublicUuid;
    use HasWorkingHoursSchedule;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'type',
        'name',
        'phone',
        'password',
        'whatsapp',
        'address',
        'gps_location',
        'working_hours',
        'owner_name',
        'owner_image',
        'logo',
        'store_images',
        'national_id_number',
        'national_id_image',
        'commercial_reg_number',
        'commercial_reg_image',
        'license_number',
        'license_image',
        'status',
        'is_verified',
        'verified_at',
        'verified_by_user_id',
        'verification_requested_at',
        'verification_requested_by_user_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'store_images' => 'array',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'verification_requested_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $customer): void {
            $customer->ensureUuidAssigned();
        });

        static::saving(function (self $customer): void {
            $value = $customer->getAttribute('working_hours');

            if (is_array($value)) {
                $customer->setAttribute('working_hours', WorkingHoursCodec::encode($value));

                return;
            }

            if (! is_string($value) || trim($value) === '') {
                $customer->setAttribute('working_hours', WorkingHoursCodec::encode(WorkingHoursCodec::defaultSchedule()));
            }
        });
    }

    public function account()
    {
        return $this->hasOne(CustomerAccount::class, 'owner_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function getNationalIdImageUrlAttribute(): ?string
    {
        return $this->resolveSensitiveImageUrl($this->national_id_image);
    }

    public function getCommercialRegImageUrlAttribute(): ?string
    {
        return $this->resolveSensitiveImageUrl($this->commercial_reg_image);
    }

    public function getOwnerImageUrlAttribute(): ?string
    {
        return $this->resolveImageUrl($this->owner_image);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->resolveImageUrl($this->logo);
    }

    public function getStoreImageUrlsAttribute(): array
    {
        $images = $this->store_images;

        if (is_string($images)) {
            $decoded = json_decode($images, true);
            $images = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($images)) {
            return [];
        }

        return array_values(array_filter(array_map(fn($path) => $this->resolveImageUrl(is_string($path) ? $path : null), $images)));
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

        $customerId = (int) $this->id;

        if (Auth::guard('customer')->check() && (int) Auth::guard('customer')->id() === $customerId) {
            return true;
        }

        if (Auth::guard('workshop')->check()) {
            $workshopUser = Auth::guard('workshop')->user();

            return (int) ($workshopUser?->customer_id ?? 0) === $customerId;
        }

        if (Auth::guard('pos')->check()) {
            $posUser = Auth::guard('pos')->user();

            return (int) ($posUser?->customer_id ?? 0) === $customerId;
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
