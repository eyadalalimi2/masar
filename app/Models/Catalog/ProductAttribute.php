<?php

namespace App\Models\Catalog;

use App\Models\Catalog\ProductAttributeValue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_filterable',
        'is_variation',
        'is_required',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_filterable' => 'boolean',
            'is_variation' => 'boolean',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function values()
    {
        return $this->hasMany(ProductAttributeValue::class);
    }
}
