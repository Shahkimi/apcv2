<?php

declare(strict_types=1);

use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Pegawai;
use App\Models\Ptj;
use App\Models\SesiMajlis;

function kioskPegawai(array $overrides = []): Pegawai
{
    $ptj = Ptj::query()->create(['nama_ptj' => 'PTJ Kiosk']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'Pegawai Kiosk']);
    $gred = Gred::query()->create(['desc_gred' => 'Gred Kiosk']);

    return Pegawai::query()->create(array_merge([
        'nama' => 'Aminah Kiosk',
        'no_kp' => '900101011234',
        'ptj_id' => $ptj->id,
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
        'rsvp' => true,
        'no_kerusi' => 12,
        'is_attend' => false,
        's_kehadiran' => Pegawai::S_KEHADIRAN_PAGI,
    ], $overrides));
}

function kioskActiveSesi(): SesiMajlis
{
    return SesiMajlis::query()->create([
        'sesi' => 'Sesi Kiosk',
        'is_active' => true,
        'is_late' => false,
        'countdown_start_late' => null,
        'seat_offset' => 0,
        's_kehadiran' => SesiMajlis::S_KEHADIRAN_PAGI,
    ]);
}

it('shows the kiosk page to users', function (): void {
    $this->actingAs(plainUser())
        ->get(route('user.kiosk.index'))
        ->assertOk()
        ->assertSee('Selamat Datang')
        ->assertSee('Daftar Masuk Kendiri')
        ->assertSee('x-data="kiosk(', false);
});

it('forbids media and admin from the kiosk', function (): void {
    $media = mediaUser();

    $this->actingAs($media)->get(route('user.kiosk.index'))->assertForbidden();
    $this->actingAs($media)->postJson(route('user.kiosk.lookup'), ['no_kp' => '900101011234'])->assertForbidden();
    $this->actingAs(adminUser())->get(route('user.kiosk.index'))->assertForbidden();
});

it('redirects guests from the kiosk', function (): void {
    $this->get(route('user.kiosk.index'))->assertRedirect(route('login'));
});

it('looks up an officer by a 12 digit no_kp', function (): void {
    kioskActiveSesi();
    $pegawai = kioskPegawai();

    $this->actingAs(plainUser())
        ->postJson(route('user.kiosk.lookup'), ['no_kp' => '900101011234'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('pegawai.id', $pegawai->id)
        ->assertJsonPath('pegawai.nama', 'Aminah Kiosk')
        ->assertJsonPath('pegawai.jawatan_name', 'Pegawai Kiosk')
        ->assertJsonPath('pegawai.ptj_name', 'PTJ Kiosk')
        ->assertJsonPath('active_sesi_name', 'Sesi Kiosk');
});

it('matches a no_kp stored with dashes', function (): void {
    $pegawai = kioskPegawai(['no_kp' => '900101-01-1234']);

    $this->actingAs(plainUser())
        ->postJson(route('user.kiosk.lookup'), ['no_kp' => '900101011234'])
        ->assertOk()
        ->assertJsonPath('pegawai.id', $pegawai->id);
});

it('returns 404 for an unknown no_kp', function (): void {
    kioskPegawai();

    $this->actingAs(plainUser())
        ->postJson(route('user.kiosk.lookup'), ['no_kp' => '111111111111'])
        ->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'No. KP tidak ditemui.');
});

it('rejects a no_kp that is not exactly 12 digits', function (string $value): void {
    $this->actingAs(plainUser())
        ->postJson(route('user.kiosk.lookup'), ['no_kp' => $value])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['no_kp']);
})->with(['short' => '90010101123', 'long' => '9001010112345', 'letters' => '90010101123A', 'dashes' => '900101-01-12']);

it('confirms attendance from the kiosk through the verify endpoint', function (): void {
    kioskActiveSesi();
    $pegawai = kioskPegawai();

    $this->actingAs(plainUser())
        ->putJson(route('user.kehadiran.verify', $pegawai), ['is_attend' => 1])
        ->assertOk()
        ->assertJsonPath('is_attend', true);

    expect($pegawai->fresh()->is_attend)->toBeTrue();

    $this->actingAs(plainUser())
        ->postJson(route('user.kiosk.lookup'), ['no_kp' => '900101011234'])
        ->assertOk()
        ->assertJsonPath('pegawai.is_attend', true);
});

it('keeps the details endpoint payload and adds jawatan_name', function (): void {
    kioskActiveSesi();
    $pegawai = kioskPegawai();

    $this->actingAs(plainUser())
        ->getJson(route('user.kehadiran.details', $pegawai))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('pegawai.no_kp', '900101011234')
        ->assertJsonPath('pegawai.no_kerusi', 12)
        ->assertJsonPath('pegawai.jawatan_name', 'Pegawai Kiosk')
        ->assertJsonStructure(['show_table_number', 'active_sesi_s_kehadiran', 'active_sesi_name', 'event_mode', 'pegawai' => ['ptj_name', 'no_meja', 'no_panggilan_lewat']]);
});

it('links the kiosk from the user sidebar', function (): void {
    $this->actingAs(plainUser())
        ->get(route('user.dashboard'))
        ->assertOk()
        ->assertSee(route('user.kiosk.index'), false);
});
