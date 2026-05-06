<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariantType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function values()
    {
        return $this->hasMany(VariantValue::class);
    }
}






