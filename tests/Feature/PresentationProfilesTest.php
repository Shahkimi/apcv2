<?php

declare(strict_types=1);

use App\Models\PresentationFont;
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

it('renders the data field picker and every font row regardless of event mode', function (): void {
    $media = presentationMediaUser();

    $this->actingAs($media)
        ->get(route('media.kawalan.presentation.index'))
        ->assertOk()
        ->assertSee('Data Dipaparkan')
        ->assertSee('id="fonts_ptj_sm"', false)
        ->assertSee('id="fonts_tarikh_md"', false)
        ->assertSee('id="fonts_bersara_md"', false)
        ->assertSee('id="fonts_tempoh_md"', false);
});

function presentationFields(array $overrides = []): array
{
    return array_replace([
        'apc' => [
            ['key' => 'tarikh', 'visible' => true, 'label' => true],
            ['key' => 'jawatan', 'visible' => true, 'label' => false],
            ['key' => 'ptj', 'visible' => false, 'label' => false],
            ['key' => 'bersara', 'visible' => false, 'label' => true],
            ['key' => 'tempoh', 'visible' => false, 'label' => true],
        ],
        'jasamu' => [
            ['key' => 'jawatan', 'visible' => true, 'label' => false],
            ['key' => 'tempoh', 'visible' => true, 'label' => false],
            ['key' => 'tarikh', 'visible' => false, 'label' => true],
            ['key' => 'bersara', 'visible' => false, 'label' => true],
            ['key' => 'ptj', 'visible' => false, 'label' => false],
        ],
    ], $overrides);
}

it('defaults to the original per-mode layout when no fields are stored', function (): void {
    $config = app(PresentationDisplayConfigService::class)->live();

    $apc = collect($config['fields']['apc'])->where('visible', true)->pluck('key')->all();
    $jasamu = collect($config['fields']['jasamu'])->where('visible', true)->pluck('key')->all();

    expect($apc)->toBe(['jawatan', 'ptj']);
    expect($jasamu)->toBe(['jawatan', 'tarikh', 'bersara', 'tempoh']);
    expect($config['hide_empty'])->toBeTrue();
});

it('saves field selection and hide_empty and returns them in form values', function (): void {
    $media = presentationMediaUser();
    $payload = presentationConfigPayload();
    $payload['fields'] = presentationFields();
    $payload['hide_empty'] = false;

    $response = $this->actingAs($media)
        ->putJson(route('media.kawalan.presentation.update'), $payload)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('form_values.hide_empty', false)
        ->assertJsonPath('form_values.fields_apc.0.key', 'tarikh')
        ->assertJsonPath('form_values.fields_apc.0.label', true)
        ->assertJsonPath('form_values.fields_jasamu.1.key', 'tempoh');

    expect($response->json('form_values.fields_apc'))->toHaveCount(5);

    $live = app(PresentationDisplayConfigService::class)->live();
    expect($live['hide_empty'])->toBeFalse();
    expect($live['fields']['apc'][0])->toBe(['key' => 'tarikh', 'visible' => true, 'label' => true]);
});

it('rejects an unknown field key', function (): void {
    $media = presentationMediaUser();
    $payload = presentationConfigPayload();
    $payload['fields'] = presentationFields(['apc' => [['key' => 'gaji', 'visible' => true, 'label' => false]]]);

    $this->actingAs($media)
        ->putJson(route('media.kawalan.presentation.update'), $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['fields.apc.0.key']);
});

it('drops duplicate keys and appends missing fields as hidden', function (): void {
    $media = presentationMediaUser();
    $payload = presentationConfigPayload();
    $payload['fields'] = presentationFields(['apc' => [
        ['key' => 'ptj', 'visible' => true, 'label' => false],
        ['key' => 'ptj', 'visible' => false, 'label' => true],
    ]]);

    $this->actingAs($media)
        ->putJson(route('media.kawalan.presentation.update'), $payload)
        ->assertOk();

    $apc = app(PresentationDisplayConfigService::class)->live()['fields']['apc'];

    expect(array_column($apc, 'key'))->toBe(['ptj', 'jawatan', 'tarikh', 'bersara', 'tempoh']);
    expect($apc[0]['visible'])->toBeTrue();
    expect($apc[1]['visible'])->toBeFalse();
});

it('falls back to per-mode default fields when a legacy payload omits them', function (): void {
    $media = presentationMediaUser();

    $this->actingAs($media)
        ->putJson(route('media.kawalan.presentation.update'), presentationConfigPayload())
        ->assertOk()
        ->assertJsonPath('form_values.hide_empty', true)
        ->assertJsonPath('form_values.fields_apc.1.key', 'ptj')
        ->assertJsonPath('form_values.fields_apc.1.visible', true)
        ->assertJsonPath('form_values.fields_jasamu.1.key', 'tarikh');
});

it('stores field selection in profiles and restores it on apply', function (): void {
    $media = presentationMediaUser();
    $config = presentationConfigPayload();
    $config['fields'] = presentationFields();
    $config['hide_empty'] = false;

    $created = $this->actingAs($media)
        ->postJson(route('media.kawalan.presentation.profiles.store'), ['name' => 'Tarikh Dahulu', 'config' => $config])
        ->assertOk()
        ->assertJsonPath('profile.form_values.fields_apc.0.key', 'tarikh');

    $this->actingAs($media)
        ->postJson(route('media.kawalan.presentation.profiles.apply', $created->json('profile.id')))
        ->assertOk()
        ->assertJsonPath('form_values.hide_empty', false)
        ->assertJsonPath('form_values.fields_apc.0.key', 'tarikh');

    $live = app(PresentationDisplayConfigService::class)->live();
    expect(array_column($live['fields']['apc'], 'key'))->toBe(['tarikh', 'jawatan', 'ptj', 'bersara', 'tempoh']);
});

it('opens an old profile without fields using default fields', function (): void {
    $media = presentationMediaUser();
    $profile = PresentationProfile::query()->create([
        'name' => 'Lama',
        'config' => presentationConfigPayload(),
    ]);

    $this->actingAs($media)
        ->postJson(route('media.kawalan.presentation.profiles.apply', $profile))
        ->assertOk()
        ->assertJsonPath('form_values.fields_jasamu.0.key', 'jawatan')
        ->assertJsonPath('form_values.hide_empty', true);
});

it('renders only the visible lines in the configured order on the presentation screen', function (): void {
    $media = presentationMediaUser();
    $payload = presentationConfigPayload();
    $payload['fields'] = presentationFields();
    $this->actingAs($media)->putJson(route('media.kawalan.presentation.update'), $payload)->assertOk();

    $sesi = activeSesiForKehadiran();
    createPegawaiForTest()->update(['is_attend' => true, 'sesi_majlis_id' => $sesi->id]);

    $this->actingAs($media)
        ->get(route('media.senarai.present'))
        ->assertOk()
        ->assertSeeInOrder(['id="officer-tarikh"', 'id="officer-jawatan"'], false)
        ->assertDontSee('id="officer-ptj"', false)
        ->assertDontSee('id="officer-bersara"', false)
        ->assertSee('Tarikh bersara');
});

it('keeps field selection separate per event mode on the presentation screen', function (): void {
    $media = presentationMediaUser();
    $payload = presentationConfigPayload();
    $payload['fields'] = presentationFields();
    $this->actingAs($media)->putJson(route('media.kawalan.presentation.update'), $payload)->assertOk();

    $sesi = activeSesiForKehadiran();
    createPegawaiForTest()->update(['is_attend' => true, 'sesi_majlis_id' => $sesi->id]);

    app(EventModeService::class)->set(EventModeService::MODE_JASAMU);

    $this->actingAs($media)
        ->get(route('media.senarai.present'))
        ->assertOk()
        ->assertSeeInOrder(['id="officer-jawatan"', 'id="officer-tempoh"'], false)
        ->assertDontSee('id="officer-tarikh"', false)
        ->assertDontSee('id="officer-ptj"', false);
});

it('exposes the hide_empty flag to the presentation screen script', function (): void {
    $media = presentationMediaUser();
    $sesi = activeSesiForKehadiran();
    createPegawaiForTest()->update(['is_attend' => true, 'sesi_majlis_id' => $sesi->id, 'tarikh_bersara' => null]);

    $this->actingAs($media)
        ->get(route('media.senarai.present'))
        ->assertOk()
        ->assertSee('const hideEmpty = true', false);
});

it('shows a real officer in the settings preview data', function (): void {
    $media = presentationMediaUser();
    $sesi = activeSesiForKehadiran();
    $pegawai = createPegawaiForTest();
    $pegawai->update(['is_attend' => true, 'sesi_majlis_id' => $sesi->id, 'hadir_at' => now()]);

    $this->actingAs($media)
        ->get(route('media.kawalan.presentation.index'))
        ->assertOk()
        ->assertSee($pegawai->nama);
});

it('auto-saves field selection without touching saved position and fonts', function (): void {
    $media = presentationMediaUser();
    $this->actingAs($media)
        ->putJson(route('media.kawalan.presentation.update'), presentationConfigPayload(['position' => ['mt_base' => 321]]))
        ->assertOk();

    $this->actingAs($media)
        ->putJson(route('media.kawalan.presentation.fields.update'), ['fields' => presentationFields(), 'hide_empty' => false])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('form_values.position_mt_base', 321)
        ->assertJsonPath('form_values.fields_apc.0.key', 'tarikh')
        ->assertJsonPath('form_values.hide_empty', false);

    $live = app(PresentationDisplayConfigService::class)->live();
    expect($live['position']['mt_base'])->toBe('321px');
    expect($live['hide_empty'])->toBeFalse();
    expect(array_column($live['fields']['jasamu'], 'key'))->toBe(['jawatan', 'tempoh', 'tarikh', 'bersara', 'ptj']);
});

it('requires both fields and hide_empty on the auto-save endpoint', function (): void {
    $this->actingAs(presentationMediaUser())
        ->putJson(route('media.kawalan.presentation.fields.update'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['fields', 'hide_empty']);
});

it('forbids non-media roles from the field auto-save endpoint', function (): void {
    $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
        ->putJson(route('media.kawalan.presentation.fields.update'), ['fields' => presentationFields(), 'hide_empty' => true])
        ->assertForbidden();
});

it('clears the active profile when auto-saved fields no longer match it', function (): void {
    $media = presentationMediaUser();
    $profile = PresentationProfile::query()->create(['name' => 'Asal', 'config' => presentationConfigPayload()]);
    $this->actingAs($media)->postJson(route('media.kawalan.presentation.profiles.apply', $profile))->assertOk();

    $this->actingAs($media)
        ->putJson(route('media.kawalan.presentation.fields.update'), ['fields' => presentationFields(), 'hide_empty' => true])
        ->assertOk()
        ->assertJsonPath('active_profile_id', null);
});

it('keeps the active profile when auto-saved fields still match it', function (): void {
    $media = presentationMediaUser();
    $config = presentationConfigPayload();
    $config['fields'] = presentationFields();
    $config['hide_empty'] = false;
    $profile = PresentationProfile::query()->create([
        'name' => 'Sepadan',
        'config' => app(PresentationDisplayConfigService::class)->normalize($config),
    ]);
    $this->actingAs($media)->postJson(route('media.kawalan.presentation.profiles.apply', $profile))->assertOk();

    $this->actingAs($media)
        ->putJson(route('media.kawalan.presentation.fields.update'), ['fields' => presentationFields(), 'hide_empty' => false])
        ->assertOk()
        ->assertJsonPath('active_profile_id', $profile->id);
});

it('groups the settings into data, position and font tabs without removing any inputs', function (): void {
    $this->actingAs(presentationMediaUser())
        ->get(route('media.kawalan.presentation.index'))
        ->assertOk()
        ->assertSee('role="tablist"', false)
        ->assertSee('id="tab-data"', false)
        ->assertSee('id="tab-kedudukan"', false)
        ->assertSee('id="tab-fon"', false)
        ->assertSee('id="tabpanel-data"', false)
        ->assertSee('id="tabpanel-kedudukan"', false)
        ->assertSee('id="tabpanel-fon"', false)
        ->assertSee('id="position_mt_md"', false)
        ->assertSee('id="fonts_name_md"', false);
});

it('hides the save bar on the data tab because that tab auto-saves', function (): void {
    $this->actingAs(presentationMediaUser())
        ->get(route('media.kawalan.presentation.index'))
        ->assertOk()
        ->assertSee('x-show="tab !== \'data\'"', false);
});

function presentationTypographyFont(string $name = 'Poppins'): PresentationFont
{
    return PresentationFont::query()->create([
        'name' => $name,
        'file_path' => 'fonts/'.md5($name).'.woff2',
        'format' => 'woff2',
        'file_size' => 20,
    ]);
}

it('defaults every slide part to the Inter font style when typography is missing', function (): void {
    $config = app(PresentationDisplayConfigService::class)->resolve(presentationConfigPayload());

    expect(array_keys($config['typography']))->toBe(PresentationDisplayConfigService::TYPO_PARTS)
        ->and($config['typography']['name'])->toBe(['font_id' => null, 'weight' => 700, 'italic' => false, 'tracking' => 'normal'])
        ->and($config['typography']['jawatan'])->toBe(['font_id' => null, 'weight' => 500, 'italic' => false, 'tracking' => 'normal']);
});

it('saves per-part font style via the live update and returns it in form values', function (): void {
    $font = presentationTypographyFont();

    $response = $this->actingAs(presentationMediaUser())
        ->putJson(route('media.kawalan.presentation.update'), presentationConfigPayload([
            'typography' => [
                'jawatan' => ['font_id' => $font->id, 'weight' => 800, 'italic' => true, 'tracking' => 'wider'],
                'name' => ['font_id' => null, 'weight' => 600, 'italic' => false, 'tracking' => 'tight'],
            ],
        ]))
        ->assertOk();

    expect($response->json('form_values.typo_jawatan_font'))->toBe($font->id)
        ->and($response->json('form_values.typo_jawatan_weight'))->toBe(800)
        ->and($response->json('form_values.typo_jawatan_italic'))->toBeTrue()
        ->and($response->json('form_values.typo_jawatan_tracking'))->toBe('wider')
        ->and($response->json('form_values.typo_name_weight'))->toBe(600)
        ->and($response->json('form_values.typo_ptj_weight'))->toBe(500);

    $live = app(PresentationDisplayConfigService::class)->live();
    expect($live['typography']['jawatan'])->toBe(['font_id' => $font->id, 'weight' => 800, 'italic' => true, 'tracking' => 'wider']);
});

it('rejects an unknown font, weight or letter spacing', function (): void {
    $this->actingAs(presentationMediaUser())
        ->putJson(route('media.kawalan.presentation.update'), presentationConfigPayload([
            'typography' => [
                'jawatan' => ['font_id' => 999, 'weight' => 450, 'tracking' => 'huge'],
            ],
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'typography.jawatan.font_id',
            'typography.jawatan.weight',
            'typography.jawatan.tracking',
        ]);
});

it('drops a font id that no longer exists when resolving a stored config', function (): void {
    $config = app(PresentationDisplayConfigService::class)->resolve(presentationConfigPayload([
        'typography' => ['ptj' => ['font_id' => 4242, 'weight' => 700]],
    ]));

    expect($config['typography']['ptj'])->toBe(['font_id' => null, 'weight' => 700, 'italic' => false, 'tracking' => 'normal']);
});

it('stores font style in profiles and restores it on apply', function (): void {
    $media = presentationMediaUser();
    $font = presentationTypographyFont();

    $response = $this->actingAs($media)
        ->postJson(route('media.kawalan.presentation.profiles.store'), [
            'name' => 'Majlis Pagi',
            'config' => presentationConfigPayload([
                'typography' => ['tempoh' => ['font_id' => $font->id, 'weight' => 600, 'italic' => true, 'tracking' => 'wide']],
            ]),
        ])
        ->assertOk();

    expect($response->json('profile.form_values.typo_tempoh_font'))->toBe($font->id);

    $profile = PresentationProfile::query()->where('name', 'Majlis Pagi')->sole();
    $this->actingAs($media)
        ->postJson(route('media.kawalan.presentation.profiles.apply', $profile))
        ->assertOk()
        ->assertJsonPath('form_values.typo_tempoh_font', $font->id)
        ->assertJsonPath('form_values.typo_tempoh_italic', true)
        ->assertJsonPath('form_values.typo_tempoh_tracking', 'wide');
});

it('keeps the active profile when the saved font style still matches it', function (): void {
    $media = presentationMediaUser();
    $font = presentationTypographyFont();
    $config = presentationConfigPayload([
        'typography' => ['jawatan' => ['font_id' => $font->id, 'weight' => 700, 'italic' => true, 'tracking' => 'wide']],
    ]);
    $profile = PresentationProfile::query()->create([
        'name' => 'Sepadan',
        'config' => app(PresentationDisplayConfigService::class)->normalize($config),
    ]);
    $this->actingAs($media)->postJson(route('media.kawalan.presentation.profiles.apply', $profile))->assertOk();

    $this->actingAs($media)
        ->putJson(route('media.kawalan.presentation.fields.update'), [
            'fields' => app(PresentationDisplayConfigService::class)->live()['fields'],
            'hide_empty' => true,
        ])
        ->assertOk()
        ->assertJsonPath('active_profile_id', $profile->id);
});

it('applies the font style to the presentation screen', function (): void {
    $font = presentationTypographyFont();
    $unused = presentationTypographyFont('Lato');
    app(SettingsService::class)->set(PresentationDisplayConfigService::LIVE_SETTINGS_KEY, presentationConfigPayload([
        'typography' => ['jawatan' => ['font_id' => $font->id, 'weight' => 800, 'italic' => true, 'tracking' => 'wide']],
    ]));

    $this->actingAs(presentationMediaUser())
        ->get(route('media.senarai.present'))
        ->assertOk()
        ->assertSee('@font-face{font-family:"apcfont-'.$font->id.'"', false)
        ->assertDontSee('apcfont-'.$unused->id, false)
        ->assertSee('--officer-jawatan-font-family: "apcfont-'.$font->id.'", \'Inter Variable\'', false)
        ->assertSee('--officer-jawatan-font-weight: 800;', false)
        ->assertSee('--officer-jawatan-font-style: italic;', false)
        ->assertSee('--officer-jawatan-letter-spacing: 0.04em;', false)
        ->assertSee('--officer-name-font-weight: 700;', false)
        ->assertSee('font-family: var(--officer-jawatan-font-family, inherit);', false);
});

it('renders the font style tab with every slide part and the library fonts', function (): void {
    $font = presentationTypographyFont('Poppins Tajuk');

    $this->actingAs(presentationMediaUser())
        ->get(route('media.kawalan.presentation.index'))
        ->assertOk()
        ->assertSee('id="tab-fonstyle"', false)
        ->assertSee('id="tabpanel-fonstyle"', false)
        ->assertSee('id="typo_name_font"', false)
        ->assertSee('id="typo_tempoh_weight"', false)
        ->assertSee('<option value="'.$font->id.'">Poppins Tajuk</option>', false)
        ->assertSee('font-family:"apcfont-'.$font->id.'"', false);
});
