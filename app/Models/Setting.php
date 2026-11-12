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

    public static function schoolDays(): array
    {
        $decoded = json_decode((string) static::get('school_days', '[1,2,3,4,5]'), true);

        if (! is_array($decoded)) {
            return [1, 2, 3, 4, 5];
        }

        $days = array_values(array_filter(array_map('intval', $decoded), fn ($day) => $day >= 1 && $day <= 7));

        return count($days) > 0 ? $days : [1, 2, 3, 4, 5];
    }
}
