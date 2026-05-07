<?php

namespace App\Models\Catalog;

use App\Models\Catalog\ProductConfiguration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductConfigurationUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_configuration_id',
        'unit_id',
        'wholesale_price',
        'retail_price',
        'conversion_factor',
        'stock_quantity',
        'low_stock_threshold',
    ];

    protected function casts(): array
    {
        return [
            'wholesale_price' => 'decimal:2',
            'retail_price' => 'decimal:2',
            'conversion_factor' => 'decimal:4',
            'stock_quantity' => 'decimal:3',
            'low_stock_threshold' => 'decimal:3',
        ];
    }

    public function configuration()
    {
        return $this->belongsTo(ProductConfiguration::class, 'product_configuration_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
