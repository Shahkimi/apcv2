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

it('takes jawatan ids from the mapped id column', function (): void {
    seedPegawaiWithLookups();
    $this->actingAs(adminUser());
    uploadLookup('jawatan', "id,Jawatan\n10,Pegawai Tadbir\n3,Juruteknik\n25,Penolong Pegawai\n");
    $body = ['mapping' => ['id' => 'id', 'desc_jawatan' => 'Jawatan']];

    $this->postJson(route('admin.kawalan.database.lookup.preview', 'jawatan'), $body)
        ->assertOk()
        ->assertJsonPath('errors', [])
        ->assertJsonPath('preview.0.id', 10)
        ->assertJsonPath('preview.1.id', 3);

    $this->postJson(route('admin.kawalan.database.lookup.import', 'jawatan'), $body + ['confirm' => 'RESET'])
        ->assertOk()
        ->assertJsonPath('imported', 3);

    expect(Jawatan::query()->orderBy('id')->pluck('desc_jawatan', 'id')->all())
        ->toBe([3 => 'Juruteknik', 10 => 'Pegawai Tadbir', 25 => 'Penolong Pegawai']);

    // Next id continues after the highest imported id.
    expect(Jawatan::query()->create(['desc_jawatan' => 'Baharu'])->id)->toBe(26);
});

it('reads jawatan ids from an xlsx file', function (): void {
    $this->actingAs(adminUser());

    $sheet = new Spreadsheet;
    $sheet->getActiveSheet()->fromArray([['ID', 'Nama Jawatan'], [7, 'Pegawai Tadbir'], [12, 'Juruteknik']]);
    $path = tempnam(sys_get_temp_dir(), 'lk').'.xlsx';
    (new Xlsx($sheet))->save($path);

    $this->post(route('admin.kawalan.database.lookup.upload', 'jawatan'), [
        'file' => new UploadedFile($path, 'jawatan.xlsx', null, null, true),
    ])->assertOk();

    $this->postJson(route('admin.kawalan.database.lookup.import', 'jawatan'), [
        'mapping' => ['id' => 'ID', 'desc_jawatan' => 'Nama Jawatan'],
        'confirm' => 'RESET',
    ])->assertOk();

    expect(Jawatan::query()->orderBy('id')->pluck('id')->all())->toBe([7, 12]);
});

it('rejects blank, invalid and duplicate jawatan ids and changes nothing', function (): void {
    seedPegawaiWithLookups();
    $this->actingAs(adminUser());
    uploadLookup('jawatan', "id,Jawatan\n10,Satu\n,Dua\nabc,Tiga\n0,Empat\n10,Lima\n1.5,Enam\n");
    $body = ['mapping' => ['id' => 'id', 'desc_jawatan' => 'Jawatan']];

    $errors = $this->postJson(route('admin.kawalan.database.lookup.preview', 'jawatan'), $body)
        ->assertOk()
        ->json('errors');

    expect($errors)->toHaveCount(5)
        ->and($errors[0])->toContain('Baris 3')->toContain('diperlukan')
        ->and($errors[1])->toContain("id 'abc' mesti integer positif")
        ->and($errors[2])->toContain("id '0' mesti integer positif")
        ->and($errors[3])->toContain("id '10' pendua (pertama pada baris 2)")
        ->and($errors[4])->toContain("id '1.5' mesti integer positif");

    $this->postJson(route('admin.kawalan.database.lookup.import', 'jawatan'), $body + ['confirm' => 'RESET'])
        ->assertStatus(422);

    expect(Jawatan::query()->count())->toBe(2)
        ->and(Pegawai::query()->count())->toBe(1);
});

it('validates the mapped id column', function (): void {
    $this->actingAs(adminUser());
    uploadLookup('jawatan', "id,Jawatan\n1,Satu\n");

    $this->postJson(route('admin.kawalan.database.lookup.preview', 'jawatan'), ['mapping' => ['id' => 'Tiada', 'desc_jawatan' => 'Jawatan']])
        ->assertStatus(422)
        ->assertJsonValidationErrors('mapping.id');

    $this->postJson(route('admin.kawalan.database.lookup.preview', 'jawatan'), ['mapping' => ['id' => 'Jawatan', 'desc_jawatan' => 'Jawatan']])
        ->assertStatus(422)
        ->assertJsonValidationErrors('mapping.id');
});

it('ignores an id mapping for bersara and keeps ids 1..N', function (): void {
    $this->actingAs(adminUser());
    uploadLookup('bersara', "id,Jenis\n40,Wajib\n41,Pilihan\n");

    $this->postJson(route('admin.kawalan.database.lookup.import', 'bersara'), [
        'mapping' => ['id' => 'id', 'jenis_bersara' => 'Jenis'],
        'confirm' => 'RESET',
    ])->assertOk();

    expect(Bersara::query()->orderBy('id')->pluck('id')->all())->toBe([1, 2]);
});

it('adds the id column to the jawatan template and mapping only', function (): void {
    $this->actingAs(adminUser());

    $jawatan = $this->get(route('admin.kawalan.database.lookup.template', 'jawatan'))->assertOk()->streamedContent();
    $bersara = $this->get(route('admin.kawalan.database.lookup.template', 'bersara'))->assertOk()->streamedContent();

    expect(explode("\n", ltrim($jawatan, "\u{FEFF}"))[0])->toBe('id,desc_jawatan')
        ->and(explode("\n", ltrim($bersara, "\u{FEFF}"))[0])->toBe('jenis_bersara');

    $this->get(route('admin.kawalan.database.index', ['dataset' => 'jawatan']))
        ->assertOk()
        ->assertSee('ID jawatan')
        ->assertViewHas('fillableFields', ['id', 'desc_jawatan']);

    $this->get(route('admin.kawalan.database.index', ['dataset' => 'bersara']))
        ->assertOk()
        ->assertViewHas('fillableFields', ['jenis_bersara']);
});

it('replaces gred with ids from the sheet and wipes pegawai', function (): void {
    [$pegawai] = seedPegawaiWithLookups();
    $sesi = SesiMajlis::query()->create(['sesi' => 'S', 'is_active' => false, 'is_late' => false, 'seat_offset' => 0, 's_kehadiran' => 0]);
    AnnouncedOfficer::query()->create(['scope_key' => 'x', 'sesi_majlis_id' => $sesi->id, 'pegawai_id' => $pegawai->id, 'announced_at' => now()]);

    $this->actingAs(adminUser());
    uploadLookup('gred', "id,Gred\n41,N41\n29,N29\n22,N22\n");
    $body = ['mapping' => ['id' => 'id', 'desc_gred' => 'Gred']];

    $this->postJson(route('admin.kawalan.database.lookup.preview', 'gred'), $body)
        ->assertOk()
        ->assertJsonPath('errors', [])
        ->assertJsonPath('preview.0.id', 41)
        ->assertJsonPath('impact.existing', 1)
        ->assertJsonPath('impact.pegawai', 1)
        ->assertJsonPath('impact.deletes_pegawai', true);

    $this->postJson(route('admin.kawalan.database.lookup.import', 'gred'), $body + ['confirm' => 'RESET'])
        ->assertOk()
        ->assertJsonPath('imported', 3)
        ->assertJsonPath('pegawai', 1);

    expect(Gred::query()->orderBy('id')->pluck('desc_gred', 'id')->all())->toBe([22 => 'N22', 29 => 'N29', 41 => 'N41'])
        ->and(Pegawai::query()->count())->toBe(0)
        ->and(AnnouncedOfficer::query()->count())->toBe(0)
        ->and(Jawatan::query()->count())->toBe(2)
        ->and(Bersara::query()->count())->toBe(1)
        ->and(Gred::query()->create(['desc_gred' => 'Baharu'])->id)->toBe(42);
});

it('numbers gred 1..N when no id column is mapped', function (): void {
    $this->actingAs(adminUser());
    uploadLookup('gred', "Gred\nN41\nN29\n");

    $this->postJson(route('admin.kawalan.database.lookup.import', 'gred'), lookupBody('desc_gred', 'Gred', 'RESET'))
        ->assertOk();

    expect(Gred::query()->orderBy('id')->pluck('desc_gred', 'id')->all())->toBe([1 => 'N41', 2 => 'N29']);
});

it('serves the gred template and mapping page', function (): void {
    $this->actingAs(adminUser());

    $csv = $this->get(route('admin.kawalan.database.lookup.template', 'gred'))
        ->assertOk()
        ->assertDownload('templat-gred.csv')
        ->streamedContent();
    expect(explode("\n", ltrim($csv, "\u{FEFF}"))[0])->toBe('id,desc_gred');

    $this->get(route('admin.kawalan.database.index', ['dataset' => 'gred']))
        ->assertOk()
        ->assertSee('Import fail — Gred', false)
        ->assertSee('ID gred')
        ->assertSee('gred_id')
        ->assertViewHas('fillableFields', ['id', 'desc_gred']);
});
