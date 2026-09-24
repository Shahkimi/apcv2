<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media\Kawalan;

use App\Http\Controllers\Controller;
use App\Models\Backdrop;
use App\Models\PresentationProfile;
use App\Services\Presentation\PresentationDisplayConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PresentationSettingsController extends Controller
{
    public function __construct(
        private readonly PresentationDisplayConfigService $displayConfig,
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
        ]);
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate(array_merge(
            $this->displayConfig->rules(),
            ['profile_id' => ['nullable', 'integer', 'exists:presentation_profiles,id']],
        ));

        /** @var array{position: array<string, int|string>, fonts: array<string, int|string>} $configInput */
        $configInput = [
            'position' => $validated['position'],
            'fonts' => $validated['fonts'],
        ];
        $config = $this->displayConfig->normalize($configInput);

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
}
