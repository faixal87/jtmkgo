<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Throwable;

class SafeArrayCache
{
    /**
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @param  callable(): array<mixed>  $callback
     * @param  array<int, string>  $requiredKeys
     * @return array<mixed>
     */
    public static function remember(string $key, mixed $ttl, callable $callback, array $requiredKeys = []): array
    {
        try {
            $cached = Cache::get($key);
        } catch (Throwable) {
            Cache::forget($key);
            $cached = null;
        }

        if (self::isPlainArray($cached) && self::hasRequiredKeys($cached, $requiredKeys)) {
            return $cached;
        }

        $value = $callback();
        $value = is_array($value) ? $value : [];
        $value = self::isPlainArray($value) ? $value : [];
        $value = self::hasRequiredKeys($value, $requiredKeys) ? $value : [];

        Cache::put($key, $value, $ttl);

        return $value;
    }

    /**
     * @param  callable(): array<mixed>  $callback
     * @param  array<int, string>  $requiredKeys
     * @return array<mixed>
     */
    public static function rememberForever(string $key, callable $callback, array $requiredKeys = []): array
    {
        try {
            $cached = Cache::get($key);
        } catch (Throwable) {
            Cache::forget($key);
            $cached = null;
        }

        if (self::isPlainArray($cached) && self::hasRequiredKeys($cached, $requiredKeys)) {
            return $cached;
        }

        $value = $callback();
        $value = is_array($value) ? $value : [];
        $value = self::isPlainArray($value) ? $value : [];
        $value = self::hasRequiredKeys($value, $requiredKeys) ? $value : [];

        Cache::forever($key, $value);

        return $value;
    }

    private static function isPlainArray(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (is_array($item) && ! self::isPlainArray($item)) {
                return false;
            }

            if (is_object($item) || is_resource($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<mixed>  $value
     * @param  array<int, string>  $requiredKeys
     */
    private static function hasRequiredKeys(array $value, array $requiredKeys): bool
    {
        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $value)) {
                return false;
            }
        }

        return true;
    }
}
