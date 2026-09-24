<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

final class EventModeService
{
    public const MODE_APC = 'apc';

    public const MODE_JASAMU = 'jasamu';

    public const SETTING_KEY = 'event.mode';

    private ?string $current = null;

    public function __construct(private readonly SettingsService $settings) {}

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::MODE_APC => 'Anugerah Pekerja Cemerlang (APC)',
            self::MODE_JASAMU => 'Jasamu Dikenang',
        ];
    }

    public static function isValid(string $mode): bool
    {
        return array_key_exists($mode, self::options());
    }

    public function current(): string
    {
        if ($this->current !== null) {
            return $this->current;
        }

        $stored = (string) $this->settings->get(self::SETTING_KEY, self::MODE_APC);

        return $this->current = self::isValid($stored) ? $stored : self::MODE_APC;
    }

    public function isJasamu(): bool
    {
        return $this->current() === self::MODE_JASAMU;
    }

    public function set(string $mode): void
    {
        if (! self::isValid($mode)) {
            throw new InvalidArgumentException("Invalid event mode: {$mode}");
        }

        $this->settings->set(self::SETTING_KEY, $mode);
        $this->current = $mode;
    }
}
