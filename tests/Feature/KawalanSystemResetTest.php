<?php

declare(strict_types=1);

use App\Models\AnnouncedOfficer;
use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Pegawai;
use App\Models\Ptj;
use App\Models\SesiMajlis;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('redirects guests from system page', function (): void {
    $this->get(route('admin.kawalan.system.index'))->assertRedirect();
});

it('forbids system reset for non-admin', function (): void {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)->postJson(route('admin.kawalan.system.reset'))->assertForbidden();
});

it('resets all pegawai attendance fields for admin', function (): void {
    $admin = User::query()->create([
        'name' => 'Admin Sistem',
        'username' => 'admin_sistem',
        'password' => Hash::make('password'),
        'role' => User::ROLE_ADMIN,
    ]);

    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Sistem']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Jawatan Sistem']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Sistem']);

    $sesi = SesiMajlis::query()->create([
        'sesi' => 'Sesi Sistem',
        'is_active' => true,
        'is_late' => false,
        'countdown_start_late' => null,
        'seat_offset' => 0,
        's_kehadiran' => SesiMajlis::S_KEHADIRAN_PAGI,
    ]);

    $pegawai = Pegawai::query()->create([
        'nama' => 'Pegawai Sistem',
        'no_kp' => '880101015678',
        'ptj_id' => $ptj->id,
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
        'sesi_majlis_id' => $sesi->id,
        'rsvp' => true,
        'no_kerusi' => 10,
        'no_meja' => 7,
        'no_panggilan_lewat' => 3,
        'is_attend' => true,
        'is_late' => true,
        's_kehadiran' => Pegawai::S_KEHADIRAN_PAGI,
    ]);

    AnnouncedOfficer::query()->create([
        'scope_key' => 'sesi:'.$sesi->id,
        'sesi_majlis_id' => $sesi->id,
        'pegawai_id' => $pegawai->id,
        'announced_at' => now(),
    ]);

    expect(AnnouncedOfficer::query()->count())->toBe(1);

    $response = $this->actingAs($admin)->postJson(route('admin.kawalan.system.reset'));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('affected', 1);

    $pegawai->refresh();

    expect($pegawai->sesi_majlis_id)->toBeNull()
        ->and($pegawai->no_meja)->toBe(0)
        ->and($pegawai->no_panggilan_lewat)->toBe(0)
        ->and($pegawai->is_attend)->toBeFalse()
        ->and($pegawai->is_late)->toBeFalse()
        ->and(AnnouncedOfficer::query()->count())->toBe(0);
});

it('allows admin to view system page', function (): void {
    $admin = User::query()->create([
        'name' => 'Admin Halaman',
        'username' => 'admin_halaman',
        'password' => Hash::make('password'),
        'role' => User::ROLE_ADMIN,
    ]);

    $this->actingAs($admin)->get(route('admin.kawalan.system.index'))->assertOk();
});
