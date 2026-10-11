<?php

declare(strict_types=1);

use App\Models\AnnouncedOfficer;
use App\Models\Bersara;
use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Pegawai;
use App\Models\Ptj;
use App\Models\SesiMajlis;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function seedPegawaiWithLookups(): array
{
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Ujian']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Lama A']);
    Jawatan::query()->create(['desc_jawatan' => 'Lama B']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);
    $bersara = Bersara::query()->create(['jenis_bersara' => 'Lama']);

    $pegawai = Pegawai::query()->create([
        'nama' => 'Ahmad Ujian',
        'no_kp' => '900101011234',
        'ptj_id' => $ptj->id,
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
        'bersara_id' => $bersara->id,
        'rsvp' => true,
        's_kehadiran' => Pegawai::S_KEHADIRAN_PAGI,
    ]);

    return [$pegawai, $jawatan, $bersara];
}

function uploadLookup(string $dataset, string $csv, string $name = 'data.csv'): void
{
    test()->post(route('admin.kawalan.database.lookup.upload', $dataset), [
        'file' => UploadedFile::fake()->createWithContent($name, $csv),
    ])->assertOk();
}

function lookupBody(string $column, string $header, ?string $confirm = null): array
{
    return array_filter([
        'mapping' => [$column => $header],
        'confirm' => $confirm,
    ], static fn ($v) => $v !== null);
}

it('replaces jawatan, restarts ids and wipes pegawai plus announced rows', function (): void {
    [$pegawai] = seedPegawaiWithLookups();
    $sesi = SesiMajlis::query()->create(['sesi' => 'S', 'is_active' => false, 'is_late' => false, 'seat_offset' => 0, 's_kehadiran' => 0]);
    AnnouncedOfficer::query()->create(['scope_key' => 'x', 'sesi_majlis_id' => $sesi->id, 'pegawai_id' => $pegawai->id, 'announced_at' => now()]);

    $this->actingAs(adminUser());
    uploadLookup('jawatan', "Jawatan\nPegawai Tadbir\nJuruteknik\nPenolong Pegawai\n");

    $this->postJson(route('admin.kawalan.database.lookup.preview', 'jawatan'), lookupBody('desc_jawatan', 'Jawatan'))
        ->assertOk()
        ->assertJsonPath('errors', [])
        ->assertJsonPath('preview.0.id', 1)
        ->assertJsonPath('preview.2.name', 'Penolong Pegawai')
        ->assertJsonPath('impact.existing', 2)
        ->assertJsonPath('impact.pegawai', 1)
        ->assertJsonPath('impact.deletes_pegawai', true);

    // preview alone changes nothing
    expect(Jawatan::query()->count())->toBe(2)->and(Pegawai::query()->count())->toBe(1);

    $this->postJson(route('admin.kawalan.database.lookup.import', 'jawatan'), lookupBody('desc_jawatan', 'Jawatan', 'RESET'))
        ->assertOk()
        ->assertJsonPath('imported', 3)
        ->assertJsonPath('replaced', 2)
        ->assertJsonPath('pegawai', 1);

    expect(Jawatan::query()->orderBy('id')->pluck('desc_jawatan', 'id')->all())
        ->toBe([1 => 'Pegawai Tadbir', 2 => 'Juruteknik', 3 => 'Penolong Pegawai']);
    expect(Pegawai::query()->count())->toBe(0);
    expect(AnnouncedOfficer::query()->count())->toBe(0);

    // next record created through the normal UI continues at N+1
    expect(Jawatan::query()->create(['desc_jawatan' => 'Baharu'])->id)->toBe(4);
});

it('replaces bersara and keeps pegawai with a cleared bersara link', function (): void {
    [$pegawai] = seedPegawaiWithLookups();

    $this->actingAs(adminUser());
    uploadLookup('bersara', "jenis_bersara\nWajib\nPilihan\n");

    $this->postJson(route('admin.kawalan.database.lookup.import', 'bersara'), lookupBody('jenis_bersara', 'jenis_bersara', 'RESET'))
        ->assertOk()
        ->assertJsonPath('imported', 2)
        ->assertJsonPath('deletes_pegawai', false)
        ->assertJsonPath('pegawai', 1);

    expect(Bersara::query()->pluck('jenis_bersara', 'id')->all())->toBe([1 => 'Wajib', 2 => 'Pilihan']);
    expect($pegawai->fresh())->not->toBeNull()->and($pegawai->fresh()->bersara_id)->toBeNull();
});

it('rejects duplicate and empty names with row errors and changes nothing', function (): void {
    seedPegawaiWithLookups();

    $this->actingAs(adminUser());
    uploadLookup('jawatan', "Jawatan,Catatan\nPegawai,a\npegawai,b\n,c\nKeranian,d\n");

    $res = $this->postJson(route('admin.kawalan.database.lookup.preview', 'jawatan'), lookupBody('desc_jawatan', 'Jawatan'))
        ->assertOk();
    expect($res->json('errors'))->toHaveCount(2);
    expect($res->json('errors.0'))->toContain('Baris 3')->toContain('pendua');

    $this->postJson(route('admin.kawalan.database.lookup.import', 'jawatan'), lookupBody('desc_jawatan', 'Jawatan', 'RESET'))
        ->assertStatus(422);

    expect(Jawatan::query()->count())->toBe(2)->and(Pegawai::query()->count())->toBe(1);
});

it('requires the RESET confirmation word on import', function (): void {
    seedPegawaiWithLookups();

    $this->actingAs(adminUser());
    uploadLookup('jawatan', "Jawatan\nBaharu\n");

    $this->postJson(route('admin.kawalan.database.lookup.import', 'jawatan'), lookupBody('desc_jawatan', 'Jawatan'))
        ->assertStatus(422)->assertJsonValidationErrors('confirm');
    $this->postJson(route('admin.kawalan.database.lookup.import', 'jawatan'), lookupBody('desc_jawatan', 'Jawatan', 'reset'))
        ->assertStatus(422)->assertJsonValidationErrors('confirm');

    expect(Jawatan::query()->count())->toBe(2)->and(Pegawai::query()->count())->toBe(1);
});

it('rolls back everything when the insert fails', function (): void {
    seedPegawaiWithLookups();

    $this->actingAs(adminUser());
    uploadLookup('jawatan', "Jawatan\nSatu\nDua\n");

    // force the table insert to fail after the delete has run
    DB::listen(static function ($query): void {
        if (str_starts_with(strtolower($query->sql), 'insert into "jawatans"')) {
            throw new RuntimeException('boom');
        }
    });

    $this->withoutExceptionHandling();
    expect(fn () => $this->postJson(route('admin.kawalan.database.lookup.import', 'jawatan'), lookupBody('desc_jawatan', 'Jawatan', 'RESET')))
        ->toThrow(RuntimeException::class);

    expect(Jawatan::query()->count())->toBe(2)->and(Pegawai::query()->count())->toBe(1);
});

it('returns 410 when the upload session is missing and 422 for an unknown column', function (): void {
    $this->actingAs(adminUser());

    $this->postJson(route('admin.kawalan.database.lookup.preview', 'jawatan'), lookupBody('desc_jawatan', 'Jawatan'))
        ->assertStatus(410);

    uploadLookup('jawatan', "Jawatan\nSatu\n");
    $this->postJson(route('admin.kawalan.database.lookup.preview', 'jawatan'), lookupBody('desc_jawatan', 'Tiada'))
        ->assertStatus(422)->assertJsonValidationErrors('mapping.desc_jawatan');
});

it('imports from an xlsx file', function (): void {
    $this->actingAs(adminUser());

    $sheet = new Spreadsheet;
    $sheet->getActiveSheet()->fromArray([['Jenis'], ['Wajib'], ['Pilihan'], ['Kesihatan']]);
    $path = tempnam(sys_get_temp_dir(), 'lk').'.xlsx';
    (new Xlsx($sheet))->save($path);

    $this->post(route('admin.kawalan.database.lookup.upload', 'bersara'), [
        'file' => new UploadedFile($path, 'bersara.xlsx', null, null, true),
    ])->assertOk()->assertJsonPath('row_count', 3);

    $this->postJson(route('admin.kawalan.database.lookup.import', 'bersara'), lookupBody('jenis_bersara', 'Jenis', 'RESET'))
        ->assertOk()->assertJsonPath('imported', 3);

    expect(Bersara::query()->count())->toBe(3);
});

it('serves a csv template and blocks non-admins and unknown datasets', function (): void {
    $this->actingAs(adminUser())
        ->get(route('admin.kawalan.database.lookup.template', 'bersara'))
        ->assertOk()
        ->assertDownload('templat-bersara.csv');

    $this->get('/admin/kawalan/database/ptj/template')->assertNotFound();

    auth()->logout();
    $this->actingAs(plainUser())
        ->postJson(route('admin.kawalan.database.lookup.preview', 'jawatan'), lookupBody('desc_jawatan', 'x'))
        ->assertForbidden();
});
