<?php

declare(strict_types=1);

use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Pegawai;
use App\Models\Ptj;
use App\Models\SesiMajlis;
use App\Models\User;
use App\Services\Kehadiran\CallingPriorityService;
use App\Services\Kehadiran\KehadiranCallingService;
use App\Services\SettingsService;

/**
 * One attended officer per category (plus OKU walk-in) and one absentee.
 */
function seedCallingPriority(): SesiMajlis
{
    $sesi = SesiMajlis::query()->create([
        'sesi' => 'Sesi Keutamaan',
        'is_active' => true,
        'is_late' => false,
        'countdown_start_late' => 501,
        'seat_offset' => 0,
        's_kehadiran' => SesiMajlis::S_KEHADIRAN_PAGI,
    ]);
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Keutamaan']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Pegawai Keutamaan']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Keutamaan']);

    $make = function (string $nama, string $noKp, array $attrs) use ($sesi, $ptj, $jawatan, $gred): void {
        Pegawai::query()->create(array_merge([
            'nama' => $nama,
            'no_kp' => $noKp,
            'ptj_id' => $ptj->id,
            'jawatan_id' => $jawatan->id,
            'gred_id' => $gred->id,
            'sesi_majlis_id' => $sesi->id,
            'rsvp' => true,
            'is_attend' => true,
            'is_late' => false,
            'is_oku' => false,
            's_kehadiran' => Pegawai::S_KEHADIRAN_PAGI,
        ], $attrs));
    };

    $make('Rsvp', '910101010001', ['no_kerusi' => 2]);
    $make('Walkin', '910101010002', ['rsvp' => false, 'is_late' => true, 'no_panggilan_lewat' => 501, 'no_kerusi' => 501]);
    $make('Oku Walkin', '910101010003', ['rsvp' => false, 'is_oku' => true, 'is_late' => true, 'no_panggilan_lewat' => 502, 'no_kerusi' => 502]);
    $make('Oku Rsvp', '910101010004', ['is_oku' => true, 'no_kerusi' => 1]);
    $make('Tidak Hadir', '910101010005', ['is_attend' => false, 'sesi_majlis_id' => null, 'no_kerusi' => 3]);

    return $sesi;
}

function reportDatatableNames(User $admin, SesiMajlis $sesi, string $section): array
{
    return collect(test()->actingAs($admin)
        ->getJson(route('admin.report.datatable', ['sesi_id' => $sesi->id, 'section' => $section, 'draw' => 1]))
        ->assertOk()
        ->json('data'))->pluck('nama')->all();
}

it('shows the susunan panggilan page to admin only', function (): void {
    $this->actingAs(adminUser())
        ->get(route('admin.kawalan.susunan-panggilan.index'))
        ->assertOk()
        ->assertSee('Susunan Panggilan')
        ->assertSee('Pegawai OKU');

    $this->actingAs(mediaUser())->get(route('admin.kawalan.susunan-panggilan.index'))->assertForbidden();
    $this->actingAs(plainUser())->putJson(route('admin.kawalan.susunan-panggilan.update'), [
        'order' => ['oku', 'rsvp', 'walkin'],
    ])->assertForbidden();
});

it('saves a new priority and applies it to senarai, paparan, report and mc list', function (): void {
    $sesi = seedCallingPriority();
    $admin = adminUser();
    $media = mediaUser();
    $expected = ['Oku Walkin', 'Oku Rsvp', 'Walkin', 'Rsvp'];

    $this->actingAs($admin)
        ->putJson(route('admin.kawalan.susunan-panggilan.update'), ['order' => ['oku', 'walkin', 'rsvp']])
        ->assertOk()
        ->assertJsonPath('order', ['oku', 'walkin', 'rsvp']);

    expect(app(CallingPriorityService::class)->order())->toBe(['oku', 'walkin', 'rsvp']);

    expect(app(KehadiranCallingService::class)->attendedPegawaiForDisplay($sesi->id)->pluck('nama')->all())
        ->toBe($expected);

    $this->actingAs($media)
        ->get(route('media.senarai.present', ['sesi_id' => $sesi->id]))
        ->assertOk()
        ->assertViewHas('officerSlides', fn ($slides) => $slides->pluck('nama')->all() === $expected);

    $paparan = $this->actingAs($media)
        ->getJson(route('media.paparan.datatable').'?'.http_build_query([
            'draw' => 1,
            'start' => 0,
            'length' => 50,
            'sesi_majlis_id' => $sesi->id,
        ]))
        ->assertOk();
    expect(collect($paparan->json('data'))->pluck('id')->map(fn ($id) => Pegawai::query()->find($id)->nama)->all())
        ->toBe($expected);

    expect(reportDatatableNames($admin, $sesi, 'oku'))->toBe(['Oku Walkin', 'Oku Rsvp']);
    expect(reportDatatableNames($admin, $sesi, 'rsvp'))->toBe(['Rsvp']);
    expect(reportDatatableNames($admin, $sesi, 'walkin'))->toBe(['Walkin']);

    $this->actingAs($admin)
        ->get(route('admin.report.preview', ['sesi_id' => $sesi->id]))
        ->assertOk()
        ->assertViewHas('sections', fn ($sections) => array_keys($sections) === ['oku', 'walkin', 'rsvp', 'notattend'])
        ->assertViewHas('mcGroups', fn ($groups) => collect($groups)->pluck('group')->all() === ['oku', 'walkin', 'rsvp']
            && collect($groups)->flatMap(fn ($g) => $g['rows']->pluck('bil'))->all() === [1, 2, 3, 4])
        ->assertSeeInOrder(['Senarai Nama untuk MC', 'Oku Walkin', 'Oku Rsvp', 'Walkin', 'Rsvp']);
});

it('defaults to rsvp, walk-in, then oku with oku rsvp first', function (): void {
    $sesi = seedCallingPriority();

    expect(app(KehadiranCallingService::class)->attendedPegawaiForDisplay($sesi->id)->pluck('nama')->all())
        ->toBe(['Rsvp', 'Walkin', 'Oku Rsvp', 'Oku Walkin']);
});

it('rejects invalid priority payloads', function (array $order): void {
    $this->actingAs(adminUser())
        ->putJson(route('admin.kawalan.susunan-panggilan.update'), ['order' => $order])
        ->assertUnprocessable();

    expect(app(CallingPriorityService::class)->order())->toBe(CallingPriorityService::DEFAULT_ORDER);
})->with([
    'duplicate' => [['rsvp', 'rsvp', 'oku']],
    'unknown key' => [['rsvp', 'walkin', 'vip']],
    'too few' => [['rsvp', 'walkin']],
]);

it('sanitises a broken stored priority', function (): void {
    app(SettingsService::class)->set(CallingPriorityService::SETTING_KEY, ['oku', 'oku', 'bogus']);

    expect(app(CallingPriorityService::class)->order())->toBe(['oku', 'rsvp', 'walkin']);
});

it('downloads the mc list pdf', function (): void {
    $sesi = seedCallingPriority();

    $response = $this->actingAs(adminUser())
        ->get(route('admin.report.download', ['sesi_id' => $sesi->id, 'export_type' => 'mc']));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('senarai-mc-');
});
