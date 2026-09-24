<?php

declare(strict_types=1);

namespace App\Services\Presentation;

use App\Services\SettingsService;

/**
 * Canonical shape + rules for the presentation display config used by
 * both the settings page (media::kawalan.presentation) and the presentation
 * screen (media::senarai.present). Single source of truth for defaults,
 * validation, normalization and merging so both places stay in sync.
 *
 * @phpstan-type PositionConfig array{mt_base: string, mt_sm: string, mt_md: string, translate_y: string}
 * @phpstan-type FontsConfig array{name_base: int, name_sm: int, name_md: int, jawatan_base: int, jawatan_sm: int, jawatan_md: int, ptj_base: string, ptj_sm: string, tarikh_base: int, tarikh_sm: int, tarikh_md: int, bersara_base: int, bersara_sm: int, bersara_md: int, tempoh_base: int, tempoh_sm: int, tempoh_md: int}
 * @phpstan-type DisplayConfig array{position: PositionConfig, fonts: FontsConfig}
 */
final class PresentationDisplayConfigService
{
    public const LIVE_SETTINGS_KEY = 'presentation_display_config';

    public const ACTIVE_PROFILE_SETTINGS_KEY = 'presentation_active_profile_id';

    /**
     * Jasamu-only font rows (px-based, like name/jawatan) added alongside the
     * always-present name/jawatan/ptj keys. Stored/validated unconditionally so
     * switching event mode or applying an old profile never breaks the shape.
     *
     * @var list<string>
     */
    private const JASAMU_FONT_KEYS = ['tarikh', 'bersara', 'tempoh'];

    /** @var list<string> */
    private const BREAKPOINTS = ['base', 'sm', 'md'];

    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    /**
     * @return array<int, string>
     */
    public function ptjFontOptions(): array
    {
        return [
            'text-xs',
            'text-sm',
            'text-base',
            'text-lg',
            'text-xl',
            'text-2xl',
            'text-3xl',
            'text-4xl',
            'text-5xl',
        ];
    }

    public function ptjClassToPx(string $class): int
    {
        return match ($class) {
            'text-xs' => 12,
            'text-sm' => 14,
            'text-base' => 16,
            'text-lg' => 18,
            'text-xl' => 20,
            'text-2xl' => 24,
            'text-3xl' => 30,
            'text-4xl' => 36,
            'text-5xl' => 48,
            default => 24,
        };
    }

    /**
     * @return array{position: array{mt_base: string, mt_sm: string, mt_md: string, translate_y: string}, fonts: array{name_base: int, name_sm: int, name_md: int, jawatan_base: int, jawatan_sm: int, jawatan_md: int, ptj_base: string, ptj_sm: string, tarikh_base: int, tarikh_sm: int, tarikh_md: int, bersara_base: int, bersara_sm: int, bersara_md: int, tempoh_base: int, tempoh_sm: int, tempoh_md: int}}
     */
    public function defaults(): array
    {
        $fonts = [
            'name_base' => 36,
            'name_sm' => 44,
            'name_md' => 52,
            'jawatan_base' => 30,
            'jawatan_sm' => 38,
            'jawatan_md' => 46,
            'ptj_base' => 'text-2xl',
            'ptj_sm' => 'text-4xl',
        ];

        foreach (self::JASAMU_FONT_KEYS as $k) {
            $fonts["{$k}_base"] = 24;
            $fonts["{$k}_sm"] = 30;
            $fonts["{$k}_md"] = 36;
        }

        return [
            'position' => [
                'mt_base' => '230px',
                'mt_sm' => '270px',
                'mt_md' => '320px',
                'translate_y' => '-72px',
            ],
            'fonts' => $fonts,
        ];
    }

    /**
     * Validation rules for a submitted config, addressable under an optional
     * dot-notation prefix (e.g. 'config.' when nested inside another payload).
     *
     * @return array<string, array<int, string>>
     */
    public function rules(string $prefix = ''): array
    {
        $ptjIn = 'in:'.implode(',', $this->ptjFontOptions());

        $rules = [
            $prefix.'position.mt_base' => ['required', 'integer', 'min:0', 'max:2000'],
            $prefix.'position.mt_sm' => ['required', 'integer', 'min:0', 'max:2000'],
            $prefix.'position.mt_md' => ['required', 'integer', 'min:0', 'max:2000'],
            $prefix.'position.translate_y' => ['required', 'integer', 'min:-1000', 'max:1000'],
            $prefix.'fonts.name_base' => ['required', 'integer', 'min:10', 'max:200'],
            $prefix.'fonts.name_sm' => ['required', 'integer', 'min:10', 'max:200'],
            $prefix.'fonts.name_md' => ['required', 'integer', 'min:10', 'max:200'],
            $prefix.'fonts.jawatan_base' => ['required', 'integer', 'min:10', 'max:200'],
            $prefix.'fonts.jawatan_sm' => ['required', 'integer', 'min:10', 'max:200'],
            $prefix.'fonts.jawatan_md' => ['required', 'integer', 'min:10', 'max:200'],
            $prefix.'fonts.ptj_base' => ['required', $ptjIn],
            $prefix.'fonts.ptj_sm' => ['required', $ptjIn],
        ];

        foreach (self::JASAMU_FONT_KEYS as $k) {
            foreach (self::BREAKPOINTS as $bp) {
                $rules[$prefix."fonts.{$k}_{$bp}"] = ['sometimes', 'integer', 'min:10', 'max:200'];
            }
        }

        return $rules;
    }

    /**
     * Turn validated raw (int position values) input into the stored shape
     * (position values suffixed with 'px').
     *
     * @param  array{position: array<string, int|string>, fonts: array<string, int|string>}  $validated
     * @return array{position: array{mt_base: string, mt_sm: string, mt_md: string, translate_y: string}, fonts: array{name_base: int, name_sm: int, name_md: int, jawatan_base: int, jawatan_sm: int, jawatan_md: int, ptj_base: string, ptj_sm: string, tarikh_base: int, tarikh_sm: int, tarikh_md: int, bersara_base: int, bersara_sm: int, bersara_md: int, tempoh_base: int, tempoh_sm: int, tempoh_md: int}}
     */
    public function normalize(array $validated): array
    {
        $defaults = $this->defaults();

        $fonts = [
            'name_base' => (int) $validated['fonts']['name_base'],
            'name_sm' => (int) $validated['fonts']['name_sm'],
            'name_md' => (int) $validated['fonts']['name_md'],
            'jawatan_base' => (int) $validated['fonts']['jawatan_base'],
            'jawatan_sm' => (int) $validated['fonts']['jawatan_sm'],
            'jawatan_md' => (int) $validated['fonts']['jawatan_md'],
            'ptj_base' => (string) $validated['fonts']['ptj_base'],
            'ptj_sm' => (string) $validated['fonts']['ptj_sm'],
        ];

        foreach (self::JASAMU_FONT_KEYS as $k) {
            foreach (self::BREAKPOINTS as $bp) {
                $field = "{$k}_{$bp}";
                $fonts[$field] = (int) ($validated['fonts'][$field] ?? $defaults['fonts'][$field]);
            }
        }

        return [
            'position' => [
                'mt_base' => ((int) $validated['position']['mt_base']).'px',
                'mt_sm' => ((int) $validated['position']['mt_sm']).'px',
                'mt_md' => ((int) $validated['position']['mt_md']).'px',
                'translate_y' => ((int) $validated['position']['translate_y']).'px',
            ],
            'fonts' => $fonts,
        ];
    }

    /**
     * Merge arbitrary stored data (possibly missing/malformed) over the defaults,
     * returning the canonical shape.
     *
     * @return array{position: array{mt_base: string, mt_sm: string, mt_md: string, translate_y: string}, fonts: array{name_base: int, name_sm: int, name_md: int, jawatan_base: int, jawatan_sm: int, jawatan_md: int, ptj_base: string, ptj_sm: string, tarikh_base: int, tarikh_sm: int, tarikh_md: int, bersara_base: int, bersara_sm: int, bersara_md: int, tempoh_base: int, tempoh_sm: int, tempoh_md: int}}
     */
    public function resolve(mixed $stored): array
    {
        $defaults = $this->defaults();

        if (! is_array($stored)) {
            return $defaults;
        }

        $position = is_array($stored['position'] ?? null) ? $stored['position'] : [];
        $fonts = is_array($stored['fonts'] ?? null) ? $stored['fonts'] : [];

        $resolvedFonts = [
            'name_base' => (int) ($fonts['name_base'] ?? $defaults['fonts']['name_base']),
            'name_sm' => (int) ($fonts['name_sm'] ?? $defaults['fonts']['name_sm']),
            'name_md' => (int) ($fonts['name_md'] ?? $defaults['fonts']['name_md']),
            'jawatan_base' => (int) ($fonts['jawatan_base'] ?? $defaults['fonts']['jawatan_base']),
            'jawatan_sm' => (int) ($fonts['jawatan_sm'] ?? $defaults['fonts']['jawatan_sm']),
            'jawatan_md' => (int) ($fonts['jawatan_md'] ?? $defaults['fonts']['jawatan_md']),
            'ptj_base' => (string) ($fonts['ptj_base'] ?? $defaults['fonts']['ptj_base']),
            'ptj_sm' => (string) ($fonts['ptj_sm'] ?? $defaults['fonts']['ptj_sm']),
        ];

        foreach (self::JASAMU_FONT_KEYS as $k) {
            foreach (self::BREAKPOINTS as $bp) {
                $field = "{$k}_{$bp}";
                $resolvedFonts[$field] = (int) ($fonts[$field] ?? $defaults['fonts'][$field]);
            }
        }

        return [
            'position' => [
                'mt_base' => (string) ($position['mt_base'] ?? $defaults['position']['mt_base']),
                'mt_sm' => (string) ($position['mt_sm'] ?? $defaults['position']['mt_sm']),
                'mt_md' => (string) ($position['mt_md'] ?? $defaults['position']['mt_md']),
                'translate_y' => (string) ($position['translate_y'] ?? $defaults['position']['translate_y']),
            ],
            'fonts' => $resolvedFonts,
        ];
    }

    /**
     * Adds the derived pixel values for the PTJ Tailwind classes, used by the
     * presentation screen's inline CSS custom properties.
     *
     * @param  array{position: array{mt_base: string, mt_sm: string, mt_md: string, translate_y: string}, fonts: array{name_base: int, name_sm: int, name_md: int, jawatan_base: int, jawatan_sm: int, jawatan_md: int, ptj_base: string, ptj_sm: string}}  $config
     * @return array{position: array{mt_base: string, mt_sm: string, mt_md: string, translate_y: string}, fonts: array{name_base: int, name_sm: int, name_md: int, jawatan_base: int, jawatan_sm: int, jawatan_md: int, ptj_base: string, ptj_sm: string, ptj_base_px: int, ptj_sm_px: int}}
     */
    public function withPx(array $config): array
    {
        $config['fonts']['ptj_base_px'] = $this->ptjClassToPx($config['fonts']['ptj_base']);
        $config['fonts']['ptj_sm_px'] = $this->ptjClassToPx($config['fonts']['ptj_sm']);

        return $config;
    }

    /**
     * Flatten a canonical config into the field-id-keyed, px-stripped shape
     * the settings form (and its JS state) works with.
     *
     * @param  array{position: array{mt_base: string, mt_sm: string, mt_md: string, translate_y: string}, fonts: array{name_base: int, name_sm: int, name_md: int, jawatan_base: int, jawatan_sm: int, jawatan_md: int, ptj_base: string, ptj_sm: string, tarikh_base: int, tarikh_sm: int, tarikh_md: int, bersara_base: int, bersara_sm: int, bersara_md: int, tempoh_base: int, tempoh_sm: int, tempoh_md: int}}  $config
     * @return array<string, int|string>
     */
    public function toFormValues(array $config): array
    {
        $values = [
            'position_mt_base' => (int) rtrim($config['position']['mt_base'], 'px'),
            'position_mt_sm' => (int) rtrim($config['position']['mt_sm'], 'px'),
            'position_mt_md' => (int) rtrim($config['position']['mt_md'], 'px'),
            'position_translate_y' => (int) rtrim($config['position']['translate_y'], 'px'),
            'fonts_name_base' => (int) $config['fonts']['name_base'],
            'fonts_name_sm' => (int) $config['fonts']['name_sm'],
            'fonts_name_md' => (int) $config['fonts']['name_md'],
            'fonts_jawatan_base' => (int) $config['fonts']['jawatan_base'],
            'fonts_jawatan_sm' => (int) $config['fonts']['jawatan_sm'],
            'fonts_jawatan_md' => (int) $config['fonts']['jawatan_md'],
            'fonts_ptj_base' => (string) $config['fonts']['ptj_base'],
            'fonts_ptj_sm' => (string) $config['fonts']['ptj_sm'],
        ];

        foreach (self::JASAMU_FONT_KEYS as $k) {
            foreach (self::BREAKPOINTS as $bp) {
                $field = "{$k}_{$bp}";
                $values["fonts_{$field}"] = (int) $config['fonts'][$field];
            }
        }

        return $values;
    }

    /**
     * The currently live (in-effect) config, merged with defaults.
     *
     * @return array{position: array{mt_base: string, mt_sm: string, mt_md: string, translate_y: string}, fonts: array{name_base: int, name_sm: int, name_md: int, jawatan_base: int, jawatan_sm: int, jawatan_md: int, ptj_base: string, ptj_sm: string}}
     */
    public function live(): array
    {
        return $this->resolve($this->settings->get(self::LIVE_SETTINGS_KEY, []));
    }

    /**
     * @param  array{position: array{mt_base: string, mt_sm: string, mt_md: string, translate_y: string}, fonts: array{name_base: int, name_sm: int, name_md: int, jawatan_base: int, jawatan_sm: int, jawatan_md: int, ptj_base: string, ptj_sm: string}}  $config
     */
    public function saveLive(array $config): void
    {
        $this->settings->set(self::LIVE_SETTINGS_KEY, $config);
    }

    public function activeProfileId(): ?int
    {
        $value = $this->settings->get(self::ACTIVE_PROFILE_SETTINGS_KEY);

        if ($value === null || $value === '') {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    public function setActiveProfileId(?int $id): void
    {
        $this->settings->set(self::ACTIVE_PROFILE_SETTINGS_KEY, $id);
    }
}
