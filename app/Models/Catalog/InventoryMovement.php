<?php

namespace App\Models\Catalog;

use App\Models\Distribution\Branch;
use App\Models\Supplier\Agent;
use App\Models\Supplier\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'product_id',
        'product_unit_id',
        'branch_id',
        'agent_id',
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

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}
