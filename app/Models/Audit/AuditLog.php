<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'event_type',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'device',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function scopeForUser(Builder $query, ?int $userId): Builder
    {
        if ($userId === null || $userId <= 0) {
            return $query;
        }

        return $query->where('user_id', $userId);
    }

    public function scopeForEvent(Builder $query, ?string $eventType): Builder
    {
        $eventType = is_string($eventType) ? trim($eventType) : '';
        if ($eventType === '') {
            return $query;
        }

        return $query->where('event_type', $eventType);
    }

    public function scopeForTable(Builder $query, ?string $tableName): Builder
    {
        $tableName = is_string($tableName) ? trim($tableName) : '';
        if ($tableName === '') {
            return $query;
        }

        return $query->where('table_name', $tableName);
    }

    public function scopeForRecord(Builder $query, ?int $recordId): Builder
    {
        if ($recordId === null || $recordId <= 0) {
            return $query;
        }

        return $query->where('record_id', $recordId);
    }

    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): Builder
    {
        if (is_string($from) && trim($from) !== '') {
            $query->where('created_at', '>=', $from);
        }

        if (is_string($to) && trim($to) !== '') {
            $query->where('created_at', '<=', $to);
        }

        return $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = is_string($term) ? trim($term) : '';
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($term): void {
            $inner->where('table_name', 'like', '%' . $term . '%')
                ->orWhere('event_type', 'like', '%' . $term . '%')
                ->orWhere('ip_address', 'like', '%' . $term . '%')
                ->orWhere('device', 'like', '%' . $term . '%');
        });
    }
}
