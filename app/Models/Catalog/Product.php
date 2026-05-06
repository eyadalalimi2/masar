<?php

namespace App\Models\Catalog;

use App\Models\Concerns\HasPublicUuid;
use App\Models\Orders\OrderItem;
use App\Models\Supplier\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use HasPublicUuid;
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    protected $fillable = [
        'uuid',
        'supplier_id',
        'category_id',
        'name',
        'model',
        'car_models',
        'production_year_from',
        'production_year_to',
        'description',
        'image',
        'status',
    ];

    protected $casts = [
        'car_models' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $product): void {
            $product->ensureUuidAssigned();
        });
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function productUnits()
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
