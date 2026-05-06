<?php

namespace App\Models\Lookup;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AccountStatus extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(fn() => Cache::forget('lookups:account_statuses:v1'));
        static::deleted(fn() => Cache::forget('lookups:account_statuses:v1'));
    }

    protected $table = 'account_statuses';

    protected $fillable = [
        'code',
        'name',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
