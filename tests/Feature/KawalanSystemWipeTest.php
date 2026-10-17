<?php

declare(strict_types=1);

use App\Models\AnnouncedOfficer;
use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Pegawai;
use App\Models\Ptj;
use App\Models\SesiMajlis;
use Illuminate\Support\Facades\DB;

function seedWipeData(): Pegawai
{
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Padam']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Jawatan A']);
    Jawatan::query()->create(['desc_jawatan' => 'Jawatan B']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred A']);
    $sesi = SesiMajlis::query()->create(['sesi' => 'Sesi Padam', 'is_active' => true, 'is_late' => false, 'seat_offset' => 0, 's_kehadiran' => 0]);

    $pegawai = Pegawai::query()->create([
        'nama' => 'Pegawai Padam',
        'no_kp' => '900101011234',
        'ptj_id' => $ptj->id,
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
        'sesi_majlis_id' => $sesi->id,
        'rsvp' => true,
        'is_attend' => true,
        's_kehadiran' => Pegawai::S_KEHADIRAN_PAGI,
    ]);

    AnnouncedOfficer::query()->create(['scope_key' => 'sesi:'.$sesi->id, 'sesi_majlis_id' => $sesi->id, 'pegawai_id' => $pegawai->id, 'announced_at' => now()]);
    DB::table('senarai_progress')->insert([
        'scope_key' => 'sesi:'.$sesi->id,
        'sesi_majlis_id' => $sesi->id,
        'current_index' => 5,
        'announced_officers' => json_encode([$pegawai->id]),
        'last_updated_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $pegawai;
}

it('redirects guests and forbids non-admins from wiping data', function (): void {
    $this->post(route('admin.kawalan.system.wipe'), ['tables' => ['pegawai']])->assertRedirect(route('login'));
    $this->actingAs(plainUser())->postJson(route('admin.kawalan.system.wipe'), ['tables' => ['pegawai']])->assertForbidden();
    $this->actingAs(mediaUser())->postJson(route('admin.kawalan.system.wipe'), ['tables' => ['pegawai']])->assertForbidden();
});

it('rejects an empty or unknown table selection', function (array $body): void {
    seedWipeData();

    $this->actingAs(adminUser())
        ->postJson(route('admin.kawalan.system.wipe'), $body)
        ->assertStatus(422);

    expect(Pegawai::query()->count())->toBe(1);
})->with([
    'missing' => [[]],
    'empty' => [['tables' => []]],
    'unknown' => [['tables' => ['ptj']]],
    'not an array' => [['tables' => 'pegawai']],
]);

it('wipes only pegawai and their senarai state', function (): void {
    seedWipeData();

    $this->actingAs(adminUser())
        ->postJson(route('admin.kawalan.system.wipe'), ['tables' => ['pegawai']])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('deleted', ['pegawai' => 1])
        ->assertJsonPath('counts', ['pegawai' => 0, 'jawatan' => 2, 'gred' => 1]);

    expect(Pegawai::query()->count())->toBe(0)
        ->and(AnnouncedOfficer::query()->count())->toBe(0)
        ->and(Jawatan::query()->count())->toBe(2)
        ->and(Gred::query()->count())->toBe(1)
        ->and(Ptj::query()->count())->toBe(1)
        ->and(SesiMajlis::query()->count())->toBe(1);

    $progress = DB::table('senarai_progress')->first();
    expect((int) $progress->current_index)->toBe(0)
        ->and($progress->announced_officers)->toBeNull();
});

it('always wipes pegawai when jawatan is wiped and keeps gred', function (): void {
    seedWipeData();

    $this->actingAs(adminUser())
        ->postJson(route('admin.kawalan.system.wipe'), ['tables' => ['jawatan']])
        ->assertOk()
        ->assertJsonPath('deleted', ['pegawai' => 1, 'jawatan' => 2]);

    expect(Pegawai::query()->count())->toBe(0)
        ->and(Jawatan::query()->count())->toBe(0)
        ->and(Gred::query()->count())->toBe(1)
        ->and(AnnouncedOfficer::query()->count())->toBe(0);
});

it('wipes pegawai, jawatan and gred and restarts their ids at 1', function (): void {
    seedWipeData();

    $this->actingAs(adminUser())
        ->postJson(route('admin.kawalan.system.wipe'), ['tables' => ['gred', 'jawatan', 'pegawai']])
        ->assertOk()
        ->assertJsonPath('deleted', ['pegawai' => 1, 'jawatan' => 2, 'gred' => 1])
        ->assertJsonPath('counts', ['pegawai' => 0, 'jawatan' => 0, 'gred' => 0])
        ->assertJsonPath('message', 'Berjaya memadam 1 pegawai, 2 jawatan, 1 gred.');

    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Baharu']);
    $gred = Gred::query()->create(['desc_gred' => 'Baharu']);
    $pegawai = Pegawai::query()->create([
        'nama' => 'Pegawai Baharu',
        'no_kp' => '900101015555',
        'ptj_id' => Ptj::query()->value('id'),
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
        's_kehadiran' => Pegawai::S_KEHADIRAN_PAGI,
    ]);

    expect($jawatan->id)->toBe(1)
        ->and($gred->id)->toBe(1)
        ->and($pegawai->id)->toBe(1);
});

it('shows the wipe card and active session note to admin', function (): void {
    seedWipeData();

    $this->actingAs(adminUser())
        ->get(route('admin.kawalan.system.index'))
        ->assertOk()
        ->assertSee('Tetap Semula Kehadiran')
        ->assertSee('Padam data pegawai dan maklumat rujukan')
        ->assertSee('role="tablist"', false)
        ->assertSee('x-data="systemPage(', false)
        ->assertViewHas('overview', ['pegawai' => 1, 'jawatan' => 2, 'gred' => 1, 'hadir' => 1, 'lewat' => 0, 'kerusi' => 0, 'diumumkan' => 1])
        ->assertViewHas('activeSesi', 'Sesi Padam')
        ->assertSee('Sesi &quot;Sesi Padam&quot; sedang aktif', false);
});

it('hides the active session warning when no session is on air', function (): void {
    seedWipeData();
    SesiMajlis::query()->update(['is_active' => false]);

    $this->actingAs(adminUser())
        ->get(route('admin.kawalan.system.index'))
        ->assertOk()
        ->assertViewHas('activeSesi', null)
        ->assertDontSee('sedang aktif');
});

it('returns the fresh overview after a reset', function (): void {
    seedWipeData();

    $this->actingAs(adminUser())
        ->postJson(route('admin.kawalan.system.reset'))
        ->assertOk()
        ->assertJsonPath('overview.pegawai', 1)
        ->assertJsonPath('overview.hadir', 0)
        ->assertJsonPath('overview.diumumkan', 0);
});
