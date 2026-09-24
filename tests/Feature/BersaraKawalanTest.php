<?php

declare(strict_types=1);

use App\Models\Bersara;

it('forbids non admin from the bersara index', function (): void {
    $this->actingAs(plainUser())
        ->get(route('admin.kawalan.bersara.index'))
        ->assertForbidden();
});

it('allows admin to view the bersara index', function (): void {
    $this->actingAs(adminUser())
        ->get(route('admin.kawalan.bersara.index'))
        ->assertOk();
});

it('creates a bersara record', function (): void {
    $this->actingAs(adminUser())
        ->postJson(route('admin.kawalan.bersara.store'), ['jenis_bersara' => 'Wajib'])
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('bersaras', ['jenis_bersara' => 'Wajib']);
});

it('rejects a duplicate jenis bersara', function (): void {
    Bersara::query()->create(['jenis_bersara' => 'Wajib']);

    $this->actingAs(adminUser())
        ->postJson(route('admin.kawalan.bersara.store'), ['jenis_bersara' => 'Wajib'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['jenis_bersara']);
});

it('updates a bersara record', function (): void {
    $bersara = Bersara::query()->create(['jenis_bersara' => 'Wajib']);

    $this->actingAs(adminUser())
        ->putJson(route('admin.kawalan.bersara.update', $bersara), ['jenis_bersara' => 'Pilihan'])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($bersara->fresh()->jenis_bersara)->toBe('Pilihan');
});

it('deletes a bersara record and nulls the fk on affected pegawai', function (): void {
    $bersara = Bersara::query()->create(['jenis_bersara' => 'Wajib']);
    $pegawai = createPegawaiForTest();
    $pegawai->update(['bersara_id' => $bersara->id]);

    $this->actingAs(adminUser())
        ->deleteJson(route('admin.kawalan.bersara.destroy', $bersara))
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('bersaras', ['id' => $bersara->id]);
    expect($pegawai->fresh()->bersara_id)->toBeNull();
});

it('lists bersara records in the datatable', function (): void {
    Bersara::query()->create(['jenis_bersara' => 'Wajib']);

    $response = $this->actingAs(adminUser())
        ->getJson(route('admin.kawalan.bersara.datatable'))
        ->assertOk();

    expect($response->json('data.0.jenis_bersara'))->toBe('Wajib');
    expect($response->json('data.0.action'))->toContain('js-edit-bersara');
});
