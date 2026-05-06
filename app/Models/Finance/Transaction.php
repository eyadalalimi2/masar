<?php

namespace App\Models\Finance;

use App\Models\Concerns\HasPublicUuid;
use App\Models\Finance\CustomerAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory;
    use HasPublicUuid;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'customer_account_id',
        'type',
        'amount',
        'description',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $transaction): void {
            $transaction->ensureUuidAssigned();
        });
    }

    public function customerAccount()
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_account_id')->withTrashed();
    }
}
