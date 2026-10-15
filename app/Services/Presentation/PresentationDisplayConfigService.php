<?php

declare(strict_types=1);

namespace App\Services\Presentation;

use App\Models\Pegawai;
use App\Models\PresentationFont;
use App\Services\EventModeService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Schema;

/**
 * Canonical shape + rules for the presentation display config used by
 * both the settings page (media::kawalan.presentation) and the presentation
 * screen (media::senarai.present). Single source of truth for defaults,
 * validation, normalization and merging so both places stay in sync.
 *
 * @phpstan-type PositionConfig array{mt_base: string, mt_sm: string, mt_md: string, translate_y: string}
 * @phpstan-type FontsConfig array{name_base: int, name_sm: int, name_md: int, jawatan_base: int, jawatan_sm: int, jawatan_md: int, ptj_base: string, ptj_sm: string, tarikh_base: int, tarikh_sm: int, tarikh_md: int, bersara_base: int, bersara_sm: int, bersara_md: int, tempoh_base: int, tempoh_sm: int, tempoh_md: int}
 * @phpstan-type FieldConfig array{key: string, visible: bool, label: bool}
 * @phpstan-type FieldsConfig array{apc: list<FieldConfig>, jasamu: list<FieldConfig>}
 * @phpstan-type TypographyPart array{font_id: int|null, weight: int, italic: bool, tracking: string}
 * @phpstan-type TypographyConfig array{name: TypographyPart, jawatan: TypographyPart, ptj: TypographyPart, tarikh: TypographyPart, bersara: TypographyPart, tempoh: TypographyPart}
 * @phpstan-type DisplayConfig array{position: PositionConfig, fonts: FontsConfig, fields: FieldsConfig, hide_empty: bool, typography: TypographyConfig}
 */
final class PresentationDisplayConfigService
{
    public const LIVE_SETTINGS_KEY = 'presentation_display_config';

    public const ACTIVE_PROFILE_SETTINGS_KEY = 'presentation_active_profile_id';

    /**
     * Data lines selectable under the officer name. Name itself is always shown.
     *
     * @var list<string>
     */
    public const FIELD_KEYS = ['jawatan', 'ptj', 'tarikh', 'bersara', 'tempoh'];

    /**
     * Event modes that each keep their own field selection.
     *
     * @var list<string>
     */
    public const MODES = [EventModeService::MODE_APC, EventModeService::MODE_JASAMU];

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

    /** Fields that show a "Label: value" prefix by default. */
    private const LABELLED_KEYS = ['tarikh', 'bersara', 'tempoh'];

    private const DEFAULT_HIDE_EMPTY = true;

    /**
     * Slide parts with their own font style (name + every selectable field).
     *
     * @var list<string>
     */
    public const TYPO_PARTS = ['name', 'jawatan', 'ptj', 'tarikh', 'bersara', 'tempoh'];

    /** @var list<int> */
    public const WEIGHTS = [400, 500, 600, 700, 800];

    /**
     * Letter-spacing presets => CSS value.
     *
     * @var array<string, string>
     */
    public const TRACKING = ['tight' => '-0.02em', 'normal' => '0em', 'wide' => '0.04em', 'wider' => '0.08em'];

    public const DEFAULT_FONT_STACK = "'Inter Variable', 'Inter', ui-sans-serif, system-ui, sans-serif";

    /** @var array<int, true>|null */
    private ?array $fontIds = null;

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
     * Registry of selectable data lines: label (also used as the "Label:" prefix),
     * icon and a sample value for previews without real data.
     *
     * @return array<string, array{label: string, icon: string, sample: string}>
     */
    public function fieldDefinitions(): array
    {
        return [
            'jawatan' => ['label' => __('Jawatan'), 'icon' => 'ri-briefcase-line', 'sample' => __('Pegawai Teknologi Maklumat')],
            'ptj' => ['label' => __('PTJ'), 'icon' => 'ri-building-line', 'sample' => __('PTJ Contoh')],
            'tarikh' => ['label' => __('Tarikh bersara'), 'icon' => 'ri-calendar-check-line', 'sample' => '31/12/2026'],
            'bersara' => ['label' => __('Bersara'), 'icon' => 'ri-user-heart-line', 'sample' => __('Wajib')],
            'tempoh' => ['label' => __('Tempoh berkhidmat'), 'icon' => 'ri-history-line', 'sample' => __('25 tahun')],
        ];
    }

    /**
     * Default field selection for one mode (reproduces the original hard-coded
     * layout: APC = jawatan + PTJ, Jasamu = jawatan + persaraan lines).
     *
     * @return list<FieldConfig>
     */
    public function defaultFields(string $mode): array
    {
        $isJasamu = $mode === EventModeService::MODE_JASAMU;

        $visible = $isJasamu ? ['jawatan', 'tarikh', 'bersara', 'tempoh'] : ['jawatan', 'ptj'];
        $order = $isJasamu
            ? ['jawatan', 'tarikh', 'bersara', 'tempoh', 'ptj']
            : ['jawatan', 'ptj', 'tarikh', 'bersara', 'tempoh'];

        return array_map(
            static fn (string $key): array => [
                'key' => $key,
                'visible' => in_array($key, $visible, true),
                'label' => in_array($key, self::LABELLED_KEYS, true),
            ],
            $order,
        );
    }

    /**
     * @return FieldsConfig
     */
    public function fieldDefaults(): array
    {
        return [
            EventModeService::MODE_APC => $this->defaultFields(EventModeService::MODE_APC),
            EventModeService::MODE_JASAMU => $this->defaultFields(EventModeService::MODE_JASAMU),
        ];
    }

    /**
     * @return DisplayConfig
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
            'fields' => $this->fieldDefaults(),
            'hide_empty' => self::DEFAULT_HIDE_EMPTY,
            'typography' => $this->typographyDefaults(),
        ];
    }

    /**
     * Font style per slide part. Weights match the original CSS (name 700, others 500).
     *
     * @return TypographyConfig
     */
    public function typographyDefaults(): array
    {
        $typography = [];

        foreach (self::TYPO_PARTS as $part) {
            $typography[$part] = [
                'font_id' => null,
                'weight' => $part === 'name' ? 700 : 500,
                'italic' => false,
                'tracking' => 'normal',
            ];
        }

        return $typography;
    }

    /**
     * Labels for the font style rows (name + field registry labels).
     *
     * @return array<string, array{label: string, icon: string}>
     */
    public function typographyParts(): array
    {
        $parts = ['name' => ['label' => __('Nama Pegawai'), 'icon' => 'ri-user-star-line']];

        foreach ($this->fieldDefinitions() as $key => $definition) {
            $parts[$key] = ['label' => $definition['label'], 'icon' => $definition['icon']];
        }

        return $parts;
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

        // Optional so older clients/profiles that predate font styles stay valid.
        foreach (self::TYPO_PARTS as $part) {
            $rules[$prefix."typography.{$part}.font_id"] = ['sometimes', 'nullable', 'integer', 'exists:presentation_fonts,id'];
            $rules[$prefix."typography.{$part}.weight"] = ['sometimes', 'integer', 'in:'.implode(',', self::WEIGHTS)];
            $rules[$prefix."typography.{$part}.italic"] = ['sometimes', 'boolean'];
            $rules[$prefix."typography.{$part}.tracking"] = ['sometimes', 'string', 'in:'.implode(',', array_keys(self::TRACKING))];
        }

        // Optional so older clients/profiles that predate field selection stay valid.
        return array_merge($rules, $this->fieldRules($prefix));
    }

    /**
     * Rules for the per-mode field selection + hide-empty flag. Optional inside
     * the full config payload; required when saved on their own.
     *
     * @return array<string, array<int, string>>
     */
    public function fieldRules(string $prefix = '', bool $required = false): array
    {
        $presence = $required ? 'required' : 'sometimes';
        $keyIn = 'in:'.implode(',', self::FIELD_KEYS);

        $rules = [$prefix.'fields' => [$presence, 'array']];

        foreach (self::MODES as $mode) {
            $rules[$prefix."fields.{$mode}"] = [$presence, 'array', 'max:'.count(self::FIELD_KEYS)];
            $rules[$prefix."fields.{$mode}.*.key"] = ['required', 'string', $keyIn];
            $rules[$prefix."fields.{$mode}.*.visible"] = ['required', 'boolean'];
            $rules[$prefix."fields.{$mode}.*.label"] = ['required', 'boolean'];
        }

        $rules[$prefix.'hide_empty'] = [$presence, 'boolean'];

        return $rules;
    }

    /**
     * Turn validated raw (int position values) input into the stored shape
     * (position values suffixed with 'px').
     *
     * @param  array{position: array<string, int|string>, fonts: array<string, int|string>, fields?: mixed, hide_empty?: mixed, typography?: mixed}  $validated
     * @return DisplayConfig
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
            'fields' => $this->normalizeFields($validated['fields'] ?? null),
            'hide_empty' => $this->normalizeHideEmpty($validated['hide_empty'] ?? null),
            'typography' => $this->normalizeTypography($validated['typography'] ?? null),
        ];
    }

    /**
     * Merge arbitrary stored data (possibly missing/malformed) over the defaults,
     * returning the canonical shape.
     *
     * @return DisplayConfig
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
            'fields' => $this->normalizeFields($stored['fields'] ?? null),
            'hide_empty' => $this->normalizeHideEmpty($stored['hide_empty'] ?? null),
            'typography' => $this->normalizeTypography($stored['typography'] ?? null),
        ];
    }

    /**
     * Replace only the field selection + hide-empty flag on an otherwise
     * canonical config (used by the auto-saving "Data Dipaparkan" section).
     *
     * @param  DisplayConfig  $config
     * @return DisplayConfig
     */
    public function withFields(array $config, mixed $fields, mixed $hideEmpty): array
    {
        $config['fields'] = $this->normalizeFields($fields);
        $config['hide_empty'] = $this->normalizeHideEmpty($hideEmpty);

        return $config;
    }

    /**
     * Adds the derived pixel values for the PTJ Tailwind classes, used by the
     * presentation screen's inline CSS custom properties.
     *
     * @param  DisplayConfig  $config
     * @return array<string, mixed>
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
     * @param  DisplayConfig  $config
     * @return array<string, int|string|bool|list<FieldConfig>>
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

        foreach (self::MODES as $mode) {
            $values["fields_{$mode}"] = $config['fields'][$mode];
        }

        $values['hide_empty'] = (bool) $config['hide_empty'];

        foreach (self::TYPO_PARTS as $part) {
            $style = $config['typography'][$part];
            $values["typo_{$part}_font"] = $style['font_id'];
            $values["typo_{$part}_weight"] = $style['weight'];
            $values["typo_{$part}_italic"] = $style['italic'];
            $values["typo_{$part}_tracking"] = $style['tracking'];
        }

        return $values;
    }

    /**
     * CSS custom properties per slide part: --officer-{part}-font-family / -font-weight /
     * -font-style / -letter-spacing. Values are built from ids and constants only, so
     * they are safe to print raw inside a <style> block.
     *
     * @param  DisplayConfig  $config
     * @return array<string, string>
     */
    public function typographyVars(array $config): array
    {
        $vars = [];

        foreach (self::TYPO_PARTS as $part) {
            $style = $config['typography'][$part];
            $vars["--officer-{$part}-font-family"] = $style['font_id'] === null
                ? self::DEFAULT_FONT_STACK
                : '"apcfont-'.(int) $style['font_id'].'", '.self::DEFAULT_FONT_STACK;
            $vars["--officer-{$part}-font-weight"] = (string) (int) $style['weight'];
            $vars["--officer-{$part}-font-style"] = $style['italic'] ? 'italic' : 'normal';
            $vars["--officer-{$part}-letter-spacing"] = self::TRACKING[$style['tracking']] ?? self::TRACKING['normal'];
        }

        return $vars;
    }

    /**
     * Font ids referenced by a config (used to load only the @font-face rules needed).
     *
     * @param  DisplayConfig  $config
     * @return list<int>
     */
    public function usedFontIds(array $config): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn (array $style) => $style['font_id'], $config['typography']),
            static fn ($id) => $id !== null,
        )));
    }

    /**
     * Parts of a config that use the given font.
     *
     * @param  DisplayConfig  $config
     * @return list<string>
     */
    public function partsUsingFont(array $config, int $fontId): array
    {
        return array_keys(array_filter(
            $config['typography'],
            static fn (array $style) => $style['font_id'] === $fontId,
        ));
    }

    /**
     * Forget the memoised list of existing font ids (after a font is added or removed).
     */
    public function forgetFontIds(): void
    {
        $this->fontIds = null;
    }

    /**
     * Visible data lines (in display order) for one event mode, each with the
     * "Label" prefix to render before the value (null = value only).
     *
     * @param  DisplayConfig  $config
     * @return list<array{key: string, prefix: string|null}>
     */
    public function visibleFields(array $config, string $mode): array
    {
        $definitions = $this->fieldDefinitions();
        $lines = [];

        foreach ($config['fields'][$mode] ?? [] as $field) {
            if (! $field['visible']) {
                continue;
            }

            $lines[] = [
                'key' => $field['key'],
                'prefix' => $field['label'] ? $definitions[$field['key']]['label'] : null,
            ];
        }

        return $lines;
    }

    /**
     * Raw display value per selectable field for an officer (null when missing).
     * Expects jawatan, ptj and bersara relations to be loaded by the caller.
     *
     * @return array<string, string|null>
     */
    public function fieldValues(Pegawai $pegawai): array
    {
        $clean = static function (mixed $value): ?string {
            if ($value === null) {
                return null;
            }

            $value = trim((string) $value);

            return $value === '' ? null : $value;
        };

        return [
            'jawatan' => $clean($pegawai->jawatan?->desc_jawatan),
            'ptj' => $clean($pegawai->ptj?->nama_ptj),
            'tarikh' => $clean($pegawai->tarikh_bersara?->format('d/m/Y')),
            'bersara' => $clean($pegawai->bersara?->jenis_bersara),
            'tempoh' => $clean($pegawai->tempoh_berkhidmat),
        ];
    }

    /**
     * The currently live (in-effect) config, merged with defaults.
     *
     * @return DisplayConfig
     */
    public function live(): array
    {
        return $this->resolve($this->settings->get(self::LIVE_SETTINGS_KEY, []));
    }

    /**
     * @param  DisplayConfig  $config
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

    /**
     * Canonicalise a per-mode field selection: drop unknown/duplicate keys, cast
     * flags to bool, and append any registry field missing from a list as hidden
     * so adding a field later never breaks stored configs. A mode that is absent
     * or malformed falls back to its defaults.
     *
     * @return FieldsConfig
     */
    private function normalizeFields(mixed $input): array
    {
        $input = is_array($input) ? $input : [];
        $result = [];

        foreach (self::MODES as $mode) {
            $defaults = $this->defaultFields($mode);
            $list = $input[$mode] ?? null;

            if (! is_array($list)) {
                $result[$mode] = $defaults;

                continue;
            }

            $defaultLabels = array_column($defaults, 'label', 'key');
            $seen = [];
            $normalized = [];

            foreach ($list as $item) {
                $key = is_array($item) ? ($item['key'] ?? null) : null;

                if (! is_string($key) || ! in_array($key, self::FIELD_KEYS, true) || isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $normalized[] = [
                    'key' => $key,
                    'visible' => filter_var($item['visible'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'label' => filter_var($item['label'] ?? $defaultLabels[$key], FILTER_VALIDATE_BOOLEAN),
                ];
            }

            foreach ($defaults as $default) {
                if (! isset($seen[$default['key']])) {
                    $normalized[] = ['key' => $default['key'], 'visible' => false, 'label' => $default['label']];
                }
            }

            $result[$mode] = $normalized;
        }

        return $result;
    }

    private function normalizeHideEmpty(mixed $value): bool
    {
        return $value === null ? self::DEFAULT_HIDE_EMPTY : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Canonical font style per part in fixed key order; unknown values and fonts
     * that no longer exist fall back to the defaults.
     *
     * @return TypographyConfig
     */
    private function normalizeTypography(mixed $input): array
    {
        $input = is_array($input) ? $input : [];
        $defaults = $this->typographyDefaults();
        $existing = $this->existingFontIds();
        $result = [];

        foreach (self::TYPO_PARTS as $part) {
            $style = is_array($input[$part] ?? null) ? $input[$part] : [];
            $fontId = filter_var($style['font_id'] ?? null, FILTER_VALIDATE_INT);
            $weight = filter_var($style['weight'] ?? null, FILTER_VALIDATE_INT);
            $tracking = $style['tracking'] ?? null;

            $result[$part] = [
                'font_id' => $fontId !== false && isset($existing[$fontId]) ? $fontId : null,
                'weight' => in_array($weight, self::WEIGHTS, true) ? $weight : $defaults[$part]['weight'],
                'italic' => array_key_exists('italic', $style)
                    ? filter_var($style['italic'], FILTER_VALIDATE_BOOLEAN)
                    : $defaults[$part]['italic'],
                'tracking' => is_string($tracking) && isset(self::TRACKING[$tracking]) ? $tracking : $defaults[$part]['tracking'],
            ];
        }

        return $result;
    }

    /**
     * @return array<int, true>
     */
    private function existingFontIds(): array
    {
        if ($this->fontIds === null) {
            $this->fontIds = Schema::hasTable('presentation_fonts')
                ? PresentationFont::query()->pluck('id')->mapWithKeys(fn ($id) => [(int) $id => true])->all()
                : [];
        }

        return $this->fontIds;
    }
}
