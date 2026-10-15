<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media\Kawalan;

use App\Http\Controllers\Controller;
use App\Models\Backdrop;
use App\Models\Pegawai;
use App\Models\PresentationFont;
use App\Models\PresentationProfile;
use App\Services\EventModeService;
use App\Services\Presentation\PresentationDisplayConfigService;
use App\Services\Presentation\PresentationFontService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PresentationSettingsController extends Controller
{
    public function __construct(
        private readonly PresentationDisplayConfigService $displayConfig,
        private readonly EventModeService $eventMode,
        private readonly PresentationFontService $fonts,
    ) {}

    public function index(): View
    {
        $backdrop = Backdrop::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->first();

        $config = $this->displayConfig->live();

        $profiles = PresentationProfile::query()
            ->orderBy('name')
            ->get()
            ->map(fn (PresentationProfile $profile) => [
                'id' => $profile->id,
                'name' => $profile->name,
                'updated_at' => $profile->updated_at?->toIso8601String() ?? '',
                'updated_human' => $profile->updated_at?->diffForHumans() ?? '',
                'form_values' => $this->displayConfig->toFormValues($this->displayConfig->resolve($profile->config)),
            ])
            ->values();

        $libraryFonts = PresentationFont::query()->orderBy('name')->get();

        return view('media::kawalan.presentation', [
            'config' => $config,
            'formValues' => $this->displayConfig->toFormValues($config),
            'ptjFontOptions' => $this->displayConfig->ptjFontOptions(),
            'ptjFontPx' => collect($this->displayConfig->ptjFontOptions())
                ->mapWithKeys(fn (string $class) => [$class => $this->displayConfig->ptjClassToPx($class)])
                ->all(),
            'profiles' => $profiles,
            'activeProfileId' => $this->displayConfig->activeProfileId(),
            'maxProfiles' => PresentationProfile::MAX_PROFILES,
            'backdrop' => $backdrop,
            'fieldDefs' => $this->displayConfig->fieldDefinitions(),
            'fieldDefaults' => $this->displayConfig->fieldDefaults(),
            'currentMode' => $this->eventMode->current(),
            'previewOfficer' => $this->previewOfficer(),
            'libraryFonts' => $libraryFonts
                ->map(fn (PresentationFont $font) => ['id' => $font->id, 'name' => $font->name, 'family' => $font->cssFamily()])
                ->values()
                ->all(),
            'fontFaceCss' => $this->fonts->fontFaceCss($libraryFonts),
            'typoParts' => $this->displayConfig->typographyParts(),
            'weightOptions' => [
                400 => __('Biasa'),
                500 => __('Sederhana'),
                600 => __('Separa tebal'),
                700 => __('Tebal'),
                800 => __('Sangat tebal'),
            ],
            'trackingOptions' => [
                'tight' => __('Rapat'),
                'normal' => __('Biasa'),
                'wide' => __('Renggang'),
                'wider' => __('Sangat renggang'),
            ],
            'trackingCss' => PresentationDisplayConfigService::TRACKING,
            'defaultFontStack' => PresentationDisplayConfigService::DEFAULT_FONT_STACK,
        ]);
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate(array_merge(
            $this->displayConfig->rules(),
            ['profile_id' => ['nullable', 'integer', 'exists:presentation_profiles,id']],
        ));

        $config = $this->displayConfig->normalize([
            'position' => $validated['position'],
            'fonts' => $validated['fonts'],
            'fields' => $validated['fields'] ?? null,
            'hide_empty' => $validated['hide_empty'] ?? null,
            'typography' => $validated['typography'] ?? null,
        ]);

        $this->displayConfig->saveLive($config);
        $this->displayConfig->setActiveProfileId(
            isset($validated['profile_id']) && $validated['profile_id'] !== null
                ? (int) $validated['profile_id']
                : null
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Tetapan paparan berjaya disimpan.'),
                'form_values' => $this->displayConfig->toFormValues($config),
                'active_profile_id' => $this->displayConfig->activeProfileId(),
            ]);
        }

        return back()->with('status', __('Tetapan paparan berjaya disimpan.'));
    }

    /**
     * Auto-save endpoint for the "Data Dipaparkan" section: replaces only the
     * field selection on the live config, keeping saved position/font values.
     */
    public function updateFields(Request $request): JsonResponse
    {
        $validated = $request->validate($this->displayConfig->fieldRules(required: true));

        $config = $this->displayConfig->withFields(
            $this->displayConfig->live(),
            $validated['fields'],
            $validated['hide_empty'],
        );

        $this->displayConfig->saveLive($config);
        $this->displayConfig->setActiveProfileId($this->matchingProfileId($config));

        return response()->json([
            'success' => true,
            'message' => __('Pilihan data dikemas kini.'),
            'form_values' => $this->displayConfig->toFormValues($config),
            'active_profile_id' => $this->displayConfig->activeProfileId(),
        ]);
    }

    /**
     * Keep the active-profile marker honest after a partial update: stay on the
     * current profile if it still matches the live config, else any matching one.
     *
     * @param  array<string, mixed>  $config
     */
    private function matchingProfileId(array $config): ?int
    {
        $activeId = $this->displayConfig->activeProfileId();

        $matching = PresentationProfile::query()
            ->orderBy('name')
            ->get()
            ->filter(fn (PresentationProfile $profile) => $this->displayConfig->resolve($profile->config) === $config);

        return ($matching->firstWhere('id', $activeId) ?? $matching->first())?->id;
    }

    /**
     * A real officer (latest attendee, else any) used to preview the screen with
     * actual data. Null when there are no officers yet — the page then falls back
     * to the registry's sample values.
     *
     * @return array{nama: string, values: array<string, string|null>}|null
     */
    private function previewOfficer(): ?array
    {
        $relations = ['jawatan', 'ptj', 'bersara'];

        $pegawai = Pegawai::query()
            ->with($relations)
            ->where('is_attend', true)
            ->latest('hadir_at')
            ->first()
            ?? Pegawai::query()->with($relations)->orderBy('id')->first();

        if ($pegawai === null) {
            return null;
        }

        return [
            'nama' => (string) $pegawai->nama,
            'values' => $this->displayConfig->fieldValues($pegawai),
        ];
    }
}
