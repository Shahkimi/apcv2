<?php

declare(strict_types=1);

use App\Models\AnnouncedOfficer;
use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Pegawai;
use App\Models\Ptj;
use App\Models\SesiMajlis;
use App\Services\Report\ReportPreviewTableService;

/**
 * Default priority gives Bil: 1 Rsvp A, 2 Rsvp B, 3 Walkin C, 4 Oku D, 5 Oku E.
 *
 * @return array{0: SesiMajlis, 1: array<string, Pegawai>}
 */
function seedMcRange(): array
{
    $sesi = SesiMajlis::query()->create([
        'sesi' => 'Sesi MC',
        'is_active' => true,
        'is_late' => false,
        'countdown_start_late' => 501,
        'seat_offset' => 0,
        's_kehadiran' => SesiMajlis::S_KEHADIRAN_PAGI,
    ]);
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ MC']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Pegawai MC']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred MC']);

    $make = fn (string $nama, string $noKp, array $attrs): Pegawai => Pegawai::query()->create(array_merge([
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

    return [$sesi, [
        'A' => $make('Rsvp A', '930101010001', ['no_kerusi' => 1]),
        'B' => $make('Rsvp B', '930101010002', ['no_kerusi' => 2]),
        'C' => $make('Walkin C', '930101010003', ['rsvp' => false, 'is_late' => true, 'no_panggilan_lewat' => 501]),
        'D' => $make('Oku D', '930101010004', ['is_oku' => true, 'no_kerusi' => 3]),
        'E' => $make('Oku E', '930101010005', ['rsvp' => false, 'is_oku' => true, 'is_late' => true, 'no_panggilan_lewat' => 502]),
    ]];
}

function announceForMc(SesiMajlis $sesi, Pegawai ...$pegawais): void
{
    foreach ($pegawais as $pegawai) {
        AnnouncedOfficer::query()->create([
            'scope_key' => 'sesi:'.$sesi->id,
            'sesi_majlis_id' => $sesi->id,
            'pegawai_id' => $pegawai->id,
            'announced_at' => now(),
        ]);
    }
}

it('selects a bil range keeping the original numbering', function (): void {
    [$sesi] = seedMcRange();
    $service = app(ReportPreviewTableService::class);

    $selection = $service->mcSelection($sesi, 2, 4);

    expect($selection['rows']->pluck('bil')->all())->toBe([2, 3, 4])
        ->and($selection['rows']->map(fn ($row) => $row['pegawai']->nama)->all())->toBe(['Rsvp B', 'Walkin C', 'Oku D'])
        ->and($selection['count'])->toBe(3)
        ->and($selection['total'])->toBe(5)
        ->and($selection['isRange'])->toBeTrue();

    $groups = $service->groupRows($selection['rows'], $selection['all']);

    expect(collect($groups)->map(fn ($g) => [$g['group'], $g['continued']])->all())
        ->toBe([['rsvp', true], ['walkin', false], ['oku', false]]);
});

it('caps an open range at the end of the list', function (): void {
    [$sesi] = seedMcRange();

    $selection = app(ReportPreviewTableService::class)->mcSelection($sesi, 4, 99);

    expect($selection['rows']->pluck('bil')->all())->toBe([4, 5])
        ->and($selection['to'])->toBe(5);
});

it('skips officers already announced on layar utama', function (): void {
    [$sesi, $p] = seedMcRange();
    announceForMc($sesi, $p['A'], $p['B'], $p['D']);

    $selection = app(ReportPreviewTableService::class)->mcSelection($sesi, 2, null, true);

    expect($selection['rows']->pluck('bil')->all())->toBe([3, 5])
        ->and($selection['skipped'])->toBe(2)
        ->and($selection['announcedCount'])->toBe(3)
        ->and($selection['lastAnnounced'])->toBe(['bil' => 4, 'nama' => 'Oku D']);
});

it('returns a live mc print summary', function (): void {
    [$sesi, $p] = seedMcRange();
    announceForMc($sesi, $p['A'], $p['B']);

    $this->actingAs(adminUser())
        ->getJson(route('admin.report.mc-preview', ['sesi_id' => $sesi->id, 'from' => 3]))
        ->assertOk()
        ->assertJsonPath('total', 5)
        ->assertJsonPath('from', 3)
        ->assertJsonPath('to', 5)
        ->assertJsonPath('count', 3)
        ->assertJsonPath('announced_count', 2)
        ->assertJsonPath('next_start', 3)
        ->assertJsonPath('first', ['bil' => 3, 'nama' => 'Walkin C'])
        ->assertJsonPath('last', ['bil' => 5, 'nama' => 'Oku E'])
        ->assertJsonPath('pages_estimate', 1);
});

it('reports when every officer is already announced', function (): void {
    [$sesi, $p] = seedMcRange();
    announceForMc($sesi, ...array_values($p));

    $this->actingAs(adminUser())
        ->getJson(route('admin.report.mc-preview', ['sesi_id' => $sesi->id, 'from' => 1, 'skip_announced' => 1]))
        ->assertOk()
        ->assertJsonPath('count', 0)
        ->assertJsonPath('all_announced', true)
        ->assertJsonPath('remaining', 0)
        ->assertJsonPath('first_unannounced', null)
        ->assertJsonPath('next_start', null)
        ->assertJsonPath('first', null)
        ->assertJsonPath('pages_estimate', 0);
});

it('points at an officer still pending before the last announced one', function (): void {
    [$sesi, $p] = seedMcRange();
    announceForMc($sesi, $p['A'], $p['C'], $p['D'], $p['E']);

    $this->actingAs(adminUser())
        ->getJson(route('admin.report.mc-preview', ['sesi_id' => $sesi->id, 'from' => 3, 'skip_announced' => 1]))
        ->assertOk()
        ->assertJsonPath('count', 0)
        ->assertJsonPath('all_announced', false)
        ->assertJsonPath('remaining', 1)
        ->assertJsonPath('first_unannounced', 2)
        ->assertJsonPath('next_start', null);
});

it('validates the mc print range', function (array $query): void {
    [$sesi] = seedMcRange();

    $this->actingAs(adminUser())
        ->getJson(route('admin.report.mc-preview', ['sesi_id' => $sesi->id, ...$query]))
        ->assertUnprocessable();
})->with([
    'to before from' => [['from' => 4, 'to' => 2]],
    'zero start' => [['from' => 0]],
    'not a number' => [['from' => 'abc']],
]);

it('limits the mc print summary to admin', function (): void {
    [$sesi] = seedMcRange();

    $this->actingAs(mediaUser())
        ->getJson(route('admin.report.mc-preview', ['sesi_id' => $sesi->id]))
        ->assertForbidden();
});

it('downloads a ranged mc pdf', function (): void {
    [$sesi, $p] = seedMcRange();
    announceForMc($sesi, $p['C']);
    $admin = adminUser();

    $range = $this->actingAs($admin)
        ->get(route('admin.report.download', ['sesi_id' => $sesi->id, 'export_type' => 'mc', 'from' => 2]));

    $range->assertOk();
    expect($range->headers->get('content-type'))->toContain('application/pdf')
        ->and($range->headers->get('content-disposition'))->toContain('senarai-mc-Sesi MC-bil-2-5.pdf');

    $skip = $this->actingAs($admin)
        ->get(route('admin.report.download', ['sesi_id' => $sesi->id, 'export_type' => 'mc', 'skip_announced' => 1]));

    $skip->assertOk();
    expect($skip->headers->get('content-disposition'))->toContain('senarai-mc-Sesi MC-belum-diumumkan.pdf');
});

it('marks announced officers and offers cetak dari sini on the report page', function (): void {
    [$sesi, $p] = seedMcRange();
    announceForMc($sesi, $p['A']);

    $this->actingAs(adminUser())
        ->get(route('admin.report.preview', ['sesi_id' => $sesi->id]))
        ->assertOk()
        ->assertSee('Cetak Senarai MC')
        ->assertSee('data-print-from="5"', false)
        ->assertSee('Diumumkan')
        ->assertViewHas('announcedIds', fn (array $ids) => array_keys($ids) === [$p['A']->id]);
});
