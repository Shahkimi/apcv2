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
        ->assertJsonPath('no_panggilan_lewat', 1);

    expect($pegawai->fresh()->no_panggilan_lewat)->toBe(1);
    expect($pegawai->fresh()->is_late)->toBeTrue();
    expect($pegawai->fresh()->sesi_majlis_id)->toBe($sesi->id);
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
        ->assertJsonPath('no_panggilan_lewat', 1);

    expect($pegawai->fresh()->no_panggilan_lewat)->toBe(1);
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
    $pegawai->forceFill(['is_attend' => true, 'no_meja' => 1])->save();

    $this->actingAs(adminUser())
        ->get(route('admin.paparan.index'))
        ->assertOk()
        ->assertSee('Ahmad Ujian', false);

    $media = mediaUser();
    $this->actingAs($media)
        ->get(route('media.paparan.index'))
        ->assertOk()
        ->assertSee('Ahmad Ujian', false);

    $this->actingAs($media)
        ->get(route('media.senarai.index'))
        ->assertOk()
        ->assertSee('Mula Presentasi', false);

    $this->actingAs($media)
        ->get(route('media.senarai.present'))
        ->assertOk()
        ->assertSee('Ahmad Ujian', false);
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
