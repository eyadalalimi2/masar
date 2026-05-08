<?php

namespace App\Models\Distribution;

use App\Models\Distribution\Branch;
use App\Models\Distribution\BranchReplenishmentRequest;
use App\Models\Supplier\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchReplenishmentOrder extends Model
{
    use HasFactory;

    protected $table = 'branch_replenishment_orders';

    protected $fillable = [
        'branch_id',
        'supplier_id',
        'status',
        'note',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(BranchReplenishmentRequest::class, 'order_id');
    }
}
