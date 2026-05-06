<?php

namespace App\Models\Supplier;

use App\Models\Catalog\Product;
use App\Models\Distribution\Branch;
use App\Models\Distribution\Distributor;
use App\Models\Finance\Payment;
use App\Models\Orders\Order;
use App\Support\WorkingHoursCodec;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Supplier extends Model
{
    use HasFactory;
    use SoftDeletes;

    private const WEEK_DAYS = [
        'saturday',
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
    ];

    protected $table = 'suppliers';

    protected $fillable = [
        'logo',
        'agent_image',
        'branch_manager_image',
        'branch_manager_password',
        'owner_name',
        'branch_manager_name',
        'business_name',
        'commercial_reg_number',
        'commercial_reg_image',
        'license_number',
        'license_image',
        'national_id_number',
        'national_id_image',
        'phone',
        'whatsapp',
        'address',
        'gps_location',
        'email',
        'working_hours',
        'status',
        'is_verified',
        'verified_at',
        'verified_by_user_id',
        'verification_requested_at',
        'verification_requested_by_user_id',
    ];

    protected $hidden = [
        'branch_manager_password',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'verification_requested_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $supplier): void {
            $value = $supplier->getAttribute('working_hours');

            if (is_array($value)) {
                $supplier->setAttribute('working_hours', WorkingHoursCodec::encode($value));

                return;
            }

            if (! is_string($value) || trim($value) === '') {
                $supplier->setAttribute('working_hours', WorkingHoursCodec::encode(WorkingHoursCodec::defaultSchedule()));
            }
        });
    }

    public function getLogoUrlAttribute(): ?string
    {
        $logo = $this->logo;

        if (! is_string($logo) || $logo === '') {
            return null;
        }

        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
            return $logo;
        }

        $logoPath = str_starts_with($logo, 'storage/') ? $logo : 'storage/' . $logo;

        return asset($logoPath);
    }
    public function getAgentImageUrlAttribute(): ?string
    {
        $img = $this->agent_image;
        if (!is_string($img) || $img === '') {
            return null;
        }
        if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
            return $img;
        }
        $imgPath = str_starts_with($img, 'storage/') ? $img : 'storage/' . $img;
        return asset($imgPath);
    }

    public function getBranchManagerImageUrlAttribute(): ?string
    {
        $img = $this->branch_manager_image;
        if (!is_string($img) || $img === '') {
            return null;
        }
        if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
            return $img;
        }
        $imgPath = str_starts_with($img, 'storage/') ? $img : 'storage/' . $img;
        return asset($imgPath);
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

        if (Auth::guard('agent')->check()) {
            $agentUser = Auth::guard('agent')->user();

            return (int) ($agentUser?->supplier_id ?? 0) === (int) $this->id;
        }

        return false;
    }

    private function resolveSensitiveImageUrl(?string $image): ?string
    {
        if (! $this->canViewSensitiveDocuments()) {
            return null;
        }

        if (! is_string($image) || $image === '') {
            return null;
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        $imagePath = str_starts_with($image, 'storage/') ? $image : 'storage/' . $image;

        return asset($imagePath);
    }

    public function getHasVerificationRequestAttribute(): bool
    {
        $agentId = (int) ($this->agentAccount?->id ?? 0);

        return $this->verification_requested_at !== null
            && $this->verification_requested_by_user_id !== null
            && $agentId > 0
            && (int) $this->verification_requested_by_user_id === $agentId;
    }

    public function getWorkingHoursScheduleAttribute(): array
    {
        return WorkingHoursCodec::decode($this->working_hours);
    }

    public function agentAccount()
    {
        return $this->hasOne(Agent::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function distributors()
    {
        return $this->hasMany(Distributor::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Order::class, 'supplier_id', 'order_id', 'id', 'id');
    }

    public function fieldChangeRequests()
    {
        return $this->hasMany(SupplierFieldChangeRequest::class);
    }
}
