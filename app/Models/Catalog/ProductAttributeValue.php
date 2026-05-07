<?php

namespace App\Models\Catalog;

use App\Models\Catalog\ProductConfiguration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_attribute_id',
        'value',
        'normalized_value',
        'slug',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }

    public function configurations()
    {
        return $this->belongsToMany(
            ProductConfiguration::class,
            'product_configuration_attribute_values',
            'product_attribute_value_id',
            'product_configuration_id'
        )->withTimestamps();
    }
}
