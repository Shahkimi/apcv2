<?php

declare(strict_types=1);

use App\Models\Bersara;
use App\Models\Meja;
use App\Services\EventModeService;

beforeEach(function (): void {
    Meja::query()->create(['sizing' => 10]);
});

it('returns jasamu fields in kehadiran details when event mode is jasamu', function (): void {
    app(EventModeService::class)->set(EventModeService::MODE_JASAMU);
    $admin = adminUser();
    $bersara = Bersara::query()->create(['jenis_bersara' => 'Wajib']);
    $pegawai = createPegawaiForTest();
    $pegawai->update([
        'tarikh_bersara' => '2026-12-31',
        'bersara_id' => $bersara->id,
        'tempoh_berkhidmat' => 28,
    ]);

    $this->actingAs($admin)
        ->getJson(route('admin.kehadiran.details', $pegawai))
        ->assertOk()
        ->assertJsonPath('event_mode', 'jasamu')
        ->assertJsonPath('pegawai.tarikh_bersara', '31/12/2026')
        ->assertJsonPath('pegawai.bersara_name', 'Wajib')
        ->assertJsonPath('pegawai.tempoh_berkhidmat', 28);
});

it('shows the persaraan column in debug paparan when event mode is jasamu', function (): void {
    app(EventModeService::class)->set(EventModeService::MODE_JASAMU);
    $admin = adminUser();
    $bersara = Bersara::query()->create(['jenis_bersara' => 'Wajib']);
    $sesi = activeSesiForKehadiran();
    $pegawai = createPegawaiForTest();
    $pegawai->update([
        'is_attend' => true,
        'hadir_at' => now(),
        'sesi_majlis_id' => $sesi->id,
        'tarikh_bersara' => '2026-12-31',
        'bersara_id' => $bersara->id,
        'tempoh_berkhidmat' => 28,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.paparan.index'))
        ->assertOk()
        ->assertSee('Persaraan');

    $response = $this->actingAs($admin)
        ->getJson(route('admin.paparan.datatable').'?'.paparanDatatableQuery())
        ->assertOk();

    expect($response->json('data.0.persaraan'))->toContain('31/12/2026');
});

it('hides the persaraan column in debug paparan when event mode is apc', function (): void {
    $admin = adminUser();

    $this->actingAs($admin)
        ->get(route('admin.paparan.index'))
        ->assertOk()
        ->assertDontSee('Persaraan');
});

it('presents jasamu fields on the presentation slide screen when event mode is jasamu', function (): void {
    app(EventModeService::class)->set(EventModeService::MODE_JASAMU);
    $media = mediaUser();
    $bersara = Bersara::query()->create(['jenis_bersara' => 'Wajib']);
    $sesi = activeSesiForKehadiran();
    $pegawai = createPegawaiForTest();
    $pegawai->update([
        'is_attend' => true,
        'sesi_majlis_id' => $sesi->id,
        'tarikh_bersara' => '2026-12-31',
        'bersara_id' => $bersara->id,
        'tempoh_berkhidmat' => 28,
    ]);

    $this->actingAs($media)
        ->get(route('media.senarai.present'))
        ->assertOk()
        ->assertSee('id="officer-tarikh"', false)
        ->assertDontSee('id="officer-ptj"', false);
});

it('presents ptj on the presentation slide screen when event mode is apc', function (): void {
    $media = mediaUser();
    $sesi = activeSesiForKehadiran();
    $pegawai = createPegawaiForTest();
    $pegawai->update(['is_attend' => true, 'sesi_majlis_id' => $sesi->id]);

    $this->actingAs($media)
        ->get(route('media.senarai.present'))
        ->assertOk()
        ->assertSee('id="officer-ptj"', false)
        ->assertDontSee('id="officer-tarikh"', false);
});

it('includes jasamu columns in report datatable and preview when event mode is jasamu', function (): void {
    app(EventModeService::class)->set(EventModeService::MODE_JASAMU);
    $admin = adminUser();
    $bersara = Bersara::query()->create(['jenis_bersara' => 'Wajib']);
    $sesi = activeSesiForKehadiran();
    $pegawai = createPegawaiForTest();
    $pegawai->update([
        'sesi_majlis_id' => $sesi->id,
        'is_late' => false,
        'tarikh_bersara' => '2026-12-31',
        'bersara_id' => $bersara->id,
        'tempoh_berkhidmat' => 28,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.report.preview', ['sesi_id' => $sesi->id]))
        ->assertOk()
        ->assertSee('Tarikh Bersara');

    $response = $this->actingAs($admin)
        ->getJson(route('admin.report.datatable', ['sesi_id' => $sesi->id, 'section' => 'ontime']))
        ->assertOk();

    expect($response->json('data.0.tarikh_bersara'))->toBe('31/12/2026');
    expect($response->json('data.0.bersara_name'))->toBe('Wajib');
});

it('downloads the report pdf successfully', function (): void {
    $admin = adminUser();
    $sesi = activeSesiForKehadiran();
    $pegawai = createPegawaiForTest();
    $pegawai->update(['sesi_majlis_id' => $sesi->id, 'is_late' => false]);

    $response = $this->actingAs($admin)
        ->get(route('admin.report.download', ['sesi_id' => $sesi->id]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});
