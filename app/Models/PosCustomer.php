<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosCustomer extends Model
{
    use HasFactory;

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
