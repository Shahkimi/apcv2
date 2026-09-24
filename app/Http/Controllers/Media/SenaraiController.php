<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Models\Backdrop;
use App\Services\Kehadiran\PaparanViewDataBuilder;
use App\Services\Presentation\PresentationDisplayConfigService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SenaraiController extends Controller
{
    public function __construct(
        private readonly PaparanViewDataBuilder $paparanViewData,
        private readonly PresentationDisplayConfigService $displayConfig,
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
            'displaySettings' => $this->displayConfig->withPx($this->displayConfig->live()),
        ]);
    }
}
