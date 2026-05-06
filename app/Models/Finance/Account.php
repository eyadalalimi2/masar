<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_INACTIVE];

    protected $table = 'accounts';

    protected $fillable = [
        'uuid',
        'account_type',
        'owner_type',
        'owner_id',
        'balance',
        'currency',
        'status',
        'name',
        'phone',
        'password',
        'fcm_token',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'balance' => 'decimal:2',
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo('owner');
    }
}
