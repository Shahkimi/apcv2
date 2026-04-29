<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Models\Backdrop;
use App\Services\Kehadiran\PaparanViewDataBuilder;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SenaraiController extends Controller
{
    public function __construct(
        private readonly PaparanViewDataBuilder $paparanViewData,
        private readonly SettingsService $settings,
    ) {}

    public function index(Request $request): View
    {
        $viewData = $this->paparanViewData->buildForRequest($request);

        return view('media::senarai.index', [
            'allSesis' => $viewData['allSesis'],
            'selectedSesi' => $viewData['selectedSesi'],
        ]);
    }

    public function present(Request $request): View
    {
        $viewData = $this->paparanViewData->buildForRequest($request);

        $backdrop = Backdrop::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->first();

        $pegawais = $viewData['pegawais'];

        return view('media::senarai.present', [
            'pegawais' => $pegawais,
            'officerSlides' => $pegawais->map(static fn ($o) => [
                'id' => $o->id,
                'nama' => $o->nama,
                'jawatan' => $o->jawatan?->desc_jawatan ?? '—',
                'ptj' => $o->ptj?->nama_ptj ?? '—',
            ])->values(),
            'backdrop' => $backdrop,
            'sesiId' => $viewData['selectedSesi']?->id,
            'displaySettings' => $this->resolvedDisplaySettings(),
        ]);
    }

    /**
     * @return array{
     *   position: array{mt_base: string, mt_sm: string, mt_md: string, translate_y: string},
     *   fonts: array{name_base: int, name_sm: int, name_md: int, jawatan_base: int, jawatan_sm: int, jawatan_md: int, ptj_base: string, ptj_sm: string, ptj_base_px: int, ptj_sm_px: int}
     * }
     */
    private function resolvedDisplaySettings(): array
    {
        $stored = $this->settings->get('presentation_display_config', []);
        $defaults = [
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
                'ptj_base_px' => 24,
                'ptj_sm_px' => 36,
            ],
        ];

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
                'ptj_base' => $ptjBase = (string) ($fonts['ptj_base'] ?? $defaults['fonts']['ptj_base']),
                'ptj_sm' => $ptjSm = (string) ($fonts['ptj_sm'] ?? $defaults['fonts']['ptj_sm']),
                'ptj_base_px' => $this->ptjClassToPx($ptjBase),
                'ptj_sm_px' => $this->ptjClassToPx($ptjSm),
            ],
        ];
    }

    private function ptjClassToPx(string $class): int
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
}
