<?php

namespace App\Models\Workshop;

use App\Models\Catalog\Product;
use App\Models\Catalog\ProductUnit;
use App\Models\Distribution\BranchProductStock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkshopPurchaseOrderItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'purchase_order_id',
        'branch_product_stock_id',
        'product_id',
        'product_unit_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(WorkshopPurchaseOrder::class, 'purchase_order_id');
    }

    public function stock()
    {
        return $this->belongsTo(BranchProductStock::class, 'branch_product_stock_id');
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
