<?php

namespace App\Models\Distribution;

use App\Models\Catalog\InventoryMovement;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductUnit;
use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BranchStockMovement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'product_id',
        'product_unit_id',
        'inventory_movement_id',
        'order_id',
        'distributor_id',
        'movement_type',
        'quantity',
        'stock_before',
        'stock_after',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'stock_before' => 'decimal:3',
            'stock_after' => 'decimal:3',
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

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function inventoryMovement()
    {
        return $this->belongsTo(InventoryMovement::class);
    }

    public function distributor()
    {
        return $this->belongsTo(Distributor::class);
    }
}
