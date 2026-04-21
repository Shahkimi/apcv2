<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

final class SettingsService
{
    private const CACHE_TTL_SECONDS = 3600;

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            "system_setting.{$key}",
            self::CACHE_TTL_SECONDS,
            fn (): mixed => SystemSetting::getValue($key, $default),
        );
    }

    public function set(string $key, mixed $value): void
    {
        SystemSetting::setValue($key, $value);
        Cache::forget("system_setting.{$key}");
    }

    public function showTableNumberInDialog(): bool
    {
        return (bool) $this->get('display.show_table_number_in_dialog', true);
    }
}
