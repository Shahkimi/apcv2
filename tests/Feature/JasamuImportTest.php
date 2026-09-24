<?php

declare(strict_types=1);

use App\Models\Bersara;
use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Pegawai;
use App\Models\Ptj;
use App\Services\DatabaseImportService;
use App\Services\EventModeService;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

it('imports jasamu fields with mixed date formats', function (): void {
    app(EventModeService::class)->set(EventModeService::MODE_JASAMU);
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Ujian']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Jawatan Ujian']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);
    $bersara = Bersara::query()->create(['jenis_bersara' => 'Wajib']);

    $csv = "Nama,KP,PTJ,Jawatan,Gred,TarikhBersara,BersaraId,Tempoh\n"
        ."A,800101011111,{$ptj->id},{$jawatan->id},{$gred->id},31122026,{$bersara->id},25\n"
        ."B,800101012222,{$ptj->id},{$jawatan->id},{$gred->id},15/01/2027,{$bersara->id},30\n";

    $this->actingAs(adminUser());
    $this->post(route('admin.kawalan.database.upload'), [
        'file' => UploadedFile::fake()->createWithContent('jasamu.csv', $csv),
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
    $emptyPolicy = array_fill_keys(DatabaseImportService::optionalPolicyFieldsFor(EventModeService::MODE_JASAMU), DatabaseImportService::POLICY_ZERO);

    $this->postJson(route('admin.kawalan.database.preview'), [
        'mapping' => $mapping,
        'empty_policy' => $emptyPolicy,
    ])->assertOk()->assertJsonPath('errors', []);

    $this->postJson(route('admin.kawalan.database.import'), [
        'mapping' => $mapping,
        'empty_policy' => $emptyPolicy,
    ])->assertOk()->assertJsonPath('imported', 2);

    $this->assertDatabaseHas('pegawais', [
        'no_kp' => '800101011111',
        'tarikh_bersara' => '2026-12-31',
        'bersara_id' => $bersara->id,
        'tempoh_berkhidmat' => 25,
    ]);
    $this->assertDatabaseHas('pegawais', [
        'no_kp' => '800101012222',
        'tarikh_bersara' => '2027-01-15',
        'tempoh_berkhidmat' => 30,
    ]);
});

it('rejects unknown bersara_id and an invalid tarikh_bersara in preview', function (): void {
    app(EventModeService::class)->set(EventModeService::MODE_JASAMU);
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Ujian']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Jawatan Ujian']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);

    $csv = "Nama,KP,PTJ,Jawatan,Gred,TarikhBersara,BersaraId,Tempoh\n"
        ."A,800101011111,{$ptj->id},{$jawatan->id},{$gred->id},99999999,9999,25\n";

    $this->actingAs(adminUser());
    $this->post(route('admin.kawalan.database.upload'), [
        'file' => UploadedFile::fake()->createWithContent('bad-jasamu.csv', $csv),
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
    $emptyPolicy = array_fill_keys(DatabaseImportService::optionalPolicyFieldsFor(EventModeService::MODE_JASAMU), DatabaseImportService::POLICY_ZERO);

    $errors = $this->postJson(route('admin.kawalan.database.preview'), [
        'mapping' => $mapping,
        'empty_policy' => $emptyPolicy,
    ])->assertOk()->json('errors');

    expect($errors)->not->toBeEmpty();
    expect(Pegawai::query()->count())->toBe(0);
});

it('rejects preview when tarikh_bersara mapping is missing in jasamu mode', function (): void {
    app(EventModeService::class)->set(EventModeService::MODE_JASAMU);
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Ujian']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Jawatan Ujian']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);
    $bersara = Bersara::query()->create(['jenis_bersara' => 'Wajib']);

    $csv = "Nama,KP,PTJ,Jawatan,Gred,BersaraId,Tempoh\n"
        ."A,800101011111,{$ptj->id},{$jawatan->id},{$gred->id},{$bersara->id},25\n";

    $this->actingAs(adminUser());
    $this->post(route('admin.kawalan.database.upload'), [
        'file' => UploadedFile::fake()->createWithContent('missing-mapping.csv', $csv),
    ])->assertOk();

    $mapping = array_fill_keys(DatabaseImportService::fillableFieldsFor(EventModeService::MODE_JASAMU), '');
    $mapping['nama'] = 'Nama';
    $mapping['no_kp'] = 'KP';
    $mapping['ptj_id'] = 'PTJ';
    $mapping['jawatan_id'] = 'Jawatan';
    $mapping['gred_id'] = 'Gred';
    $mapping['bersara_id'] = 'BersaraId';
    $mapping['tempoh_berkhidmat'] = 'Tempoh';
    // tarikh_bersara left unmapped.
    $emptyPolicy = array_fill_keys(DatabaseImportService::optionalPolicyFieldsFor(EventModeService::MODE_JASAMU), DatabaseImportService::POLICY_ZERO);

    $this->postJson(route('admin.kawalan.database.preview'), [
        'mapping' => $mapping,
        'empty_policy' => $emptyPolicy,
    ])->assertStatus(422)->assertJsonValidationErrors(['mapping.tarikh_bersara']);
});

it('ignores jasamu fields when event mode is apc', function (): void {
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Ujian']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Jawatan Ujian']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);

    $csv = "Nama,KP,PTJ,Jawatan,Gred\n"
        ."A,800101011111,{$ptj->id},{$jawatan->id},{$gred->id}\n";

    $this->actingAs(adminUser());
    $this->post(route('admin.kawalan.database.upload'), [
        'file' => UploadedFile::fake()->createWithContent('apc.csv', $csv),
    ])->assertOk();

    $mapping = array_fill_keys(DatabaseImportService::PEGAWAI_FILLABLE, '');
    $mapping['nama'] = 'Nama';
    $mapping['no_kp'] = 'KP';
    $mapping['ptj_id'] = 'PTJ';
    $mapping['jawatan_id'] = 'Jawatan';
    $mapping['gred_id'] = 'Gred';
    $emptyPolicy = array_fill_keys(DatabaseImportService::OPTIONAL_POLICY_FIELDS, DatabaseImportService::POLICY_ZERO);

    $this->postJson(route('admin.kawalan.database.import'), [
        'mapping' => $mapping,
        'empty_policy' => $emptyPolicy,
    ])->assertOk()->assertJsonPath('imported', 1);

    $pegawai = Pegawai::query()->where('no_kp', '800101011111')->firstOrFail();
    expect($pegawai->tarikh_bersara)->toBeNull();
    expect($pegawai->bersara_id)->toBeNull();
    expect($pegawai->tempoh_berkhidmat)->toBeNull();
});

it('imports tarikh_bersara from an excel date cell', function (): void {
    app(EventModeService::class)->set(EventModeService::MODE_JASAMU);
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Ujian']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Jawatan Ujian']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);
    $bersara = Bersara::query()->create(['jenis_bersara' => 'Wajib']);

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
        ['Nama', 'KP', 'PTJ', 'Jawatan', 'Gred', 'TarikhBersara', 'BersaraId', 'Tempoh'],
        ['Karim Bin Test', '850101011234', $ptj->id, $jawatan->id, $gred->id, null, $bersara->id, 25],
    ]);
    $excelSerial = ExcelDate::PHPToExcel(new DateTime('2026-12-31'));
    $sheet->setCellValue('F2', $excelSerial);
    $sheet->getStyle('F2')->getNumberFormat()->setFormatCode('dd/mm/yyyy');

    $tmpPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('jasamu_xlsx_', true).'.xlsx';
    (new Xlsx($spreadsheet))->save($tmpPath);

    try {
        $this->actingAs(adminUser());
        $this->post(route('admin.kawalan.database.upload'), [
            'file' => new UploadedFile(
                $tmpPath,
                'pegawai.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            ),
        ])->assertOk();
    } finally {
        @unlink($tmpPath);
    }

    $mapping = array_fill_keys(DatabaseImportService::fillableFieldsFor(EventModeService::MODE_JASAMU), '');
    $mapping['nama'] = 'Nama';
    $mapping['no_kp'] = 'KP';
    $mapping['ptj_id'] = 'PTJ';
    $mapping['jawatan_id'] = 'Jawatan';
    $mapping['gred_id'] = 'Gred';
    $mapping['tarikh_bersara'] = 'TarikhBersara';
    $mapping['bersara_id'] = 'BersaraId';
    $mapping['tempoh_berkhidmat'] = 'Tempoh';
    $emptyPolicy = array_fill_keys(DatabaseImportService::optionalPolicyFieldsFor(EventModeService::MODE_JASAMU), DatabaseImportService::POLICY_ZERO);

    $this->postJson(route('admin.kawalan.database.import'), [
        'mapping' => $mapping,
        'empty_policy' => $emptyPolicy,
    ])->assertOk()->assertJsonPath('imported', 1);

    $this->assertDatabaseHas('pegawais', [
        'no_kp' => '850101011234',
        'tarikh_bersara' => '2026-12-31',
        'tempoh_berkhidmat' => 25,
    ]);
});
