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

it('batch assigns late numbers when session becomes inactive late', function (): void {
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Ujian']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Pegawai']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Ujian']);

    $sesi = SesiMajlis::query()->create([
        'sesi' => 'Pagi',
        'is_active' => true,
        'is_late' => false,
        'countdown_start_late' => 100,
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
    ]);

    $this->actingAs($admin)
        ->putJson(route('admin.kehadiran.verify', $pegawai))
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    expect($pegawai->fresh()->is_attend)->toBeFalse();
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

it('allows admin and media to view paparan', function (): void {
    $pegawai = createPegawaiForTest();
    $pegawai->forceFill(['is_attend' => true, 'no_meja' => 1])->save();

    $this->actingAs(adminUser())
        ->get(route('admin.paparan.index'))
        ->assertOk()
        ->assertSee('Ahmad Ujian', false);

    $this->actingAs(mediaUser())
        ->get(route('media.paparan.index'))
        ->assertOk()
        ->assertSee('Ahmad Ujian', false);
});
