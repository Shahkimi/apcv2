<?php

declare(strict_types=1);

use App\Models\PresentationProfile;
use App\Models\User;
use App\Services\EventModeService;
use App\Services\Presentation\PresentationDisplayConfigService;
use App\Services\SettingsService;

function presentationMediaUser(): User
{
    return User::factory()->create(['role' => User::ROLE_MEDIA]);
}

function presentationConfigPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'position' => [
            'mt_base' => 100,
            'mt_sm' => 150,
            'mt_md' => 200,
            'translate_y' => -20,
        ],
        'fonts' => [
            'name_base' => 20,
            'name_sm' => 24,
            'name_md' => 28,
            'jawatan_base' => 16,
            'jawatan_sm' => 18,
            'jawatan_md' => 20,
            'ptj_base' => 'text-lg',
            'ptj_sm' => 'text-xl',
            'tarikh_base' => 18,
            'tarikh_sm' => 22,
            'tarikh_md' => 26,
            'bersara_base' => 18,
            'bersara_sm' => 22,
            'bersara_md' => 26,
            'tempoh_base' => 18,
            'tempoh_sm' => 22,
            'tempoh_md' => 26,
        ],
    ], $overrides);
}

it('renders the presentation settings page for media', function (): void {
    $this->actingAs(presentationMediaUser())
        ->get(route('media.kawalan.presentation.index'))
        ->assertOk()
        ->assertSee('Kawalan Paparan Presentasi')
        ->assertSee(__('Pratonton'))
        ->assertSee('id="fonts_ptj_sm"', false)
        ->assertSee('id="position_translate_y"', false);
});

it('forbids non-media roles from the presentation settings page', function (): void {
    $this->actingAs(User::factory()->create(['role' => User::ROLE_USER]))
        ->get(route('media.kawalan.presentation.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
        ->get(route('media.kawalan.presentation.index'))
        ->assertForbidden();
});

it('redirects guests from the presentation settings page', function (): void {
    $this->get(route('media.kawalan.presentation.index'))
        ->assertRedirect(route('login'));
});

it('saves the live presentation config via json update', function (): void {
    $media = presentationMediaUser();

    $response = $this->actingAs($media)
        ->putJson(route('media.kawalan.presentation.update'), presentationConfigPayload())
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($response->json('form_values.position_mt_base'))->toBe(100);
    expect($response->json('active_profile_id'))->toBeNull();

    $stored = app(SettingsService::class)->get(PresentationDisplayConfigService::LIVE_SETTINGS_KEY);
    expect($stored['position']['mt_base'])->toBe('100px');
    expect($stored['fonts']['name_base'])->toBe(20);
});

it('clears the active profile marker when update is submitted without profile_id', function (): void {
    $media = presentationMediaUser();
    $profile = PresentationProfile::query()->create([
        'name' => 'Malam Gala',
        'config' => presentationConfigPayload(),
    ]);
    app(PresentationDisplayConfigService::class)->setActiveProfileId($profile->id);

    $this->actingAs($media)
        ->putJson(route('media.kawalan.presentation.update'), presentationConfigPayload(['position' => ['mt_base' => 999]]))
        ->assertOk()
        ->assertJsonPath('active_profile_id', null);

    expect(app(PresentationDisplayConfigService::class)->activeProfileId())->toBeNull();
});

it('sets the active profile marker when update is submitted with profile_id', function (): void {
    $media = presentationMediaUser();
    $profile = PresentationProfile::query()->create([
        'name' => 'Malam Gala',
        'config' => presentationConfigPayload(),
    ]);

    $payload = presentationConfigPayload();
    $payload['profile_id'] = $profile->id;

    $this->actingAs($media)
        ->putJson(route('media.kawalan.presentation.update'), $payload)
        ->assertOk()
        ->assertJsonPath('active_profile_id', $profile->id);
});

it('creates a presentation profile', function (): void {
    $media = presentationMediaUser();

    $response = $this->actingAs($media)
        ->postJson(route('media.kawalan.presentation.profiles.store'), [
            'name' => 'Majlis Pagi',
            'config' => presentationConfigPayload(),
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('profile.name', 'Majlis Pagi')
        ->assertJsonCount(1, 'profiles');

    expect($response->json('profile.form_values.fonts_ptj_base'))->toBe('text-lg');

    $this->assertDatabaseHas('presentation_profiles', ['name' => 'Majlis Pagi']);
});

it('rejects a duplicate profile name', function (): void {
    $media = presentationMediaUser();
    PresentationProfile::query()->create(['name' => 'Majlis Pagi', 'config' => presentationConfigPayload()]);

    $this->actingAs($media)
        ->postJson(route('media.kawalan.presentation.profiles.store'), [
            'name' => 'Majlis Pagi',
            'config' => presentationConfigPayload(),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('rejects an invalid config value on profile creation', function (): void {
    $media = presentationMediaUser();

    $this->actingAs($media)
        ->postJson(route('media.kawalan.presentation.profiles.store'), [
            'name' => 'Tidak Sah',
            'config' => presentationConfigPayload(['fonts' => ['ptj_base' => 'text-huge']]),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['config.fonts.ptj_base']);
});

it('rejects an 11th profile with a limit message', function (): void {
    $media = presentationMediaUser();

    for ($i = 1; $i <= PresentationProfile::MAX_PROFILES; $i++) {
        PresentationProfile::query()->create([
            'name' => "Profil {$i}",
            'config' => presentationConfigPayload(),
        ]);
    }

    $this->actingAs($media)
        ->postJson(route('media.kawalan.presentation.profiles.store'), [
            'name' => 'Profil Lebihan',
            'config' => presentationConfigPayload(),
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    expect(PresentationProfile::query()->count())->toBe(PresentationProfile::MAX_PROFILES);
});

it('renames a profile without touching its config', function (): void {
    $media = presentationMediaUser();
    $profile = PresentationProfile::query()->create([
        'name' => 'Nama Lama',
        'config' => presentationConfigPayload(),
    ]);

    $this->actingAs($media)
        ->putJson(route('media.kawalan.presentation.profiles.update', $profile), [
            'name' => 'Nama Baharu',
        ])
        ->assertOk()
        ->assertJsonPath('profile.name', 'Nama Baharu');

    $fresh = $profile->fresh();
    expect($fresh->name)->toBe('Nama Baharu');
    expect($fresh->config['position']['mt_base'])->toBe(100);
});

it('overwrites a profile config without touching its name', function (): void {
    $media = presentationMediaUser();
    $profile = PresentationProfile::query()->create([
        'name' => 'Tetap',
        'config' => presentationConfigPayload(),
    ]);

    $this->actingAs($media)
        ->putJson(route('media.kawalan.presentation.profiles.update', $profile), [
            'config' => presentationConfigPayload(['position' => ['mt_base' => 555]]),
        ])
        ->assertOk();

    $fresh = $profile->fresh();
    expect($fresh->name)->toBe('Tetap');
    expect($fresh->config['position']['mt_base'])->toBe('555px');
});

it('applies a profile onto the live config and marks it active', function (): void {
    $media = presentationMediaUser();
    $profile = PresentationProfile::query()->create([
        'name' => 'Petang',
        'config' => presentationConfigPayload(['fonts' => ['name_base' => 77]]),
    ]);

    $response = $this->actingAs($media)
        ->postJson(route('media.kawalan.presentation.profiles.apply', $profile))
        ->assertOk()
        ->assertJsonPath('active_profile_id', $profile->id);

    expect($response->json('form_values.fonts_name_base'))->toBe(77);

    $live = app(PresentationDisplayConfigService::class)->live();
    expect($live['fonts']['name_base'])->toBe(77);
});

it('deletes a profile and clears the active marker if it was active', function (): void {
    $media = presentationMediaUser();
    $profile = PresentationProfile::query()->create([
        'name' => 'Sementara',
        'config' => presentationConfigPayload(),
    ]);
    app(PresentationDisplayConfigService::class)->setActiveProfileId($profile->id);

    $this->actingAs($media)
        ->deleteJson(route('media.kawalan.presentation.profiles.destroy', $profile))
        ->assertOk()
        ->assertJsonPath('active_profile_id', null)
        ->assertJsonCount(0, 'profiles');

    $this->assertDatabaseMissing('presentation_profiles', ['id' => $profile->id]);
    expect(app(PresentationDisplayConfigService::class)->activeProfileId())->toBeNull();
});

it('resolves display settings for the presentation screen after the refactor', function (): void {
    $media = presentationMediaUser();
    app(SettingsService::class)->set(PresentationDisplayConfigService::LIVE_SETTINGS_KEY, presentationConfigPayload([
        'fonts' => ['name_base' => 61],
    ]));

    $this->actingAs($media)
        ->get(route('media.senarai.present'))
        ->assertOk()
        ->assertSee('61px', false);
});

it('shows jasamu font rows instead of the ptj select when event mode is jasamu', function (): void {
    app(EventModeService::class)->set(EventModeService::MODE_JASAMU);
    $media = presentationMediaUser();

    $this->actingAs($media)
        ->get(route('media.kawalan.presentation.index'))
        ->assertOk()
        ->assertSee('id="fonts_tarikh_md"', false)
        ->assertSee('id="fonts_bersara_md"', false)
        ->assertSee('id="fonts_tempoh_md"', false)
        ->assertDontSee('id="fonts_ptj_sm"', false);
});

it('shows the ptj select instead of jasamu font rows when event mode is apc', function (): void {
    $media = presentationMediaUser();

    $this->actingAs($media)
        ->get(route('media.kawalan.presentation.index'))
        ->assertOk()
        ->assertSee('id="fonts_ptj_sm"', false)
        ->assertDontSee('id="fonts_tarikh_md"', false);
});

it('saves and reflects jasamu font sizes on the presentation screen', function (): void {
    app(EventModeService::class)->set(EventModeService::MODE_JASAMU);
    $media = presentationMediaUser();

    $this->actingAs($media)
        ->putJson(route('media.kawalan.presentation.update'), presentationConfigPayload(['fonts' => ['tarikh_md' => 61]]))
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->actingAs($media)
        ->get(route('media.senarai.present'))
        ->assertOk()
        ->assertSee('--officer-tarikh-font-size-md: 61px', false);
});

it('rejects an out-of-range jasamu font size on profile creation', function (): void {
    $media = presentationMediaUser();

    $this->actingAs($media)
        ->postJson(route('media.kawalan.presentation.profiles.store'), [
            'name' => 'Tidak Sah Jasamu',
            'config' => presentationConfigPayload(['fonts' => ['tarikh_md' => 5]]),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['config.fonts.tarikh_md']);
});

it('falls back to default jasamu font sizes when an old payload omits them', function (): void {
    $media = presentationMediaUser();
    $legacyPayload = presentationConfigPayload();
    unset(
        $legacyPayload['fonts']['tarikh_base'], $legacyPayload['fonts']['tarikh_sm'], $legacyPayload['fonts']['tarikh_md'],
        $legacyPayload['fonts']['bersara_base'], $legacyPayload['fonts']['bersara_sm'], $legacyPayload['fonts']['bersara_md'],
        $legacyPayload['fonts']['tempoh_base'], $legacyPayload['fonts']['tempoh_sm'], $legacyPayload['fonts']['tempoh_md'],
    );

    $this->actingAs($media)
        ->putJson(route('media.kawalan.presentation.update'), $legacyPayload)
        ->assertOk()
        ->assertJsonPath('success', true);

    $live = app(PresentationDisplayConfigService::class)->live();
    expect($live['fonts']['tarikh_md'])->toBe(36);
    expect($live['fonts']['bersara_md'])->toBe(36);
    expect($live['fonts']['tempoh_md'])->toBe(36);
});
