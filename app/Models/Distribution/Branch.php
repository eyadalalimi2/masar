<?php

namespace App\Models\Distribution;

use App\Models\Orders\Order;
use App\Models\Supplier\Supplier;
use App\Support\WorkingHoursCodec;
use App\Traits\HasWorkingHoursSchedule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory;
    use HasWorkingHoursSchedule;
    use SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'name',
        'phone',
        'branch_manager_name',
        'branch_manager_image',
        'branch_manager_password',
        'address',
        'gps_location',
        'working_hours',
        'status',
    ];

    protected $hidden = [
        'branch_manager_password',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $branch): void {
            $value = $branch->getAttribute('working_hours');

            if (is_array($value)) {
                $branch->setAttribute('working_hours', WorkingHoursCodec::encode($value));

                return;
            }

            if (! is_string($value) || trim($value) === '') {
                $branch->setAttribute('working_hours', WorkingHoursCodec::encode(WorkingHoursCodec::defaultSchedule()));
            }
        });
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function distributors()
    {
        return $this->hasMany(Distributor::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function account()
    {
        return $this->hasOne(BranchAccount::class);
    }

    public function productStocks()
    {
        return $this->hasMany(BranchProductStock::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(BranchStockMovement::class);
    }

    public function replenishmentRequests()
    {
        return $this->hasMany(BranchReplenishmentRequest::class);
    }
}
