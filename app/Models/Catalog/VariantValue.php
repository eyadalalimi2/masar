<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariantValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'variant_type_id',
        'value',
    ];

    public function type()
    {
        return $this->belongsTo(VariantType::class, 'variant_type_id');
    }

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}






