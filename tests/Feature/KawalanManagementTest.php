<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Pegawai;
use App\Models\Ptj;
use App\Models\User;
use App\Services\DatabaseImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class KawalanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_kawalan_ptj_index(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user)
            ->get(route('admin.kawalan.ptj.index'))
            ->assertForbidden();
    }

    public function test_admin_can_access_kawalan_ptj_index(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.kawalan.ptj.index'))
            ->assertOk();
    }

    public function test_admin_can_access_kawalan_meja_index(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.kawalan.meja.index'))
            ->assertOk();
    }

    public function test_admin_can_access_kawalan_sesi_majlis_index(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.kawalan.sesi-majlis.index'))
            ->assertOk();
    }

    public function test_admin_can_access_kawalan_database_import_index(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.kawalan.database.index'))
            ->assertOk();
    }

    public function test_admin_can_import_pegawai_csv_with_mapping(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Ujian']);
        $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Jawatan Ujian']);
        $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);

        $csv = "Nama Pegawai,No Kad Pengenalan,idptj,idjawatan,idgred,rvsp,nokerusi,nosijil\n"
            ."Ali Bin Test,800101011234,{$ptj->id},{$jawatan->id},{$gred->id},1,15,99\n";

        $this->actingAs($admin);
        $upload = $this->post(route('admin.kawalan.database.upload'), [
            'file' => UploadedFile::fake()->createWithContent('pegawai.csv', $csv),
        ]);
        $upload->assertOk()->assertJsonStructure(['headers', 'row_count']);

        $mapping = array_fill_keys(DatabaseImportService::PEGAWAI_FILLABLE, '');
        $mapping['nama'] = 'Nama Pegawai';
        $mapping['no_kp'] = 'No Kad Pengenalan';
        $mapping['ptj_id'] = 'idptj';
        $mapping['jawatan_id'] = 'idjawatan';
        $mapping['gred_id'] = 'idgred';
        $mapping['rsvp'] = 'rvsp';
        $mapping['no_kerusi'] = 'nokerusi';
        $mapping['no_sijil'] = 'nosijil';

        $emptyPolicy = array_fill_keys(DatabaseImportService::OPTIONAL_POLICY_FIELDS, DatabaseImportService::POLICY_ZERO);

        $preview = $this->postJson(route('admin.kawalan.database.preview'), [
            'mapping' => $mapping,
            'empty_policy' => $emptyPolicy,
        ]);
        $preview->assertOk()->assertJsonPath('errors', []);

        $commit = $this->postJson(route('admin.kawalan.database.import'), [
            'mapping' => $mapping,
            'empty_policy' => $emptyPolicy,
        ]);
        $commit->assertOk()->assertJsonPath('imported', 1);

        $this->assertDatabaseHas('pegawais', [
            'nama' => 'Ali Bin Test',
            'no_kp' => '800101011234',
            'ptj_id' => $ptj->id,
            'jawatan_id' => $jawatan->id,
            'gred_id' => $gred->id,
            'no_kerusi' => 15,
            'no_sijil' => 99,
        ]);
    }

    public function test_import_rejects_duplicate_no_kp_in_csv(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Ujian']);
        $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Jawatan Ujian']);
        $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);

        $csv = "Nama Pegawai,No Kad Pengenalan,idptj,idjawatan,idgred\n"
            ."A,800101011234,{$ptj->id},{$jawatan->id},{$gred->id}\n"
            ."B,800101011234,{$ptj->id},{$jawatan->id},{$gred->id}\n";

        $this->actingAs($admin);
        $this->post(route('admin.kawalan.database.upload'), [
            'file' => UploadedFile::fake()->createWithContent('dup.csv', $csv),
        ])->assertOk();

        $mapping = array_fill_keys(DatabaseImportService::PEGAWAI_FILLABLE, '');
        $mapping['nama'] = 'Nama Pegawai';
        $mapping['no_kp'] = 'No Kad Pengenalan';
        $mapping['ptj_id'] = 'idptj';
        $mapping['jawatan_id'] = 'idjawatan';
        $mapping['gred_id'] = 'idgred';
        $emptyPolicy = array_fill_keys(DatabaseImportService::OPTIONAL_POLICY_FIELDS, DatabaseImportService::POLICY_ZERO);

        $errors = $this->postJson(route('admin.kawalan.database.preview'), [
            'mapping' => $mapping,
            'empty_policy' => $emptyPolicy,
        ])->assertOk()->json('errors');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsStringIgnoringCase('pendua', implode(' ', $errors));
        $this->assertSame(0, Pegawai::query()->count());
    }

    public function test_admin_can_import_pegawai_xlsx_with_mapping(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Xlsx']);
        $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Jawatan Xlsx']);
        $gred = Gred::query()->create(['desc_gred' => 'Gred Xlsx']);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Nama Pegawai', 'No Kad Pengenalan', 'idptj', 'idjawatan', 'idgred', 'rvsp', 'nokerusi', 'nosijil'],
            ['Siti Binti Xlsx', '910303031234', $ptj->id, $jawatan->id, $gred->id, 1, 20, 88],
        ]);

        $tmpPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('pegawai_xlsx_', true).'.xlsx';
        (new Xlsx($spreadsheet))->save($tmpPath);

        try {
            $this->actingAs($admin);
            $upload = $this->post(route('admin.kawalan.database.upload'), [
                'file' => new UploadedFile(
                    $tmpPath,
                    'pegawai.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true
                ),
            ]);
            $upload->assertOk()->assertJsonStructure(['headers', 'row_count']);
        } finally {
            @unlink($tmpPath);
        }

        $mapping = array_fill_keys(DatabaseImportService::PEGAWAI_FILLABLE, '');
        $mapping['nama'] = 'Nama Pegawai';
        $mapping['no_kp'] = 'No Kad Pengenalan';
        $mapping['ptj_id'] = 'idptj';
        $mapping['jawatan_id'] = 'idjawatan';
        $mapping['gred_id'] = 'idgred';
        $mapping['rsvp'] = 'rvsp';
        $mapping['no_kerusi'] = 'nokerusi';
        $mapping['no_sijil'] = 'nosijil';
        $emptyPolicy = array_fill_keys(DatabaseImportService::OPTIONAL_POLICY_FIELDS, DatabaseImportService::POLICY_ZERO);

        $this->postJson(route('admin.kawalan.database.preview'), [
            'mapping' => $mapping,
            'empty_policy' => $emptyPolicy,
        ])->assertOk()->assertJsonPath('errors', []);

        $this->postJson(route('admin.kawalan.database.import'), [
            'mapping' => $mapping,
            'empty_policy' => $emptyPolicy,
        ])->assertOk()->assertJsonPath('imported', 1);

        $this->assertDatabaseHas('pegawais', [
            'nama' => 'Siti Binti Xlsx',
            'no_kp' => '910303031234',
            'ptj_id' => $ptj->id,
            'jawatan_id' => $jawatan->id,
            'gred_id' => $gred->id,
            'no_kerusi' => 20,
            'no_sijil' => 88,
        ]);
    }
}
