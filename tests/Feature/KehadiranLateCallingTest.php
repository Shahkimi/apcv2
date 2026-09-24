<?php

declare(strict_types=1);

use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Meja;
use App\Models\Pegawai;
use App\Models\Ptj;
use App\Models\SesiMajlis;
use App\Models\User;
use App\Services\Kehadiran\KehadiranCallingService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    Meja::query()->create(['sizing' => 10]);
});

function activeSesiForKehadiran(array $overrides = []): SesiMajlis
{
    return SesiMajlis::query()->create(array_merge([
        'sesi' => 'Sesi Ujian',
        'is_active' => true,
        'is_late' => false,
        'countdown_start_late' => null,
        'seat_offset' => 0,
        's_kehadiran' => SesiMajlis::S_KEHADIRAN_PAGI,
    ], $overrides));
}

function createPegawaiForTest(): Pegawai
{
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Ujian']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Pegawai']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);

    return Pegawai::query()->create([
        'nama' => 'Ahmad Ujian',
        'no_kp' => '900101011234',
        'ptj_id' => $ptj->id,
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
        'rsvp' => true,
        'no_kerusi' => 5,
        'is_attend' => false,
        's_kehadiran' => Pegawai::S_KEHADIRAN_PAGI,
    ]);
}

function adminUser(): User
{
    return User::query()->create([
        'name' => 'Admin Ujian',
        'username' => 'admin_ujian',
        'password' => Hash::make('password'),
        'role' => User::ROLE_ADMIN,
    ]);
}

function mediaUser(): User
{
    return User::query()->create([
        'name' => 'Media Ujian',
        'username' => 'media_ujian',
        'password' => Hash::make('password'),
        'role' => User::ROLE_MEDIA,
    ]);
}

function plainUser(): User
{
    return User::factory()->create(['role' => User::ROLE_USER]);
}

it('assigns late calling number when late session is on air', function (): void {
    $admin = adminUser();
    $pegawai = createPegawaiForTest();

    $sesi = activeSesiForKehadiran([
        'sesi' => 'Pagi Lewat',
        'is_late' => true,
        'countdown_start_late' => 60,
    ]);

    $this->actingAs($admin)
        ->putJson(route('admin.kehadiran.verify', $pegawai))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('is_attend', true)
        ->assertJsonPath('no_panggilan_lewat', 60);

    expect($pegawai->fresh()->no_panggilan_lewat)->toBe(60);
    expect($pegawai->fresh()->is_late)->toBeTrue();
    expect($pegawai->fresh()->sesi_majlis_id)->toBe($sesi->id);
});

it('assigns late calling number when no_panggilan_lewat placeholder is zero', function (): void {
    $admin = adminUser();
    $pegawai = createPegawaiForTest();
    $pegawai->update(['no_panggilan_lewat' => 0]);

    activeSesiForKehadiran([
        'sesi' => 'Pagi — placeholder sifar',
        'is_late' => true,
        'countdown_start_late' => 55,
    ]);

    $this->actingAs($admin)
        ->putJson(route('admin.kehadiran.verify', $pegawai))
        ->assertOk()
        ->assertJsonPath('no_panggilan_lewat', 55);

    expect($pegawai->fresh()->no_panggilan_lewat)->toBe(55);
    expect($pegawai->fresh()->is_late)->toBeTrue();
});

it('assigns late calling number when session is still active but is_late is true', function (): void {
    $admin = adminUser();
    $pegawai = createPegawaiForTest();

    activeSesiForKehadiran([
        'sesi' => 'Pagi — fasa lewat',
        'is_late' => true,
        'countdown_start_late' => 1600,
    ]);

    $this->actingAs($admin)
        ->putJson(route('admin.kehadiran.verify', $pegawai))
        ->assertOk()
        ->assertJsonPath('no_panggilan_lewat', 1600);

    expect($pegawai->fresh()->no_panggilan_lewat)->toBe(1600);
    expect($pegawai->fresh()->is_late)->toBeTrue();
});

it('does not assign late calling number for regular active session', function (): void {
    $admin = adminUser();
    $pegawai = createPegawaiForTest();

    activeSesiForKehadiran([
        'sesi' => 'Pagi',
        'is_late' => false,
        'countdown_start_late' => null,
    ]);

    $this->actingAs($admin)
        ->putJson(route('admin.kehadiran.verify', $pegawai))
        ->assertOk();

    expect($pegawai->fresh()->no_panggilan_lewat)->toBeNull();
    expect($pegawai->fresh()->is_late)->toBeFalse();
});

it('treats rsvp no officer as late even when session is not late', function (): void {
    $admin = adminUser();
    $pegawai = createPegawaiForTest();
    $pegawai->update([
        'rsvp' => false,
        'no_kerusi' => 99,
    ]);

    activeSesiForKehadiran([
        'sesi' => 'Pagi',
        'is_late' => false,
        'seat_offset' => 700,
    ]);

    $this->actingAs($admin)
        ->putJson(route('admin.kehadiran.verify', $pegawai))
        ->assertOk()
        ->assertJsonPath('is_attend', true);

    $freshPegawai = $pegawai->fresh();
    expect($freshPegawai->is_late)->toBeTrue();
    expect($freshPegawai->no_panggilan_lewat)->toBe(700);
    expect($freshPegawai->no_kerusi)->toBeNull();
    expect($freshPegawai->no_meja)->toBeNull();
});

it('continues late calling sequence for rsvp no officer', function (): void {
    $admin = adminUser();
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Ujian']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Pegawai']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);

    $sesi = activeSesiForKehadiran([
        'sesi' => 'Sesi Aktif',
        'is_late' => true,
        'seat_offset' => 700,
    ]);

    Pegawai::query()->create([
        'nama' => 'Pegawai Awal',
        'no_kp' => '980101011111',
        'ptj_id' => $ptj->id,
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
        'rsvp' => true,
        'no_kerusi' => 2,
        'is_attend' => true,
        'is_late' => true,
        'no_panggilan_lewat' => 705,
        'sesi_majlis_id' => $sesi->id,
    ]);

    $pegawai = createPegawaiForTest();
    $pegawai->update(['rsvp' => false]);

    $this->actingAs($admin)
        ->putJson(route('admin.kehadiran.verify', $pegawai))
        ->assertOk();

    expect($pegawai->fresh()->no_panggilan_lewat)->toBe(706);
});

it('clears late calling number when attendance is cancelled', function (): void {
    $admin = adminUser();
    $pegawai = createPegawaiForTest();

    $sesi = activeSesiForKehadiran([
        'sesi' => 'Pagi Lewat',
        'is_late' => true,
        'countdown_start_late' => 60,
    ]);

    $pegawai->forceFill([
        'is_attend' => true,
        'no_panggilan_lewat' => 3,
        'no_meja' => 1,
        'is_late' => true,
        'sesi_majlis_id' => $sesi->id,
    ])->save();

    $this->actingAs($admin)
        ->putJson(route('admin.kehadiran.verify', $pegawai))
        ->assertOk()
        ->assertJsonPath('is_attend', false);

    expect($pegawai->fresh()->no_panggilan_lewat)->toBeNull();
    expect($pegawai->fresh()->is_late)->toBeFalse();
    expect($pegawai->fresh()->sesi_majlis_id)->toBeNull();
});

it('returns rsvp value in kehadiran details response', function (): void {
    $admin = adminUser();
    $pegawai = createPegawaiForTest();
    $pegawai->update(['rsvp' => false]);
    activeSesiForKehadiran();

    $this->actingAs($admin)
        ->getJson(route('admin.kehadiran.details', $pegawai))
        ->assertOk()
        ->assertJsonPath('pegawai.rsvp', false);
});

it('previews next late calling number in details for rsvp no officer', function (): void {
    $admin = adminUser();
    $pegawai = createPegawaiForTest();
    $pegawai->update([
        'rsvp' => false,
        'is_attend' => false,
    ]);

    activeSesiForKehadiran([
        'sesi' => 'Sesi Preview',
        'is_late' => false,
        'seat_offset' => 800,
    ]);

    $response = $this->actingAs($admin)
        ->getJson(route('admin.kehadiran.details', $pegawai))
        ->assertOk()
        ->json('pegawai');

    expect($response['no_panggilan_lewat'])->toBe(800);
});

it('increments late number without duplicates for first come first serve rsvp no officers', function (): void {
    $admin = adminUser();
    $firstPegawai = createPegawaiForTest();
    $firstPegawai->update([
        'rsvp' => false,
        'no_kp' => '900101011235',
    ]);

    $secondPegawai = createPegawaiForTest();
    $secondPegawai->update([
        'rsvp' => false,
        'no_kp' => '900101011236',
    ]);

    activeSesiForKehadiran([
        'sesi' => 'Sesi FCFS',
        'is_late' => false,
        'seat_offset' => 700,
    ]);

    $this->actingAs($admin)
        ->putJson(route('admin.kehadiran.verify', $firstPegawai))
        ->assertOk();

    $this->actingAs($admin)
        ->putJson(route('admin.kehadiran.verify', $secondPegawai))
        ->assertOk();

    expect($firstPegawai->fresh()->no_panggilan_lewat)->toBe(700);
    expect($secondPegawai->fresh()->no_panggilan_lewat)->toBe(701);
    expect($firstPegawai->fresh()->no_panggilan_lewat)
        ->not->toBe($secondPegawai->fresh()->no_panggilan_lewat);
});

it('clears late calling number when rsvp no officer attendance is cancelled', function (): void {
    $admin = adminUser();
    $pegawai = createPegawaiForTest();
    $pegawai->forceFill([
        'rsvp' => false,
        'is_attend' => true,
        'is_late' => true,
        'no_kerusi' => null,
        'no_meja' => null,
        'no_panggilan_lewat' => 701,
        'sesi_majlis_id' => activeSesiForKehadiran(['seat_offset' => 700])->id,
    ])->save();

    $this->actingAs($admin)
        ->putJson(route('admin.kehadiran.verify', $pegawai))
        ->assertOk()
        ->assertJsonPath('is_attend', false);

    $freshPegawai = $pegawai->fresh();
    expect($freshPegawai->no_panggilan_lewat)->toBeNull();
    expect($freshPegawai->is_late)->toBeFalse();
    expect($freshPegawai->sesi_majlis_id)->toBeNull();
});

it('batch assigns late numbers when session becomes inactive late', function (): void {
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Ujian']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Pegawai']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);

    $sesi = SesiMajlis::query()->create([
        'sesi' => 'Pagi',
        'is_active' => true,
        'is_late' => false,
        'countdown_start_late' => 100,
        'seat_offset' => 0,
        's_kehadiran' => SesiMajlis::S_KEHADIRAN_PAGI,
    ]);

    $makePegawai = function (string $noKp, int $seat) use ($ptj, $jawatan, $gred, $sesi): Pegawai {
        return Pegawai::query()->create([
            'nama' => 'Pegawai '.$noKp,
            'no_kp' => $noKp,
            'ptj_id' => $ptj->id,
            'jawatan_id' => $jawatan->id,
            'gred_id' => $gred->id,
            'rsvp' => true,
            'no_kerusi' => $seat,
            'is_attend' => true,
            'sesi_majlis_id' => $sesi->id,
        ]);
    };

    $p1 = $makePegawai('911111011111', 5);
    $p2 = $makePegawai('922222022222', 3);
    $p3 = $makePegawai('933333033333', 10);

    $sesi->update([
        'is_active' => false,
        'is_late' => true,
    ]);

    expect($p2->fresh()->no_panggilan_lewat)->toBe(100);
    expect($p1->fresh()->no_panggilan_lewat)->toBe(101);
    expect($p3->fresh()->no_panggilan_lewat)->toBe(102);
    expect($p1->fresh()->is_late)->toBeTrue();
    expect($p2->fresh()->is_late)->toBeTrue();
    expect($p3->fresh()->is_late)->toBeTrue();
});

it('orders paparan display on-time by no_kerusi then late by no_panggilan_lewat', function (): void {
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Ujian']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Pegawai']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);

    $make = function (string $nama, string $noKp, int $seat, bool $late, ?int $noPanggilan = null) use ($ptj, $jawatan, $gred): Pegawai {
        return Pegawai::query()->create([
            'nama' => $nama,
            'no_kp' => $noKp,
            'ptj_id' => $ptj->id,
            'jawatan_id' => $jawatan->id,
            'gred_id' => $gred->id,
            'rsvp' => true,
            'no_kerusi' => $seat,
            'is_attend' => true,
            'is_late' => $late,
            'no_panggilan_lewat' => $noPanggilan,
            'no_meja' => 1,
        ]);
    };

    $make('Liza', '911111011111', 5, false, null);
    $make('Ali', '922222022222', 1, false, null);
    $make('Abu', '933333033333', 3, false, null);
    $make('Zara', '944444044444', 2, true, 1602);
    $make('Aina', '955555055555', 4, true, 1601);

    Pegawai::query()->create([
        'nama' => 'Tidak Hadir',
        'no_kp' => '966666066666',
        'ptj_id' => $ptj->id,
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
        'rsvp' => true,
        'no_kerusi' => 9,
        'is_attend' => false,
        'is_late' => false,
    ]);

    $ordered = app(KehadiranCallingService::class)->attendedPegawaiForDisplay()->pluck('nama')->all();

    expect($ordered)->toBe(['Ali', 'Abu', 'Liza', 'Aina', 'Zara']);
});

it('rejects attendance verification when no session is active', function (): void {
    $admin = adminUser();
    $pegawai = createPegawaiForTest();

    SesiMajlis::query()->create([
        'sesi' => 'Tidak aktif',
        'is_active' => false,
        'is_late' => false,
        'countdown_start_late' => null,
        'seat_offset' => 0,
        's_kehadiran' => SesiMajlis::S_KEHADIRAN_PAGI,
    ]);

    $this->actingAs($admin)
        ->putJson(route('admin.kehadiran.verify', $pegawai))
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    expect($pegawai->fresh()->is_attend)->toBeFalse();
});

it('rejects verify when officer session type does not match active session', function (): void {
    $admin = adminUser();
    $pegawai = createPegawaiForTest();
    $pegawai->update(['s_kehadiran' => Pegawai::S_KEHADIRAN_PAGI]);

    activeSesiForKehadiran([
        'sesi' => 'Petang',
        's_kehadiran' => SesiMajlis::S_KEHADIRAN_PETANG,
    ]);

    $this->actingAs($admin)
        ->putJson(route('admin.kehadiran.verify', $pegawai))
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    expect($pegawai->fresh()->is_attend)->toBeFalse();
});

it('allows verify when officer and active session s_kehadiran match petang', function (): void {
    $admin = adminUser();
    $pegawai = createPegawaiForTest();
    $pegawai->update(['s_kehadiran' => Pegawai::S_KEHADIRAN_PETANG]);

    $sesi = activeSesiForKehadiran([
        'sesi' => 'Petang',
        's_kehadiran' => SesiMajlis::S_KEHADIRAN_PETANG,
    ]);

    $this->actingAs($admin)
        ->putJson(route('admin.kehadiran.verify', $pegawai))
        ->assertOk();

    expect($pegawai->fresh()->sesi_majlis_id)->toBe($sesi->id);
});

it('stores sesi_majlis_id when attendance is verified', function (): void {
    $admin = adminUser();
    $pegawai = createPegawaiForTest();
    $sesi = activeSesiForKehadiran(['sesi' => 'Pagi']);

    $this->actingAs($admin)
        ->putJson(route('admin.kehadiran.verify', $pegawai))
        ->assertOk();

    expect($pegawai->fresh()->sesi_majlis_id)->toBe($sesi->id);
    expect($pegawai->fresh()->is_late)->toBeFalse();
});

it('calculates table number with seat offset on verify', function (): void {
    $admin = adminUser();
    activeSesiForKehadiran([
        'sesi' => 'Petang',
        'seat_offset' => 700,
    ]);
    $pegawai = createPegawaiForTest();
    $pegawai->no_kerusi = 705;
    $pegawai->save();

    $this->actingAs($admin)
        ->putJson(route('admin.kehadiran.verify', $pegawai))
        ->assertOk();

    expect($pegawai->fresh()->no_meja)->toBe(1);
});

it('maps same relative seat to same table across sessions with different offsets', function (): void {
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Ujian']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Pegawai']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);
    $service = app(KehadiranCallingService::class);

    $sesi1 = activeSesiForKehadiran(['sesi' => 'Pagi', 'seat_offset' => 0]);
    $sesi1->update(['is_active' => false]);

    Pegawai::query()->create([
        'nama' => 'Officer 1',
        'no_kp' => '111111111111',
        'ptj_id' => $ptj->id,
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
        'no_kerusi' => 15,
        'is_attend' => true,
        'sesi_majlis_id' => $sesi1->id,
        'no_meja' => $service->calculateTableNumber(15, $sesi1),
    ]);

    $sesi2 = activeSesiForKehadiran([
        'sesi' => 'Petang',
        'seat_offset' => 700,
        's_kehadiran' => SesiMajlis::S_KEHADIRAN_PETANG,
    ]);

    Pegawai::query()->create([
        'nama' => 'Officer 2',
        'no_kp' => '222222222222',
        'ptj_id' => $ptj->id,
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
        'no_kerusi' => 715,
        'is_attend' => true,
        'sesi_majlis_id' => $sesi2->id,
        'no_meja' => $service->calculateTableNumber(715, $sesi2),
    ]);

    expect($service->calculateTableNumber(15, $sesi1))->toBe(2);
    expect($service->calculateTableNumber(715, $sesi2))->toBe(2);
});

it('shows table preview in details based on active session offset', function (): void {
    $admin = adminUser();
    $pegawai = createPegawaiForTest();
    $pegawai->forceFill([
        'is_attend' => true,
        'sesi_majlis_id' => activeSesiForKehadiran([
            'sesi' => 'Sesi Lama',
            'seat_offset' => 0,
        ])->id,
        'no_meja' => 2,
    ])->save();

    SesiMajlis::query()->where('id', $pegawai->sesi_majlis_id)->update(['is_active' => false]);
    activeSesiForKehadiran([
        'sesi' => 'Sesi Baru',
        'seat_offset' => 4,
    ]);

    $response = $this->actingAs($admin)
        ->getJson(route('admin.kehadiran.details', $pegawai))
        ->assertOk()
        ->json('pegawai');

    // seat 5 with offset 4 => relative seat 1 => table 1
    expect($response['no_meja'])->toBe(1);
});

it('hides table number in details when setting is disabled', function (): void {
    $admin = adminUser();
    app(SettingsService::class)->set('display.show_table_number_in_dialog', false);

    $pegawai = createPegawaiForTest();
    activeSesiForKehadiran();

    $response = $this->actingAs($admin)
        ->getJson(route('admin.kehadiran.details', $pegawai))
        ->assertOk()
        ->json();

    expect($response['show_table_number'])->toBeFalse();
});

it('admin can toggle table number display setting', function (): void {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->postJson(route('admin.kawalan.meja.toggle-display'), ['show' => false])
        ->assertOk()
        ->assertJsonPath('show', false);

    expect(app(SettingsService::class)->showTableNumberInDialog())->toBeFalse();
});

it('allows admin and media to view paparan', function (): void {
    $pegawai = createPegawaiForTest();
    $pegawai->forceFill(['is_attend' => true, 'no_meja' => 1, 'hadir_at' => now()])->save();

    $this->actingAs(adminUser())
        ->get(route('admin.paparan.index'))
        ->assertOk()
        ->assertSee(__('Debug paparan'), false)
        ->assertSee('paparan-table', false);

    $media = mediaUser();
    $this->actingAs($media)
        ->get(route('media.paparan.index'))
        ->assertOk()
        ->assertSee(__('Debug paparan'), false)
        ->assertSee('paparan-table', false);

    $this->actingAs($media)
        ->get(route('media.senarai.index'))
        ->assertOk()
        ->assertSee('Mula Presentasi', false);

    $this->actingAs($media)
        ->get(route('media.senarai.present'))
        ->assertOk()
        ->assertSee('Ahmad Ujian', false);
});

function paparanDatatableQuery(array $extra = []): string
{
    return http_build_query(array_merge([
        'draw' => 1,
        'start' => 0,
        'length' => 50,
        'search' => ['value' => '', 'regex' => 'false'],
        'columns' => [
            ['data' => '', 'name' => '', 'searchable' => 'false', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']],
            ['data' => 'giliran', 'name' => 'giliran', 'searchable' => 'false', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']],
            ['data' => 'nama', 'name' => 'nama', 'searchable' => 'true', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']],
            ['data' => 'tempat_duduk', 'name' => 'tempat_duduk', 'searchable' => 'false', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']],
            ['data' => 'ptj_name', 'name' => 'ptj.nama_ptj', 'searchable' => 'false', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']],
            ['data' => 'hadir_at_label', 'name' => 'hadir_at', 'searchable' => 'false', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']],
            ['data' => 'status_label', 'name' => 'status', 'searchable' => 'false', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']],
        ],
    ], $extra));
}

it('sets hadir_at when attendance is verified', function (): void {
    $admin = adminUser();
    $pegawai = createPegawaiForTest();
    activeSesiForKehadiran();

    $this->travelTo(now()->setTime(9, 30, 0));

    $this->actingAs($admin)
        ->putJson(route('admin.kehadiran.verify', $pegawai))
        ->assertOk()
        ->assertJsonPath('is_attend', true);

    expect($pegawai->fresh()->hadir_at)->not->toBeNull();
    expect($pegawai->fresh()->hadir_at->equalTo(now()))->toBeTrue();
});

it('does not bump hadir_at on idempotent re-verify', function (): void {
    $admin = adminUser();
    $pegawai = createPegawaiForTest();
    activeSesiForKehadiran();

    $this->travelTo(now()->setTime(9, 0, 0));
    $this->actingAs($admin)->putJson(route('admin.kehadiran.verify', $pegawai), ['is_attend' => true])->assertOk();
    $firstHadirAt = $pegawai->fresh()->hadir_at;

    $this->travelTo(now()->addMinutes(5));
    $this->actingAs($admin)->putJson(route('admin.kehadiran.verify', $pegawai), ['is_attend' => true])->assertOk();

    expect($pegawai->fresh()->hadir_at->equalTo($firstHadirAt))->toBeTrue();
});

it('clears hadir_at when attendance is cancelled', function (): void {
    $admin = adminUser();
    $pegawai = createPegawaiForTest();
    activeSesiForKehadiran();

    $this->actingAs($admin)->putJson(route('admin.kehadiran.verify', $pegawai), ['is_attend' => true])->assertOk();
    expect($pegawai->fresh()->hadir_at)->not->toBeNull();

    $this->actingAs($admin)->putJson(route('admin.kehadiran.verify', $pegawai), ['is_attend' => false])->assertOk();
    expect($pegawai->fresh()->hadir_at)->toBeNull();
});

it('orders paparan datatable by hadir_at desc with nulls last', function (): void {
    $admin = adminUser();
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Paparan']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Pegawai']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);
    $base = ['ptj_id' => $ptj->id, 'jawatan_id' => $jawatan->id, 'gred_id' => $gred->id, 'rsvp' => true, 's_kehadiran' => Pegawai::S_KEHADIRAN_PAGI, 'is_attend' => true];

    $oldest = Pegawai::query()->create(array_merge($base, ['nama' => 'Lama', 'no_kp' => '920101010001', 'hadir_at' => now()->subMinutes(10)]));
    $newest = Pegawai::query()->create(array_merge($base, ['nama' => 'Terkini', 'no_kp' => '920101010002', 'hadir_at' => now()]));
    $middle = Pegawai::query()->create(array_merge($base, ['nama' => 'Tengah', 'no_kp' => '920101010003', 'hadir_at' => now()->subMinutes(5)]));
    $noTimestamp = Pegawai::query()->create(array_merge($base, ['nama' => 'Tiada Masa', 'no_kp' => '920101010004', 'hadir_at' => null]));
    Pegawai::query()->create(array_merge($base, ['nama' => 'Belum Hadir', 'no_kp' => '920101010005', 'is_attend' => false]));

    $response = $this->actingAs($admin)
        ->getJson(route('admin.paparan.datatable').'?'.paparanDatatableQuery())
        ->assertOk()
        ->assertJsonPath('recordsTotal', 4);

    $ids = array_map(static fn (array $row): int => (int) $row['id'], $response->json('data'));

    expect($ids)->toBe([$newest->id, $middle->id, $oldest->id, $noTimestamp->id]);
});

it('computes giliran from no_kerusi for on-time and no_panggilan_lewat for late', function (): void {
    $admin = adminUser();
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Giliran']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Pegawai']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);
    $base = ['ptj_id' => $ptj->id, 'jawatan_id' => $jawatan->id, 'gred_id' => $gred->id, 'rsvp' => true, 's_kehadiran' => Pegawai::S_KEHADIRAN_PAGI, 'is_attend' => true];

    Pegawai::query()->create(array_merge($base, ['nama' => 'Tepat Masa', 'no_kp' => '930101010001', 'no_kerusi' => 12, 'is_late' => false, 'hadir_at' => now()]));
    Pegawai::query()->create(array_merge($base, ['nama' => 'Datang Lewat', 'no_kp' => '930101010002', 'no_kerusi' => 7, 'no_panggilan_lewat' => 1603, 'is_late' => true, 'hadir_at' => now()->subMinute()]));

    $response = $this->actingAs($admin)
        ->getJson(route('admin.paparan.datatable').'?'.paparanDatatableQuery())
        ->assertOk();

    $rows = collect($response->json('data'));
    $ontime = $rows->first(fn (array $row) => str_contains($row['nama'], 'Tepat Masa'));
    $late = $rows->first(fn (array $row) => str_contains($row['nama'], 'Datang Lewat'));

    expect($ontime['giliran'])->toBe('12');
    expect($late['giliran'])->toBe('1603');
    expect($ontime['status_label'])->toContain(__('Tepat masa'));
    expect($late['status_label'])->toContain(__('Lewat'));
});

it('filters paparan datatable by sesi and searches by no_kp', function (): void {
    $admin = adminUser();
    $sesiA = activeSesiForKehadiran(['sesi' => 'Sesi A', 'is_active' => false]);
    $sesiB = activeSesiForKehadiran(['sesi' => 'Sesi B', 'is_active' => false]);
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Tapis']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Pegawai']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);
    $base = ['ptj_id' => $ptj->id, 'jawatan_id' => $jawatan->id, 'gred_id' => $gred->id, 'rsvp' => true, 's_kehadiran' => Pegawai::S_KEHADIRAN_PAGI, 'is_attend' => true, 'hadir_at' => now()];

    Pegawai::query()->create(array_merge($base, ['nama' => 'Sesi A Satu', 'no_kp' => '940101010001', 'sesi_majlis_id' => $sesiA->id]));
    Pegawai::query()->create(array_merge($base, ['nama' => 'Sesi B Satu', 'no_kp' => '940101010002', 'sesi_majlis_id' => $sesiB->id]));

    $this->actingAs($admin)
        ->getJson(route('admin.paparan.datatable').'?'.paparanDatatableQuery(['sesi_majlis_id' => $sesiA->id]))
        ->assertOk()
        ->assertJsonPath('recordsFiltered', 1);

    $this->actingAs($admin)
        ->getJson(route('admin.paparan.datatable').'?'.paparanDatatableQuery(['search' => ['value' => '940101010002', 'regex' => 'false']]))
        ->assertOk()
        ->assertJsonPath('recordsFiltered', 1);
});

it('returns stats inside paparan datatable payload and forbids plain users', function (): void {
    $media = mediaUser();
    $pegawai = createPegawaiForTest();
    $pegawai->forceFill(['is_attend' => true, 'is_late' => false, 'hadir_at' => now()])->save();

    $this->actingAs($media)
        ->getJson(route('media.paparan.datatable').'?'.paparanDatatableQuery())
        ->assertOk()
        ->assertJsonStructure(['stats' => ['total_hadir', 'total_tepat', 'total_lewat']])
        ->assertJsonPath('stats.total_hadir', 1)
        ->assertJsonPath('stats.total_tepat', 1)
        ->assertJsonPath('stats.total_lewat', 0);

    $this->actingAs(plainUser())
        ->getJson(route('media.paparan.datatable').'?'.paparanDatatableQuery())
        ->assertForbidden();
});

it('renders renamed media sidebar labels', function (): void {
    $media = mediaUser();

    $this->actingAs($media)
        ->get(route('media.dashboard'))
        ->assertOk()
        ->assertSee(__('Layar Utama'), false)
        ->assertSee(__('Debug paparan'), false)
        ->assertDontSee('Senarai kehadiran', false)
        ->assertSee('ri-slideshow-3-line', false)
        ->assertSee('ri-bug-line', false);
});

it('user role can open kehadiran index', function (): void {
    $user = plainUser();

    $this->actingAs($user)
        ->get(route('user.kehadiran.index'))
        ->assertOk()
        ->assertSee(__('Kehadiran pegawai'), false);
});

it('user role can verify attendance when session matches', function (): void {
    $user = plainUser();
    $pegawai = createPegawaiForTest();
    activeSesiForKehadiran();

    $this->actingAs($user)
        ->putJson(route('user.kehadiran.verify', $pegawai))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('is_attend', true);
});

it('forbids media from user kehadiran routes', function (): void {
    $media = mediaUser();

    $this->actingAs($media)
        ->get(route('user.kehadiran.index'))
        ->assertForbidden();
});

it('redirects guests from user kehadiran', function (): void {
    $this->get(route('user.kehadiran.index'))
        ->assertRedirect(route('login'));
});

it('orders admin kehadiran datatable by is_attend then id', function (): void {
    $admin = adminUser();
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Susunan']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Pegawai']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);
    $base = [
        'ptj_id' => $ptj->id,
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
        'rsvp' => true,
        'no_kerusi' => 1,
        's_kehadiran' => Pegawai::S_KEHADIRAN_PAGI,
    ];

    $attendedFirst = Pegawai::query()->create(array_merge($base, [
        'nama' => 'Hadir Awal',
        'no_kp' => '910101010001',
        'is_attend' => true,
    ]));
    $notAttendLater = Pegawai::query()->create(array_merge($base, [
        'nama' => 'Belum Lepas',
        'no_kp' => '910101010002',
        'is_attend' => false,
    ]));
    $notAttendEarlier = Pegawai::query()->create(array_merge($base, [
        'nama' => 'Belum Awal',
        'no_kp' => '910101010003',
        'is_attend' => false,
    ]));

    $query = http_build_query([
        'draw' => 1,
        'start' => 0,
        'length' => 50,
        'search' => ['value' => '', 'regex' => false],
        'columns' => [
            ['data' => 'id', 'name' => 'id', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
            ['data' => 'nama', 'name' => 'nama', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
            ['data' => 'ptj_name', 'name' => 'ptj.nama_ptj', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
            ['data' => 'rsvp_sesi_label', 'name' => 'rsvp_sesi', 'searchable' => false, 'orderable' => false, 'search' => ['value' => '', 'regex' => false]],
            ['data' => 'no_kerusi', 'name' => 'no_kerusi', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
            ['data' => 'action', 'name' => 'action', 'searchable' => false, 'orderable' => false, 'search' => ['value' => '', 'regex' => false]],
        ],
    ]);

    $response = $this->actingAs($admin)
        ->getJson(route('admin.kehadiran.datatable').'?'.$query)
        ->assertOk()
        ->assertJsonPath('recordsTotal', 3);

    $ids = array_map(
        static fn (array $row): int => (int) $row['id'],
        $response->json('data')
    );

    $expected = [
        min($notAttendEarlier->id, $notAttendLater->id),
        max($notAttendEarlier->id, $notAttendLater->id),
        $attendedFirst->id,
    ];
    expect($ids)->toBe($expected);
});

it('returns kehadiran stats json for admin matching pegawai counts', function (): void {
    $admin = adminUser();
    createPegawaiForTest();

    $this->actingAs($admin)
        ->getJson(route('admin.kehadiran.stats'))
        ->assertOk()
        ->assertJsonStructure(['total_pegawai', 'total_rsvp', 'total_hadir'])
        ->assertJsonPath('total_pegawai', 1)
        ->assertJsonPath('total_rsvp', 1)
        ->assertJsonPath('total_hadir', 0);
});

it('returns kehadiran stats json for user role', function (): void {
    $user = plainUser();
    createPegawaiForTest();

    $this->actingAs($user)
        ->getJson(route('user.kehadiran.stats'))
        ->assertOk()
        ->assertJsonPath('total_pegawai', 1)
        ->assertJsonPath('total_rsvp', 1)
        ->assertJsonPath('total_hadir', 0);
});

it('returns kehadiran stats json with correct counts for a mixed set', function (): void {
    $admin = adminUser();
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Stats']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Pegawai']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);
    $base = [
        'ptj_id' => $ptj->id,
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
        's_kehadiran' => Pegawai::S_KEHADIRAN_PAGI,
    ];

    Pegawai::query()->create(array_merge($base, [
        'nama' => 'Pegawai A', 'no_kp' => '900101010101', 'rsvp' => true, 'is_attend' => true,
    ]));
    Pegawai::query()->create(array_merge($base, [
        'nama' => 'Pegawai B', 'no_kp' => '900101010102', 'rsvp' => true, 'is_attend' => false,
    ]));
    Pegawai::query()->create(array_merge($base, [
        'nama' => 'Pegawai C', 'no_kp' => '900101010103', 'rsvp' => false, 'is_attend' => false,
    ]));

    $this->actingAs($admin)
        ->getJson(route('admin.kehadiran.stats'))
        ->assertOk()
        ->assertJsonPath('total_pegawai', 3)
        ->assertJsonPath('total_rsvp', 2)
        ->assertJsonPath('total_hadir', 1);
});

it('filters admin kehadiran datatable by name search', function (): void {
    $admin = adminUser();
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Carian']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Pegawai']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);
    $base = [
        'ptj_id' => $ptj->id,
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
        'rsvp' => true,
        's_kehadiran' => Pegawai::S_KEHADIRAN_PAGI,
    ];

    $match = Pegawai::query()->create(array_merge($base, [
        'nama' => 'Belum Hadir Satu',
        'no_kp' => '900101011111',
        'is_attend' => false,
    ]));
    Pegawai::query()->create(array_merge($base, [
        'nama' => 'Lain Sahaja',
        'no_kp' => '900101012222',
        'is_attend' => false,
    ]));

    $query = http_build_query([
        'draw' => 1,
        'start' => 0,
        'length' => 50,
        'search' => ['value' => 'belum', 'regex' => 'false'],
        'columns' => [
            ['data' => 'id', 'name' => 'id', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
            ['data' => 'nama', 'name' => 'nama', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
            ['data' => 'ptj_name', 'name' => 'ptj.nama_ptj', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
            ['data' => 'rsvp_sesi_label', 'name' => 'rsvp_sesi', 'searchable' => 'false', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']],
            ['data' => 'no_kerusi', 'name' => 'no_kerusi', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
            ['data' => 'action', 'name' => 'action', 'searchable' => 'false', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']],
        ],
    ]);

    $response = $this->actingAs($admin)
        ->getJson(route('admin.kehadiran.datatable').'?'.$query)
        ->assertOk()
        ->assertJsonPath('recordsTotal', 2)
        ->assertJsonPath('recordsFiltered', 1);

    expect($response->json('data.0.id'))->toBe($match->id);
});

it('renders the verify button in the datatable action column', function (): void {
    $admin = adminUser();
    $pegawai = createPegawaiForTest();
    $pegawai->update(['is_attend' => true]);

    $query = http_build_query([
        'draw' => 1,
        'start' => 0,
        'length' => 50,
        'search' => ['value' => '', 'regex' => false],
        'columns' => [
            ['data' => 'id', 'name' => 'id', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
            ['data' => 'nama', 'name' => 'nama', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
            ['data' => 'ptj_name', 'name' => 'ptj.nama_ptj', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
            ['data' => 'rsvp_sesi_label', 'name' => 'rsvp_sesi', 'searchable' => false, 'orderable' => false, 'search' => ['value' => '', 'regex' => false]],
            ['data' => 'no_kerusi', 'name' => 'no_kerusi', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
            ['data' => 'action', 'name' => 'action', 'searchable' => false, 'orderable' => false, 'search' => ['value' => '', 'regex' => false]],
        ],
    ]);

    $response = $this->actingAs($admin)
        ->getJson(route('admin.kehadiran.datatable').'?'.$query)
        ->assertOk();

    $action = $response->json('data.0.action');
    expect($action)->toContain('js-verify-kehadiran');
    expect($action)->toContain('data-is-attend="1"');
    expect($action)->not->toContain('kawalan-dt-officer-avatar');
});

it('is idempotent when verify is called twice with the same explicit intent', function (): void {
    $admin = adminUser();
    $pegawai = createPegawaiForTest();
    activeSesiForKehadiran();

    $this->actingAs($admin)
        ->putJson(route('admin.kehadiran.verify', $pegawai), ['is_attend' => 1])
        ->assertOk()
        ->assertJsonPath('is_attend', true);

    $afterFirst = $pegawai->fresh();

    $this->actingAs($admin)
        ->putJson(route('admin.kehadiran.verify', $pegawai), ['is_attend' => 1])
        ->assertOk()
        ->assertJsonPath('is_attend', true);

    $afterSecond = $pegawai->fresh();

    expect($afterSecond->no_panggilan_lewat)->toBe($afterFirst->no_panggilan_lewat);
    expect($afterSecond->no_meja)->toBe($afterFirst->no_meja);
});

it('is a no-op when verify is called with is_attend=0 on an officer already not attending', function (): void {
    $admin = adminUser();
    $pegawai = createPegawaiForTest();

    $this->actingAs($admin)
        ->putJson(route('admin.kehadiran.verify', $pegawai), ['is_attend' => 0])
        ->assertOk()
        ->assertJsonPath('is_attend', false);

    expect($pegawai->fresh()->is_attend)->toBeFalse();
});
