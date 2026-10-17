<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Models\Backdrop;
use App\Services\EventModeService;
use App\Services\Kehadiran\PaparanViewDataBuilder;
use App\Services\Kehadiran\SenaraiQueueService;
use App\Services\Presentation\PresentationDisplayConfigService;
use App\Services\Presentation\PresentationFontService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SenaraiController extends Controller
{
    public function __construct(
        private readonly PaparanViewDataBuilder $paparanViewData,
        private readonly PresentationDisplayConfigService $displayConfig,
        private readonly EventModeService $eventMode,
        private readonly PresentationFontService $fonts,
        private readonly SenaraiQueueService $queue,
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
        $sesiId = $viewData['selectedSesi']?->id;
        $displaySettings = $this->displayConfig->live();

        return view('media::senarai.present', [
            'pegawais' => $pegawais,
            'officerSlides' => $this->officerSlides($pegawais),
            'listVersion' => $this->queue->version($sesiId),
            'visibleFields' => $this->displayConfig->visibleFields($displaySettings, $this->eventMode->current()),
            'hideEmpty' => $displaySettings['hide_empty'],
            'backdrop' => $backdrop,
            'sesiId' => $sesiId,
            'displaySettings' => $this->displayConfig->withPx($displaySettings),
            // Only the fonts this screen actually uses are loaded.
            'fontFaceCss' => $this->fonts->fontFaceCss(
                $this->fonts->fontsByIds($this->displayConfig->usedFontIds($displaySettings))
            ),
            'typographyVars' => $this->displayConfig->typographyVars($displaySettings),
        ]);
    }

    /**
     * Live queue for a running presentation: late arrivals show up without a reload.
     */
    public function slides(Request $request): JsonResponse
    {
        $sesiId = $request->filled('sesi_id') ? $request->integer('sesi_id') : null;
        $version = $this->queue->version($sesiId);
        $payload = ['changed' => $request->query('version') !== $version, 'version' => $version];

        if ($payload['changed']) {
            $payload['officers'] = $this->officerSlides($this->queue->officers($sesiId));
        }

        return response()->json($payload)->header('Cache-Control', 'no-store, private');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function officerSlides(Collection $pegawais): Collection
    {
        return $pegawais->map(fn ($o) => [
            'id' => $o->id,
            'nama' => $o->nama,
            ...$this->displayConfig->fieldValues($o),
        ])->values();
    }
}
