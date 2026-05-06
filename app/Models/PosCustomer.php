<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosCustomer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pos_account_id',
        'name',
        'phone',
        'notes',
        'status',
    ];

    public function posAccount()
    {
        return $this->belongsTo(Pos::class, 'pos_account_id');
    }
}
