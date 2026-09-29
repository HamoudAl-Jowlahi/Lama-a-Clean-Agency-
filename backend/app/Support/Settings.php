<?php

namespace App\Support;

use App\Models\AdminUser;
use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

/**
 * مصدر واحد للقيم التجارية: جدول settings (تعدّله الإدارة) ثم config/agency.php.
 *
 *   Settings::get('contracts.max_replacements')
 */
class Settings
{
    private const CACHE_KEY = 'agency.settings';

    public static function get(string $key, mixed $default = null): mixed
    {
        $overrides = self::overrides();

        if (array_key_exists($key, $overrides)) {
            return $overrides[$key];
        }

        return config('agency.'.$key, $default);
    }

    public static function set(string $key, mixed $value, ?AdminUser $by = null): void
    {
        if (! Arr::has(config('agency'), $key)) {
            throw new \InvalidArgumentException("Unknown agency setting [{$key}].");
        }

        Setting::updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $by?->id]);
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, mixed> */
    private static function overrides(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => Setting::pluck('value', 'key')->all());
    }
}
