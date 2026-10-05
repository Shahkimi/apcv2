<?php

declare(strict_types=1);

use App\Models\AnnouncedOfficer;
use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Pegawai;
use App\Models\Ptj;
use App\Models\SesiMajlis;
use App\Services\Dashboard\DashboardStatsService;
use Carbon\CarbonImmutable;

function dashboardPegawai(SesiMajlis $sesi, array $overrides = []): Pegawai
{
    static $n = 0;
    $n++;

    $ptj = Ptj::query()->firstOrCreate(['nama_ptj' => 'PTJ Dashboard']);
    $jawatan = Jawatan::query()->firstOrCreate(['desc_jawatan' => 'Pegawai']);
    $gred = Gred::query()->firstOrCreate(['desc_gred' => 'Gred Dashboard']);

    return Pegawai::query()->create(array_merge([
        'nama' => "Pegawai {$n}",
        'no_kp' => str_pad((string) $n, 12, '9', STR_PAD_LEFT),
        'ptj_id' => $ptj->id,
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
        'sesi_majlis_id' => $sesi->id,
        'rsvp' => true,
        'is_attend' => false,
        'is_late' => false,
        's_kehadiran' => Pegawai::S_KEHADIRAN_PAGI,
    ], $overrides));
}

function dashboardSesi(string $name, bool $active): SesiMajlis
{
    return SesiMajlis::query()->create([
        'sesi' => $name,
        'is_active' => $active,
        'is_late' => false,
        'countdown_start_late' => null,
        'seat_offset' => 0,
        's_kehadiran' => SesiMajlis::S_KEHADIRAN_PAGI,
    ]);
}

/** On-air sesi: 2 tepat, 1 lewat, 1 belum hadir. Other sesi: 1 tepat. */
function seedDashboardData(): array
{
    $pagi = dashboardSesi('Pagi', true);
    $petang = dashboardSesi('Petang', false);

    dashboardPegawai($pagi, ['is_attend' => true, 'hadir_at' => '2026-09-29 09:05:00']);
    dashboardPegawai($pagi, ['is_attend' => true, 'hadir_at' => '2026-09-29 09:10:00']);
    $lewat = dashboardPegawai($pagi, ['is_attend' => true, 'is_late' => true, 'hadir_at' => '2026-09-29 09:40:00']);
    dashboardPegawai($pagi);
    dashboardPegawai($petang, ['is_attend' => true, 'hadir_at' => '2026-09-29 14:00:00']);

    AnnouncedOfficer::query()->create([
        'scope_key' => 'sesi:'.$pagi->id,
        'sesi_majlis_id' => $pagi->id,
        'pegawai_id' => $lewat->id,
        'announced_at' => CarbonImmutable::parse('2026-09-29 09:45:00'),
    ]);

    return [$pagi, $petang];
}

it('renders each role dashboard with its own cards', function (string $role, string $route, string $label): void {
    seedDashboardData();
    $user = match ($role) {
        'admin' => adminUser(),
        'media' => mediaUser(),
        'user' => plainUser(),
    };

    $this->actingAs($user)
        ->get(route($route))
        ->assertOk()
        ->assertSee($label)
        ->assertSee('bento-hero', false)
        ->assertSee('data-ring=', false);
})->with([
    ['admin', 'admin.dashboard', 'Jumlah pegawai'],
    ['media', 'media.dashboard', 'Baki diumumkan'],
    ['user', 'user.dashboard', 'Belum hadir'],
]);

it('returns live admin stats scoped to the on-air sesi by default', function (): void {
    seedDashboardData();

    $json = $this->actingAs(adminUser())
        ->getJson(route('admin.dashboard.stats'))
        ->assertOk()
        ->assertHeader('Cache-Control')
        ->json();

    expect($json['stats']['total']['value'])->toBe('4')
        ->and($json['stats']['hadir']['value'])->toBe('3')
        ->and($json['stats']['hadir']['raw'])->toBe(3)
        ->and($json['stats']['hadir']['ratio'])->toEqual(75)
        ->and($json['stats']['total']['ratio'])->toBeNull()
        ->and($json['stats']['progress']['suffix'])->toBe('%')
        ->and($json['stats']['lewat']['value'])->toBe('1')
        ->and($json['stats']['belum_hadir']['value'])->toBe('1')
        ->and($json['stats']['announced']['value'])->toBe('1')
        ->and($json['stats']['baki']['value'])->toBe('2')
        ->and($json['status']['sesi'])->toBe('Pagi')
        ->and($json['charts']['timeline']['series'][0]['data'])->toBe([2, 0, 1]);
});

it('aggregates all sesi when sesi_id is empty', function (): void {
    seedDashboardData();

    $json = $this->actingAs(adminUser())
        ->getJson(route('admin.dashboard.stats', ['sesi_id' => '']))
        ->assertOk()
        ->json();

    expect($json['sesi_id'])->toBeNull()
        ->and($json['stats']['total']['value'])->toBe('5')
        ->and($json['stats']['hadir']['value'])->toBe('4');
});

it('filters by an explicit sesi_id', function (): void {
    [, $petang] = seedDashboardData();

    $json = $this->actingAs(mediaUser())
        ->getJson(route('media.dashboard.stats', ['sesi_id' => $petang->id]))
        ->assertOk()
        ->json();

    expect($json['stats']['hadir']['value'])->toBe('1')
        ->and($json['stats']['announced']['value'])->toBe('0')
        ->and($json['tables']['announcements'])->toBe([]);
});

it('lists recent announcements for media', function (): void {
    seedDashboardData();

    $json = $this->actingAs(mediaUser())
        ->getJson(route('media.dashboard.stats'))
        ->assertOk()
        ->json();

    expect($json['tables']['announcements'])->toHaveCount(1)
        ->and($json['tables']['announcements'][0][2])->toBe('09:45:00');
});

it('exposes check-in desk stats to the user role', function (): void {
    seedDashboardData();

    $this->actingAs(plainUser())
        ->getJson(route('user.dashboard.stats'))
        ->assertOk()
        ->assertJsonPath('stats.rsvp.value', '4')
        ->assertJsonPath('tables.checkins.0.3', '09:40:00');
});

it('blocks stats endpoints across roles', function (): void {
    $this->actingAs(mediaUser())->getJson(route('admin.dashboard.stats'))->assertForbidden();
    $this->actingAs(plainUser())->getJson(route('media.dashboard.stats'))->assertForbidden();
    $this->actingAs(adminUser())->getJson(route('user.dashboard.stats'))->assertForbidden();
});

it('buckets times into 15 minute slots for the latest day only', function (): void {
    $times = collect([
        CarbonImmutable::parse('2026-09-28 08:00:00'),
        CarbonImmutable::parse('2026-09-29 09:01:00'),
        CarbonImmutable::parse('2026-09-29 09:14:59'),
        CarbonImmutable::parse('2026-09-29 09:31:00'),
    ]);

    $timeline = app(DashboardStatsService::class)->timeline($times, 'Hadir');

    expect($timeline['categories'])->toBe(['09:00', '09:15', '09:30'])
        ->and($timeline['series'][0]['data'])->toBe([2, 0, 1]);
});

it('returns an empty timeline when there are no times', function (): void {
    $timeline = app(DashboardStatsService::class)->timeline(collect(), 'Hadir');

    expect($timeline['categories'])->toBe([])
        ->and($timeline['series'][0]['data'])->toBe([]);
});
