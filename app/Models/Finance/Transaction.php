<?php

namespace App\Models\Finance;

use App\Models\Finance\CustomerAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'customer_account_id',
        'type',
        'amount',
        'description',
    ];

    public function customerAccount()
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_account_id')->withTrashed();
    }
}
