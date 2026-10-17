<?php

declare(strict_types=1);

use App\Models\AnnouncedOfficer;
use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Pegawai;
use App\Models\Ptj;
use App\Models\SesiMajlis;
use App\Models\User;
use App\Services\Report\ReportPreviewTableService;
use Illuminate\Support\Facades\DB;

/**
 * Default call-out order (RSVP → walk-in → OKU) gives the queue: Rsvp 1, Rsvp 2, Walkin, Oku.
 */
function seedLateQueue(bool $lewat = true): SesiMajlis
{
    $sesi = SesiMajlis::query()->create([
        'sesi' => 'Sesi Lewat',
        'is_active' => true,
        'is_late' => $lewat,
        'countdown_start_late' => 501,
        'seat_offset' => 0,
        's_kehadiran' => SesiMajlis::S_KEHADIRAN_PAGI,
    ]);
    Ptj::query()->create(['nama_ptj' => 'PTJ Lewat']);
    Jawatan::query()->create(['desc_jawatan' => 'Pegawai Lewat']);
    Gred::query()->create(['desc_gred' => 'Gred Lewat']);

    lateQueuePegawai($sesi, 'Rsvp 1', ['no_kerusi' => 1, 'hadir_at' => now()->subHour()]);
    lateQueuePegawai($sesi, 'Rsvp 2', ['no_kerusi' => 2, 'hadir_at' => now()->subHour()]);
    lateQueuePegawai($sesi, 'Walkin', ['rsvp' => false, 'is_late' => true, 'no_panggilan_lewat' => 501, 'hadir_at' => now()->subHour()]);
    lateQueuePegawai($sesi, 'Oku', ['is_oku' => true, 'no_kerusi' => 3, 'hadir_at' => now()->subHour()]);

    return $sesi;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function lateQueuePegawai(SesiMajlis $sesi, string $nama, array $attributes = []): Pegawai
{
    return Pegawai::factory()->create([
        'nama' => $nama,
        'sesi_majlis_id' => $sesi->id,
        'rsvp' => true,
        'is_oku' => false,
        'is_attend' => true,
        'is_late' => false,
        'no_kerusi' => null,
        'no_panggilan_lewat' => null,
        's_kehadiran' => Pegawai::S_KEHADIRAN_PAGI,
        'hadir_at' => now(),
        ...$attributes,
    ]);
}

function announceLateQueue(SesiMajlis $sesi, string ...$names): void
{
    foreach ($names as $nama) {
        AnnouncedOfficer::query()->create([
            'scope_key' => 'sesi:'.$sesi->id,
            'sesi_majlis_id' => $sesi->id,
            'pegawai_id' => Pegawai::query()->where('nama', $nama)->value('id'),
            'announced_at' => now(),
        ]);
    }
}

/**
 * @return list<string>
 */
function lateQueueNames(User $media, SesiMajlis $sesi): array
{
    return collect(test()->actingAs($media)
        ->getJson(route('media.senarai.slides', ['sesi_id' => $sesi->id]))
        ->assertOk()
        ->json('officers'))->pluck('nama')->all();
}

it('puts a late arrival whose slot was passed after oku', function (): void {
    $sesi = seedLateQueue();
    announceLateQueue($sesi, 'Rsvp 1', 'Rsvp 2', 'Walkin', 'Oku');

    // RSVP slot is behind the presentation (OKU on screen).
    lateQueuePegawai($sesi, 'Rsvp Lewat', ['is_late' => true, 'no_kerusi' => 4, 'no_panggilan_lewat' => 502]);

    expect(lateQueueNames(mediaUser(), $sesi))->toBe(['Rsvp 1', 'Rsvp 2', 'Walkin', 'Oku', 'Rsvp Lewat']);
});

it('keeps a late arrival in their normal slot while it is still ahead', function (): void {
    $sesi = seedLateQueue();
    announceLateQueue($sesi, 'Rsvp 1');

    lateQueuePegawai($sesi, 'Oku Lewat', ['is_oku' => true, 'is_late' => true, 'no_kerusi' => 5, 'no_panggilan_lewat' => 502]);

    expect(lateQueueNames(mediaUser(), $sesi))->toBe(['Rsvp 1', 'Rsvp 2', 'Walkin', 'Oku', 'Oku Lewat']);
});

it('orders passed late arrivals by arrival time and keeps them in place once announced', function (): void {
    $sesi = seedLateQueue();
    $media = mediaUser();
    announceLateQueue($sesi, 'Rsvp 1', 'Rsvp 2', 'Walkin');

    lateQueuePegawai($sesi, 'Rsvp Kedua', ['is_late' => true, 'no_kerusi' => 4, 'hadir_at' => now()]);
    lateQueuePegawai($sesi, 'Rsvp Pertama', ['is_late' => true, 'no_kerusi' => 9, 'hadir_at' => now()->subMinute()]);

    expect(lateQueueNames($media, $sesi))->toBe(['Rsvp 1', 'Rsvp 2', 'Walkin', 'Oku', 'Rsvp Pertama', 'Rsvp Kedua']);

    announceLateQueue($sesi, 'Oku', 'Rsvp Pertama');

    expect(lateQueueNames($media, $sesi))->toBe(['Rsvp 1', 'Rsvp 2', 'Walkin', 'Oku', 'Rsvp Pertama', 'Rsvp Kedua']);
});

it('uses the normal calling order when lewat is off', function (): void {
    $sesi = seedLateQueue(lewat: false);
    announceLateQueue($sesi, 'Rsvp 1', 'Rsvp 2', 'Walkin', 'Oku');

    lateQueuePegawai($sesi, 'Rsvp Baru', ['no_kerusi' => 4]);

    expect(lateQueueNames(mediaUser(), $sesi))->toBe(['Rsvp 1', 'Rsvp 2', 'Rsvp Baru', 'Walkin', 'Oku']);
});

it('gives the mc list the same order as the presentation', function (): void {
    $sesi = seedLateQueue();
    announceLateQueue($sesi, 'Rsvp 1', 'Rsvp 2', 'Walkin', 'Oku');
    lateQueuePegawai($sesi, 'Rsvp Lewat', ['is_late' => true, 'no_kerusi' => 4]);

    $mc = app(ReportPreviewTableService::class)->mcList($sesi)->pluck('nama')->all();

    expect($mc)->toBe(lateQueueNames(mediaUser(), $sesi));
});

it('only sends slides when the queue version changed', function (): void {
    $sesi = seedLateQueue();
    $media = mediaUser();

    $first = $this->actingAs($media)
        ->getJson(route('media.senarai.slides', ['sesi_id' => $sesi->id]))
        ->assertOk()
        ->assertJsonPath('changed', true)
        ->assertJsonCount(4, 'officers');

    $version = $first->json('version');

    $this->getJson(route('media.senarai.slides', ['sesi_id' => $sesi->id, 'version' => $version]))
        ->assertOk()
        ->assertJsonPath('changed', false)
        ->assertJsonMissingPath('officers');

    lateQueuePegawai($sesi, 'Rsvp Lewat', ['is_late' => true, 'no_kerusi' => 4]);

    $this->getJson(route('media.senarai.slides', ['sesi_id' => $sesi->id, 'version' => $version]))
        ->assertOk()
        ->assertJsonPath('changed', true)
        ->assertJsonCount(5, 'officers');
});

it('forbids non media users from the slides feed', function (): void {
    $this->actingAs(plainUser())
        ->getJson(route('media.senarai.slides'))
        ->assertForbidden();
});

it('resets the presentation position on attendance reset', function (): void {
    $sesi = seedLateQueue();
    DB::table('senarai_progress')->insert([
        'scope_key' => 'sesi:'.$sesi->id,
        'sesi_majlis_id' => $sesi->id,
        'current_index' => 3,
        'last_updated_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs(adminUser())
        ->postJson(route('admin.kawalan.system.reset'))
        ->assertOk();

    expect(DB::table('senarai_progress')->value('current_index'))->toBe(0);
});
