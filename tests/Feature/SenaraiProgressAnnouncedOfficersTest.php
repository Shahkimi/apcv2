<?php

declare(strict_types=1);

use App\Models\AnnouncedOfficer;
use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Pegawai;
use App\Models\Ptj;
use App\Models\SesiMajlis;
use App\Services\Kehadiran\SenaraiProgressService;

it('stores announced officers in rows and deduplicates by pegawai per sesi scope', function (): void {
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Senarai']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Jawatan Senarai']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Senarai']);
    $sesi = SesiMajlis::query()->create([
        'sesi' => 'Sesi Senarai',
        'is_active' => true,
        'is_late' => false,
        'countdown_start_late' => null,
        'seat_offset' => 0,
        's_kehadiran' => SesiMajlis::S_KEHADIRAN_PAGI,
    ]);

    $p1 = Pegawai::query()->create([
        'nama' => 'Pegawai Satu',
        'no_kp' => '900101015001',
        'ptj_id' => $ptj->id,
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
        'sesi_majlis_id' => $sesi->id,
        'rsvp' => true,
        'no_kerusi' => 1,
        'no_meja' => 1,
        'no_panggilan_lewat' => 0,
        'is_attend' => true,
        'is_late' => false,
        's_kehadiran' => Pegawai::S_KEHADIRAN_PAGI,
    ]);

    $p2 = Pegawai::query()->create([
        'nama' => 'Pegawai Dua',
        'no_kp' => '900101015002',
        'ptj_id' => $ptj->id,
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
        'sesi_majlis_id' => $sesi->id,
        'rsvp' => true,
        'no_kerusi' => 2,
        'no_meja' => 1,
        'no_panggilan_lewat' => 0,
        'is_attend' => true,
        'is_late' => false,
        's_kehadiran' => Pegawai::S_KEHADIRAN_PAGI,
    ]);

    $service = app(SenaraiProgressService::class);
    $sesiId = (int) $sesi->id;
    $scopeKey = 'sesi:'.$sesiId;

    $service->updateProgress($sesiId, 0, $p1->id, 10);
    $service->updateProgress($sesiId, 1, $p1->id, 10);

    expect(AnnouncedOfficer::query()->where('scope_key', $scopeKey)->count())->toBe(1);

    $service->updateProgress($sesiId, 2, $p2->id, 10);

    expect(AnnouncedOfficer::query()->where('scope_key', $scopeKey)->count())->toBe(2);

    $announced = $service->getAnnouncedOfficers($sesiId);

    expect($announced)->toHaveCount(2)
        ->and($announced->pluck('id')->all())->toBe([$p1->id, $p2->id])
        ->and($announced->first()['nama'])->toBe('Pegawai Satu');

    $progress = $service->getProgress($sesiId, 10);

    expect($progress['announced_count'])->toBe(2)
        ->and($progress['current_index'])->toBe(2);
});
