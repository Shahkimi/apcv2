<?php

declare(strict_types=1);

use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Pegawai;
use App\Models\Ptj;

beforeEach(function (): void {
    Ptj::query()->create(['nama_ptj' => 'PTJ Ujian']);
    Jawatan::query()->create(['desc_jawatan' => 'Pegawai']);
    Gred::query()->create(['desc_gred' => 'Gred Ujian']);
});

/**
 * @param  array<string, mixed>  $attributes
 */
function rsvpPegawaiForTest(array $attributes = []): Pegawai
{
    return Pegawai::factory()->create([
        'rsvp' => false,
        'no_kerusi' => null,
        'is_attend' => false,
        ...$attributes,
    ]);
}

it('forbids non admin from the rsvp page', function (): void {
    $this->actingAs(plainUser())
        ->get(route('admin.rsvp.index'))
        ->assertForbidden();
});

it('allows admin to view the rsvp page', function (): void {
    $this->actingAs(adminUser())
        ->get(route('admin.rsvp.index'))
        ->assertOk()
        ->assertSee(__('Kemas kini RSVP'));
});

it('lists pegawai and filters by rsvp', function (): void {
    rsvpPegawaiForTest(['nama' => 'Ali Hadir', 'rsvp' => true, 'no_kerusi' => 1]);
    rsvpPegawaiForTest(['nama' => 'Abu Tidak']);

    $all = $this->actingAs(adminUser())
        ->getJson(route('admin.rsvp.datatable'))
        ->assertOk();

    expect($all->json('recordsTotal'))->toBe(2);
    expect($all->json('data.0.nama'))->toContain('Abu Tidak');
    expect($all->json('data.0.action'))->toContain('js-edit-rsvp');

    $tidak = $this->getJson(route('admin.rsvp.datatable', ['rsvp' => '0']))->assertOk();

    expect($tidak->json('recordsFiltered'))->toBe(1);
    expect($tidak->json('data.0.nama'))->toContain('Abu Tidak');
});

it('disables the edit button for attended pegawai', function (): void {
    rsvpPegawaiForTest(['is_attend' => true]);

    $response = $this->actingAs(adminUser())
        ->getJson(route('admin.rsvp.datatable'))
        ->assertOk();

    expect($response->json('data.0.action'))->toContain('disabled');
});

it('suggests the seat after the last rsvp seat', function (): void {
    rsvpPegawaiForTest(['rsvp' => true, 'no_kerusi' => 100]);
    rsvpPegawaiForTest(['rsvp' => true, 'no_kerusi' => 42]);
    $pegawai = rsvpPegawaiForTest();

    $this->actingAs(adminUser())
        ->getJson(route('admin.rsvp.show', $pegawai))
        ->assertOk()
        ->assertJsonPath('last_seat', 100)
        ->assertJsonPath('suggested_seat', 101);
});

it('skips seats already held by walk-ins when suggesting', function (): void {
    rsvpPegawaiForTest(['rsvp' => true, 'no_kerusi' => 100]);
    rsvpPegawaiForTest(['no_kerusi' => 101]);
    rsvpPegawaiForTest(['no_kerusi' => 102]);
    $pegawai = rsvpPegawaiForTest();

    $this->actingAs(adminUser())
        ->getJson(route('admin.rsvp.show', $pegawai))
        ->assertOk()
        ->assertJsonPath('suggested_seat', 103);
});

it('moves a pegawai into rsvp with a seat', function (): void {
    $pegawai = rsvpPegawaiForTest();

    $this->actingAs(adminUser())
        ->putJson(route('admin.rsvp.update', $pegawai), ['rsvp' => 1, 'no_kerusi' => 101])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('counts.rsvp_hadir', 1)
        ->assertJsonPath('last_seat', 101);

    $fresh = $pegawai->fresh();
    expect($fresh->rsvp)->toBeTrue();
    expect($fresh->no_kerusi)->toBe(101);
});

it('clears the seat when rsvp is set to tidak hadir', function (): void {
    $pegawai = rsvpPegawaiForTest(['rsvp' => true, 'no_kerusi' => 7]);

    $this->actingAs(adminUser())
        ->putJson(route('admin.rsvp.update', $pegawai), ['rsvp' => 0, 'no_kerusi' => 7])
        ->assertOk();

    $fresh = $pegawai->fresh();
    expect($fresh->rsvp)->toBeFalse();
    expect($fresh->no_kerusi)->toBeNull();
});

it('requires a seat for rsvp hadir', function (): void {
    $pegawai = rsvpPegawaiForTest();

    $this->actingAs(adminUser())
        ->putJson(route('admin.rsvp.update', $pegawai), ['rsvp' => 1])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['no_kerusi']);
});

it('rejects a seat used by another pegawai', function (): void {
    rsvpPegawaiForTest(['nama' => 'Pemilik Kerusi', 'rsvp' => true, 'no_kerusi' => 50]);
    $pegawai = rsvpPegawaiForTest();

    $this->actingAs(adminUser())
        ->putJson(route('admin.rsvp.update', $pegawai), ['rsvp' => 1, 'no_kerusi' => 50])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['no_kerusi'])
        ->assertJsonFragment(['no_kerusi' => [__('No. kerusi :seat sudah digunakan oleh :nama.', ['seat' => 50, 'nama' => 'Pemilik Kerusi'])]]);

    expect($pegawai->fresh()->rsvp)->toBeFalse();
});

it('keeps the own seat when re-saving an rsvp pegawai', function (): void {
    $pegawai = rsvpPegawaiForTest(['rsvp' => true, 'no_kerusi' => 50]);

    $this->actingAs(adminUser())
        ->putJson(route('admin.rsvp.update', $pegawai), ['rsvp' => 1, 'no_kerusi' => 50])
        ->assertOk();
});

it('locks pegawai who already attended', function (): void {
    $pegawai = rsvpPegawaiForTest(['is_attend' => true]);

    $this->actingAs(adminUser())
        ->putJson(route('admin.rsvp.update', $pegawai), ['rsvp' => 1, 'no_kerusi' => 10])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['rsvp']);

    $fresh = $pegawai->fresh();
    expect($fresh->rsvp)->toBeFalse();
    expect($fresh->no_kerusi)->toBeNull();
});
