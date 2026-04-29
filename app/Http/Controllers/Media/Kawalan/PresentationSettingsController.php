<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media\Kawalan;

use App\Http\Controllers\Controller;
use App\Models\Backdrop;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PresentationSettingsController extends Controller
{
    private const SETTINGS_KEY = 'presentation_display_config';

    public function __construct(
        private readonly SettingsService $settings
    ) {}

    public function index(): View
    {
        $backdrop = Backdrop::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->first();

        return view('media::kawalan.presentation', [
            'config' => $this->resolvedConfig(),
            'ptjFontOptions' => $this->ptjFontOptions(),
            'backdrop' => $backdrop,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'position.mt_base' => ['required', 'integer', 'min:0', 'max:2000'],
            'position.mt_sm' => ['required', 'integer', 'min:0', 'max:2000'],
            'position.mt_md' => ['required', 'integer', 'min:0', 'max:2000'],
            'position.translate_y' => ['required', 'integer', 'min:-1000', 'max:1000'],
            'fonts.name_base' => ['required', 'integer', 'min:10', 'max:200'],
            'fonts.name_sm' => ['required', 'integer', 'min:10', 'max:200'],
            'fonts.name_md' => ['required', 'integer', 'min:10', 'max:200'],
            'fonts.jawatan_base' => ['required', 'integer', 'min:10', 'max:200'],
            'fonts.jawatan_sm' => ['required', 'integer', 'min:10', 'max:200'],
            'fonts.jawatan_md' => ['required', 'integer', 'min:10', 'max:200'],
            'fonts.ptj_base' => ['required', 'in:'.implode(',', $this->ptjFontOptions())],
            'fonts.ptj_sm' => ['required', 'in:'.implode(',', $this->ptjFontOptions())],
        ]);

        /** @var array{position: array<string, int|string>, fonts: array<string, int|string>} $validated */
        $validated['position'] = [
            'mt_base' => (int) $validated['position']['mt_base'].'px',
            'mt_sm' => (int) $validated['position']['mt_sm'].'px',
            'mt_md' => (int) $validated['position']['mt_md'].'px',
            'translate_y' => (int) $validated['position']['translate_y'].'px',
        ];

        $this->settings->set(self::SETTINGS_KEY, $validated);

        return back()->with('status', __('Tetapan paparan berjaya disimpan.'));
    }

    /**
     * @return array{position: array{mt_base: string, mt_sm: string, mt_md: string, translate_y: string}, fonts: array{name_base: int, name_sm: int, name_md: int, jawatan_base: int, jawatan_sm: int, jawatan_md: int, ptj_base: string, ptj_sm: string}}
     */
    private function resolvedConfig(): array
    {
        $stored = $this->settings->get(self::SETTINGS_KEY, []);
        $defaults = $this->defaultConfig();

        if (! is_array($stored)) {
            return $defaults;
        }

        $position = is_array($stored['position'] ?? null) ? $stored['position'] : [];
        $fonts = is_array($stored['fonts'] ?? null) ? $stored['fonts'] : [];

        return [
            'position' => [
                'mt_base' => (string) ($position['mt_base'] ?? $defaults['position']['mt_base']),
                'mt_sm' => (string) ($position['mt_sm'] ?? $defaults['position']['mt_sm']),
                'mt_md' => (string) ($position['mt_md'] ?? $defaults['position']['mt_md']),
                'translate_y' => (string) ($position['translate_y'] ?? $defaults['position']['translate_y']),
            ],
            'fonts' => [
                'name_base' => (int) ($fonts['name_base'] ?? $defaults['fonts']['name_base']),
                'name_sm' => (int) ($fonts['name_sm'] ?? $defaults['fonts']['name_sm']),
                'name_md' => (int) ($fonts['name_md'] ?? $defaults['fonts']['name_md']),
                'jawatan_base' => (int) ($fonts['jawatan_base'] ?? $defaults['fonts']['jawatan_base']),
                'jawatan_sm' => (int) ($fonts['jawatan_sm'] ?? $defaults['fonts']['jawatan_sm']),
                'jawatan_md' => (int) ($fonts['jawatan_md'] ?? $defaults['fonts']['jawatan_md']),
                'ptj_base' => (string) ($fonts['ptj_base'] ?? $defaults['fonts']['ptj_base']),
                'ptj_sm' => (string) ($fonts['ptj_sm'] ?? $defaults['fonts']['ptj_sm']),
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function ptjFontOptions(): array
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

    /**
     * @return array{position: array{mt_base: string, mt_sm: string, mt_md: string, translate_y: string}, fonts: array{name_base: int, name_sm: int, name_md: int, jawatan_base: int, jawatan_sm: int, jawatan_md: int, ptj_base: string, ptj_sm: string}}
     */
    private function defaultConfig(): array
    {
        return [
            'position' => [
                'mt_base' => '230px',
                'mt_sm' => '270px',
                'mt_md' => '320px',
                'translate_y' => '-72px',
            ],
            'fonts' => [
                'name_base' => 36,
                'name_sm' => 44,
                'name_md' => 52,
                'jawatan_base' => 30,
                'jawatan_sm' => 38,
                'jawatan_md' => 46,
                'ptj_base' => 'text-2xl',
                'ptj_sm' => 'text-4xl',
            ],
        ];
    }
}
