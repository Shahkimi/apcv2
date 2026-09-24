<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media\Kawalan;

use App\Http\Controllers\Controller;
use App\Models\PresentationProfile;
use App\Services\Presentation\PresentationDisplayConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresentationProfileController extends Controller
{
    public function __construct(
        private readonly PresentationDisplayConfigService $displayConfig,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->listPayload());
    }

    public function store(Request $request): JsonResponse
    {
        if (PresentationProfile::query()->count() >= PresentationProfile::MAX_PROFILES) {
            return response()->json([
                'success' => false,
                'message' => __('Had :max profil dicapai. Padam atau kemas kini profil sedia ada.', [
                    'max' => PresentationProfile::MAX_PROFILES,
                ]),
            ], 422);
        }

        $validated = $request->validate(array_merge(
            [
                'name' => ['required', 'string', 'max:60', 'unique:presentation_profiles,name'],
            ],
            $this->displayConfig->rules('config.'),
        ));

        $profile = PresentationProfile::query()->create([
            'name' => trim((string) $validated['name']),
            'config' => $this->displayConfig->normalize($validated['config']),
        ]);

        return response()->json(array_merge([
            'success' => true,
            'message' => __('Profil ":name" disimpan.', ['name' => $profile->name]),
            'profile' => $this->profilePayload($profile),
        ], $this->listPayload()));
    }

    public function update(Request $request, PresentationProfile $presentationProfile): JsonResponse
    {
        $rules = [
            'name' => ['sometimes', 'required', 'string', 'max:60', 'unique:presentation_profiles,name,'.$presentationProfile->id],
        ];

        if ($request->has('config')) {
            $rules = array_merge($rules, $this->displayConfig->rules('config.'));
        }

        $validated = $request->validate($rules);

        if (array_key_exists('name', $validated)) {
            $presentationProfile->name = trim((string) $validated['name']);
        }

        if (array_key_exists('config', $validated)) {
            $presentationProfile->config = $this->displayConfig->normalize($validated['config']);
        }

        $presentationProfile->save();

        return response()->json(array_merge([
            'success' => true,
            'message' => __('Profil ":name" dikemas kini.', ['name' => $presentationProfile->name]),
            'profile' => $this->profilePayload($presentationProfile),
        ], $this->listPayload()));
    }

    public function destroy(PresentationProfile $presentationProfile): JsonResponse
    {
        $name = $presentationProfile->name;
        $wasActive = $this->displayConfig->activeProfileId() === $presentationProfile->id;

        $presentationProfile->delete();

        if ($wasActive) {
            $this->displayConfig->setActiveProfileId(null);
        }

        return response()->json(array_merge([
            'success' => true,
            'message' => __('Profil ":name" dipadam.', ['name' => $name]),
        ], $this->listPayload()));
    }

    public function apply(PresentationProfile $presentationProfile): JsonResponse
    {
        $config = $this->displayConfig->resolve($presentationProfile->config);

        $this->displayConfig->saveLive($config);
        $this->displayConfig->setActiveProfileId($presentationProfile->id);

        return response()->json(array_merge([
            'success' => true,
            'message' => __('Profil ":name" sedang digunakan.', ['name' => $presentationProfile->name]),
            'form_values' => $this->displayConfig->toFormValues($config),
        ], $this->listPayload()));
    }

    /**
     * @return array{profiles: array<int, array<string, mixed>>, active_profile_id: int|null, max: int}
     */
    private function listPayload(): array
    {
        $profiles = PresentationProfile::query()
            ->orderBy('name')
            ->get()
            ->map(fn (PresentationProfile $profile) => $this->profilePayload($profile))
            ->values()
            ->all();

        return [
            'profiles' => $profiles,
            'active_profile_id' => $this->displayConfig->activeProfileId(),
            'max' => PresentationProfile::MAX_PROFILES,
        ];
    }

    /**
     * @return array{id: int, name: string, updated_at: string, updated_human: string, form_values: array<string, int|string>}
     */
    private function profilePayload(PresentationProfile $profile): array
    {
        $config = $this->displayConfig->resolve($profile->config);

        return [
            'id' => $profile->id,
            'name' => $profile->name,
            'updated_at' => $profile->updated_at?->toIso8601String() ?? '',
            'updated_human' => $profile->updated_at?->diffForHumans() ?? '',
            'form_values' => $this->displayConfig->toFormValues($config),
        ];
    }
}
