<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_value_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variantValue()
    {
        return $this->belongsTo(VariantValue::class);
    }

    public function variantUnits()
    {
        return $this->hasMany(ProductVariantUnit::class);
    }
}






