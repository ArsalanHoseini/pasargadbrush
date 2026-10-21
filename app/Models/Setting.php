<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('settings'));
        static::deleted(fn () => Cache::forget('settings'));
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        // Cache a plain array, not a Collection — caching objects is fragile across
        // cache-driver (de)serialization boundaries.
        $all = Cache::rememberForever('settings', fn () => static::query()->pluck('value', 'key')->all());

        return $all[$key] ?? $default;
    }
}
