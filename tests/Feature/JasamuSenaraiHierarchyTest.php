<?php

declare(strict_types=1);

use App\Models\Bersara;
use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Pegawai;
use App\Models\Ptj;
use App\Models\SesiMajlis;
use App\Services\DatabaseImportService;
use App\Services\EventModeService;
use App\Services\Kehadiran\KehadiranCallingService;
use Illuminate\Http\UploadedFile;

/**
 * Attended mix: RSVP on-time + late, walk-in, OKU RSVP, OKU walk-in; plus one not attended.
 */
function seedSenaraiHierarchy(): SesiMajlis
{
    $sesi = SesiMajlis::query()->create([
        'sesi' => 'Sesi Hierarki',
        'is_active' => true,
        'is_late' => false,
        'countdown_start_late' => 501,
        'seat_offset' => 0,
        's_kehadiran' => SesiMajlis::S_KEHADIRAN_PAGI,
    ]);
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Hierarki']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Pegawai Hierarki']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Hierarki']);

    $make = function (string $nama, string $noKp, array $attrs) use ($sesi, $ptj, $jawatan, $gred): void {
        Pegawai::query()->create(array_merge([
            'nama' => $nama,
            'no_kp' => $noKp,
            'ptj_id' => $ptj->id,
            'jawatan_id' => $jawatan->id,
            'gred_id' => $gred->id,
            'sesi_majlis_id' => $sesi->id,
            'is_attend' => true,
            'is_late' => false,
            'is_oku' => false,
            's_kehadiran' => Pegawai::S_KEHADIRAN_PAGI,
        ], $attrs));
    };

    $make('Oku Walkin', '900101010005', ['rsvp' => false, 'is_oku' => true, 'is_late' => true, 'no_panggilan_lewat' => 503, 'no_kerusi' => 503]);
    $make('Walkin', '900101010003', ['rsvp' => false, 'is_late' => true, 'no_panggilan_lewat' => 502, 'no_kerusi' => 502]);
    $make('Oku Rsvp', '900101010004', ['rsvp' => true, 'is_oku' => true, 'no_kerusi' => 1]);
    $make('Rsvp Lewat', '900101010002', ['rsvp' => true, 'is_late' => true, 'no_panggilan_lewat' => 501, 'no_kerusi' => 7]);
    $make('Rsvp Tepat', '900101010001', ['rsvp' => true, 'no_kerusi' => 3]);
    $make('Tidak Hadir', '900101010006', ['rsvp' => true, 'is_attend' => false, 'no_kerusi' => 2]);

    return $sesi;
}

it('orders jasamu senarai as rsvp, then walk-in, then oku last', function (): void {
    app(EventModeService::class)->set(EventModeService::MODE_JASAMU);
    $sesi = seedSenaraiHierarchy();
    $expected = ['Rsvp Tepat', 'Rsvp Lewat', 'Walkin', 'Oku Rsvp', 'Oku Walkin'];

    expect(app(KehadiranCallingService::class)->attendedPegawaiForDisplay($sesi->id)->pluck('nama')->all())
        ->toBe($expected);

    $this->actingAs(mediaUser())
        ->get(route('media.senarai.present', ['sesi_id' => $sesi->id]))
        ->assertOk()
        ->assertViewHas('officerSlides', fn ($slides) => $slides->pluck('nama')->all() === $expected);
});

it('applies the same priority order on apc senarai', function (): void {
    $sesi = seedSenaraiHierarchy();

    $this->actingAs(mediaUser())
        ->get(route('media.senarai.present', ['sesi_id' => $sesi->id]))
        ->assertOk()
        ->assertViewHas('officerSlides', fn ($slides) => $slides->pluck('nama')->all() === [
            'Rsvp Tepat', 'Rsvp Lewat', 'Walkin', 'Oku Rsvp', 'Oku Walkin',
        ]);
});

it('imports is_oku from the jasamu mapping', function (): void {
    app(EventModeService::class)->set(EventModeService::MODE_JASAMU);
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Ujian']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Jawatan Ujian']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);
    $bersara = Bersara::query()->create(['jenis_bersara' => 'Wajib']);

    $csv = "Nama,KP,PTJ,Jawatan,Gred,TarikhBersara,BersaraId,Tempoh,OKU\n"
        ."A,800101011111,{$ptj->id},{$jawatan->id},{$gred->id},31122026,{$bersara->id},25,1\n"
        ."B,800101012222,{$ptj->id},{$jawatan->id},{$gred->id},31122026,{$bersara->id},30,\n";

    $this->actingAs(adminUser());
    $this->post(route('admin.kawalan.database.upload'), [
        'file' => UploadedFile::fake()->createWithContent('oku.csv', $csv),
    ])->assertOk();

    $mapping = array_fill_keys(DatabaseImportService::fillableFieldsFor(EventModeService::MODE_JASAMU), '');
    $mapping['nama'] = 'Nama';
    $mapping['no_kp'] = 'KP';
    $mapping['ptj_id'] = 'PTJ';
    $mapping['jawatan_id'] = 'Jawatan';
    $mapping['gred_id'] = 'Gred';
    $mapping['tarikh_bersara'] = 'TarikhBersara';
    $mapping['bersara_id'] = 'BersaraId';
    $mapping['tempoh_berkhidmat'] = 'Tempoh';
    $mapping['is_oku'] = 'OKU';
    $emptyPolicy = array_fill_keys(DatabaseImportService::optionalPolicyFieldsFor(EventModeService::MODE_JASAMU), DatabaseImportService::POLICY_ZERO);

    $this->postJson(route('admin.kawalan.database.import'), [
        'mapping' => $mapping,
        'empty_policy' => $emptyPolicy,
    ])->assertOk()->assertJsonPath('imported', 2);

    $this->assertDatabaseHas('pegawais', ['no_kp' => '800101011111', 'is_oku' => true]);
    $this->assertDatabaseHas('pegawais', ['no_kp' => '800101012222', 'is_oku' => false]);
});
