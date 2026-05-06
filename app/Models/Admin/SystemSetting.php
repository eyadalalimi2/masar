<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'admin_system_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        try {
            $setting = static::query()->where('key', $key)->first();
        } catch (\Throwable) {
            return $default;
        }

        if (! $setting) {
            return $default;
        }

        return $setting->value ?? $default;
    }

    public static function putValue(string $key, array $value, string $type = 'json'): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );
    }
}
