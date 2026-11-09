<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        try {
            return static::query()->where('key', $key)->value('value') ?? $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function put(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        if ($key === 'timezone') {
            Cache::forever('settings.timezone', $value);
        }
    }
}
