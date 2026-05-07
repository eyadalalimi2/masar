<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasPublicUuid
{
    protected function ensureUuidAssigned(): void
    {
        $value = $this->getAttribute('uuid');

        if (! is_string($value) || trim($value) === '') {
            $this->setAttribute('uuid', (string) Str::uuid());
        }
    }

    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?? $this->getRouteKeyName();
        $primaryKey = $this->getKeyName();

        $query = $this->newQuery()->where($field, $value);

        if ($field !== $primaryKey) {
            $query->orWhere($primaryKey, $value);
        }

        if ($field !== 'uuid') {
            $query->orWhere('uuid', $value);
        }

        return $query->firstOrFail();
    }

    public function scopeSearchByUuid(Builder $query, ?string $uuid): Builder
    {
        if (! is_string($uuid) || trim($uuid) === '') {
            return $query;
        }

        return $query->where($query->qualifyColumn('uuid'), 'like', '%' . trim($uuid) . '%');
    }
}
