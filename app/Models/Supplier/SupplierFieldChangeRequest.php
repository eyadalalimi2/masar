<?php

namespace App\Models\Supplier;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierFieldChangeRequest extends Model
{
    use HasFactory;

    protected $table = 'supplier_field_change_requests';

    protected $fillable = [
        'supplier_id',
        'requested_by_user_id',
        'field_key',
        'requested_value',
        'note',
        'document_path',
        'status',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function requestedByUser()
    {
        return $this->belongsTo(Agent::class, 'requested_by_user_id');
    }

    public function reviewedByUser()
    {
        return $this->belongsTo(Admin::class, 'reviewed_by_user_id');
    }
}
