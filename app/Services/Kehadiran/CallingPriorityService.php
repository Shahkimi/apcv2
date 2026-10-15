<?php

declare(strict_types=1);

namespace App\Services\Kehadiran;

use App\Models\Pegawai;
use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

/**
 * Call-out priority between officer categories (RSVP, walk-in, OKU), set by admin in Kawalan.
 * Categories are mutually exclusive: OKU wins over rsvp; the rest split by rsvp.
 */
final class CallingPriorityService
{
    public const GROUP_RSVP = 'rsvp';

    public const GROUP_WALKIN = 'walkin';

    public const GROUP_OKU = 'oku';

    public const SETTING_KEY = 'calling.priority';

    public const DEFAULT_ORDER = [self::GROUP_RSVP, self::GROUP_WALKIN, self::GROUP_OKU];

    public function __construct(private readonly SettingsService $settings) {}

    /**
     * @return array<string, array{label: string, short: string, description: string, icon: string}>
     */
    public static function options(): array
    {
        return [
            self::GROUP_RSVP => [
                'label' => __('Pegawai RSVP'),
                'short' => __('RSVP'),
                'description' => __('Pegawai yang mengesahkan kehadiran (RSVP) dan telah hadir.'),
                'icon' => 'ri-mail-check-line',
            ],
            self::GROUP_WALKIN => [
                'label' => __('Tanpa RSVP (walk-in)'),
                'short' => __('Walk-in'),
                'description' => __('Pegawai yang hadir tanpa RSVP.'),
                'icon' => 'ri-walk-line',
            ],
            self::GROUP_OKU => [
                'label' => __('Pegawai OKU'),
                'short' => __('OKU'),
                'description' => __('Pegawai OKU yang hadir, tanpa mengira status RSVP.'),
                'icon' => 'ri-wheelchair-line',
            ],
        ];
    }

    public static function isValidOrder(mixed $order): bool
    {
        if (! is_array($order) || count($order) !== count(self::DEFAULT_ORDER)) {
            return false;
        }

        $values = array_values($order);
        sort($values);
        $expected = self::DEFAULT_ORDER;
        sort($expected);

        return $values === $expected;
    }

    /**
     * Stored order, sanitised: unknown keys and duplicates dropped, missing keys appended in default order.
     *
     * @return list<string>
     */
    public function order(): array
    {
        $stored = $this->settings->get(self::SETTING_KEY, self::DEFAULT_ORDER);
        $order = [];

        foreach (is_array($stored) ? $stored : [] as $group) {
            if (is_string($group) && in_array($group, self::DEFAULT_ORDER, true) && ! in_array($group, $order, true)) {
                $order[] = $group;
            }
        }

        foreach (self::DEFAULT_ORDER as $group) {
            if (! in_array($group, $order, true)) {
                $order[] = $group;
            }
        }

        return $order;
    }

    /**
     * @param  list<string>  $order
     */
    public function set(array $order): void
    {
        if (! self::isValidOrder($order)) {
            throw new InvalidArgumentException('Invalid calling priority order.');
        }

        $this->settings->set(self::SETTING_KEY, array_values($order));
    }

    /**
     * @return array<string, array{label: string, short: string, description: string, icon: string}>
     */
    public function orderedOptions(): array
    {
        $options = self::options();

        return collect($this->order())->mapWithKeys(fn (string $group) => [$group => $options[$group]])->all();
    }

    public function groupOf(Pegawai $pegawai): string
    {
        return match (true) {
            (bool) $pegawai->is_oku => self::GROUP_OKU,
            (bool) $pegawai->rsvp => self::GROUP_RSVP,
            default => self::GROUP_WALKIN,
        };
    }

    /**
     * Category rank first; second key only splits OKU into RSVP / walk-in by their relative rank.
     */
    public function applyGroupOrder(Builder $query): void
    {
        $rank = array_flip($this->order());

        $query->orderByRaw(sprintf(
            'CASE WHEN COALESCE(is_oku, 0) = 1 THEN %d WHEN COALESCE(rsvp, 0) = 1 THEN %d ELSE %d END',
            $rank[self::GROUP_OKU],
            $rank[self::GROUP_RSVP],
            $rank[self::GROUP_WALKIN],
        ))->orderByRaw(sprintf(
            'CASE WHEN COALESCE(rsvp, 0) = 1 THEN %d ELSE %d END',
            $rank[self::GROUP_RSVP],
            $rank[self::GROUP_WALKIN],
        ));
    }

    public function applyGroupFilter(Builder $query, string $group): void
    {
        match ($group) {
            self::GROUP_OKU => $query->where('is_oku', true),
            self::GROUP_RSVP => $query->where('is_oku', false)->where('rsvp', true),
            self::GROUP_WALKIN => $query->where('is_oku', false)->where('rsvp', false),
            default => throw new InvalidArgumentException("Invalid calling priority group: {$group}"),
        };
    }
}
