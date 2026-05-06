<?php

namespace App\Models\Distribution;

use App\Models\Catalog\Product;
use App\Models\Catalog\ProductUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BranchProductStock extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'product_id',
        'product_unit_id',
        'quantity',
        'selling_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'selling_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
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
