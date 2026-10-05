<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SesiMajlis;
use App\Services\Dashboard\DashboardStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

abstract class AbstractDashboardController extends Controller
{
    public function __construct(protected readonly DashboardStatsService $dashboardStats) {}

    /** Route prefix and view namespace: admin, media or user. */
    abstract protected function role(): string;

    abstract protected function title(): string;

    abstract protected function subtitle(): string;

    /**
     * @return array{stats: array, charts: array, tables: array, status: array}
     */
    abstract protected function payload(?int $sesiId): array;

    /**
     * Static page structure: stat cards, chart panels, tables, status rows and quick actions.
     *
     * @return array{hero: array, cards: list<array>, charts: list<array>, tables: list<array>, statusRows: list<array>, actions: list<array>}
     */
    abstract protected function layout(): array;

    public function index(Request $request): View
    {
        $sesiId = $this->dashboardStats->resolveSesiId($request);

        return view($this->role().'::dashboard', [
            'title' => $this->title(),
            'subtitle' => $this->subtitle(),
            'role' => $this->role(),
            'layout' => $this->layout(),
            'payload' => $this->payload($sesiId),
            'allSesis' => SesiMajlis::query()->select(['id', 'sesi'])->orderBy('id')->get(),
            'selectedSesiId' => $sesiId,
            'pollUrl' => route($this->role().'.dashboard.stats'),
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $sesiId = $this->dashboardStats->resolveSesiId($request);

        return response()->json($this->payload($sesiId) + [
            'sesi_id' => $sesiId,
            'updated_at' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-store, private');
    }
}
