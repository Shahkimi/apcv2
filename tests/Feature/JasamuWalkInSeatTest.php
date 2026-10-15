<?php

declare(strict_types=1);

use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Meja;
use App\Models\Pegawai;
use App\Models\Ptj;
use App\Models\SesiMajlis;
use App\Services\EventModeService;

beforeEach(function (): void {
    Meja::query()->create(['sizing' => 10]);
    app(EventModeService::class)->set(EventModeService::MODE_JASAMU);
});

function walkInSesi(array $overrides = []): SesiMajlis
{
    return SesiMajlis::query()->create(array_merge([
        'sesi' => 'Sesi Jasamu',
        'is_active' => true,
        'is_late' => false,
        'countdown_start_late' => 501,
        'seat_offset' => 0,
        's_kehadiran' => SesiMajlis::S_KEHADIRAN_PAGI,
    ], $overrides));
}

function walkInPegawai(string $noKp, array $overrides = []): Pegawai
{
    $ptj = Ptj::query()->firstOrCreate(['nama_ptj' => 'PTJ Jasamu']);
    $jawatan = Jawatan::query()->firstOrCreate(['desc_jawatan' => 'Pegawai Jasamu']);
    $gred = Gred::query()->firstOrCreate(['desc_gred' => 'Gred Jasamu']);

    return Pegawai::query()->create(array_merge([
        'nama' => 'Walk-in '.$noKp,
        'no_kp' => $noKp,
        'ptj_id' => $ptj->id,
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
        'rsvp' => false,
        'no_kerusi' => null,
        'is_attend' => false,
        's_kehadiran' => Pegawai::S_KEHADIRAN_PAGI,
    ], $overrides));
}

it('seats a jasamu walk-in at their late calling number and assigns the table', function (): void {
    walkInSesi();
    $pegawai = walkInPegawai('900101010001');

    $this->actingAs(adminUser())
        ->putJson(route('admin.kehadiran.verify', $pegawai))
        ->assertOk()
        ->assertJsonPath('no_panggilan_lewat', 501)
        ->assertJsonPath('no_kerusi', 501)
        ->assertJsonPath('no_meja', 51);

    $fresh = $pegawai->fresh();
    expect($fresh->no_panggilan_lewat)->toBe(501)
        ->and($fresh->no_kerusi)->toBe(501)
        ->and($fresh->no_meja)->toBe(51)
        ->and($fresh->is_late)->toBeTrue();
});

it('continues seats and tables for later jasamu walk-ins', function (): void {
    walkInSesi();
    $admin = adminUser();

    $walkIns = collect(range(1, 11))
        ->map(fn (int $i) => walkInPegawai(sprintf('9001010100%02d', $i)));

    foreach ($walkIns as $pegawai) {
        $this->actingAs($admin)->putJson(route('admin.kehadiran.verify', $pegawai))->assertOk();
    }

    $second = $walkIns[1]->fresh();
    $eleventh = $walkIns[10]->fresh();

    expect($second->no_kerusi)->toBe(502)
        ->and($second->no_meja)->toBe(51)
        ->and($eleventh->no_kerusi)->toBe(511)
        ->and($eleventh->no_meja)->toBe(52);
});

it('keeps apc walk-ins without seat and table', function (): void {
    app(EventModeService::class)->set(EventModeService::MODE_APC);
    walkInSesi();
    $pegawai = walkInPegawai('900101010001', ['no_kerusi' => 99]);

    $this->actingAs(adminUser())
        ->putJson(route('admin.kehadiran.verify', $pegawai))
        ->assertOk()
        ->assertJsonPath('no_panggilan_lewat', 501);

    $fresh = $pegawai->fresh();
    expect($fresh->no_kerusi)->toBeNull()
        ->and($fresh->no_meja)->toBeNull();
});

it('releases the jasamu walk-in seat when attendance is cancelled', function (): void {
    walkInSesi();
    $pegawai = walkInPegawai('900101010001');
    $admin = adminUser();

    $this->actingAs($admin)->putJson(route('admin.kehadiran.verify', $pegawai), ['is_attend' => 1])->assertOk();
    $this->actingAs($admin)->putJson(route('admin.kehadiran.verify', $pegawai), ['is_attend' => 0])->assertOk();

    $fresh = $pegawai->fresh();
    expect($fresh->is_attend)->toBeFalse()
        ->and($fresh->no_panggilan_lewat)->toBeNull()
        ->and($fresh->no_kerusi)->toBeNull()
        ->and($fresh->no_meja)->toBeNull();
});

it('previews the jasamu walk-in seat and table before confirming', function (): void {
    walkInSesi();
    $pegawai = walkInPegawai('900101010001');

    $this->actingAs(adminUser())
        ->getJson(route('admin.kehadiran.details', $pegawai))
        ->assertOk()
        ->assertJsonPath('pegawai.no_panggilan_lewat', 501)
        ->assertJsonPath('pegawai.no_kerusi', 501)
        ->assertJsonPath('pegawai.no_meja', 51);

    $this->actingAs(plainUser())
        ->postJson(route('user.kiosk.lookup'), ['no_kp' => '900101010001'])
        ->assertOk()
        ->assertJsonPath('pegawai.no_kerusi', 501)
        ->assertJsonPath('pegawai.no_meja', 51);
});

it('shows the jasamu walk-in seat and table in the report late section', function (): void {
    $sesi = walkInSesi();
    $pegawai = walkInPegawai('900101010001');
    $admin = adminUser();

    $this->actingAs($admin)->putJson(route('admin.kehadiran.verify', $pegawai))->assertOk();

    $this->actingAs($admin)
        ->getJson(route('admin.report.datatable', ['sesi_id' => $sesi->id, 'section' => 'late', 'draw' => 1]))
        ->assertOk()
        ->assertJsonPath('data.0.no_kerusi', '501')
        ->assertJsonPath('data.0.no_meja', '51');
});

it('backfills seat and table for jasamu walk-ins attended before the change', function (): void {
    $sesi = walkInSesi(['seat_offset' => 500]);
    $walkIn = walkInPegawai('900101010001', [
        'is_attend' => true,
        'is_late' => true,
        'sesi_majlis_id' => $sesi->id,
        'no_panggilan_lewat' => 512,
    ]);
    $rsvp = walkInPegawai('900101010002', [
        'rsvp' => true,
        'is_attend' => true,
        'sesi_majlis_id' => $sesi->id,
        'no_panggilan_lewat' => 513,
    ]);

    (require database_path('migrations/2026_10_13_000000_backfill_jasamu_walk_in_seats_on_pegawais_table.php'))->up();

    expect($walkIn->fresh()->no_kerusi)->toBe(512)
        ->and($walkIn->fresh()->no_meja)->toBe(2)
        ->and($rsvp->fresh()->no_kerusi)->toBeNull();
});
