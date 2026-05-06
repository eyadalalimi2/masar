<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariantUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'unit_id',
        'wholesale_price',
        'retail_price',
    ];

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}






