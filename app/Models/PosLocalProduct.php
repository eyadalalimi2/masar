<?php

namespace App\Models;

use App\Models\Catalog\Product;
use App\Models\Catalog\ProductUnit;
use App\Models\Distribution\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosLocalProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_account_id',
        'branch_id',
        'product_id',
        'product_unit_id',
        'purchase_price',
        'selling_price',
        'local_quantity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'local_quantity' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function posAccount()
    {
        return $this->belongsTo(Pos::class, 'pos_account_id');
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

    public function sales()
    {
        return $this->hasMany(PosSale::class);
    }
}
