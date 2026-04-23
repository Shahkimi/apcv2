<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Services\Kehadiran\PaparanViewDataBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaparanController extends Controller
{
    public function __construct(
        private readonly PaparanViewDataBuilder $paparanViewData,
    ) {}

    public function index(Request $request): View
    {
        return view('media::paparan.index', $this->paparanViewData->buildForRequest($request));
    }
}
