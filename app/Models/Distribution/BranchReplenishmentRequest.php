<?php

namespace App\Models\Distribution;

use App\Models\Catalog\Product;
use App\Models\Catalog\ProductUnit;
use App\Models\Supplier\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchReplenishmentRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    protected $fillable = [
        'branch_id',
        'supplier_id',
        'product_id',
        'product_unit_id',
        'requested_quantity',
        'status',
        'note',
        'requested_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_quantity' => 'decimal:3',
            'requested_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class);
    }
}
