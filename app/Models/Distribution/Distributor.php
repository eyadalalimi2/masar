<?php

namespace App\Models\Distribution;

use App\Models\Finance\Payment;
use App\Models\Orders\Order;
use App\Models\Supplier\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Distributor extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'branch_id',
        'name',
        'phone',
        'password',
        'image',
        'vehicle_type',
        'distribution_points',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Order::class, 'distributor_id', 'order_id', 'id', 'id');
    }

    public function account()
    {
        return $this->hasOne(DistributorAccount::class);
    }
}
