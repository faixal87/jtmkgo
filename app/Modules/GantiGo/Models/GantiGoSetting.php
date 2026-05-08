<?php

namespace App\Modules\GantiGo\Models;

use Illuminate\Database\Eloquent\Model;

class GantiGoSetting extends Model
{
    protected $fillable = [
        'setting_key',
        'setting_value',
    ];

    public static function value(string $key, mixed $default = null): mixed
    {
        return static::query()->where('setting_key', $key)->value('setting_value') ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = static::value($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
        );
    }
}
