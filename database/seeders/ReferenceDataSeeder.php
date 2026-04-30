<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Pegawai;
use App\Models\Ptj;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SesiMajlisMejaSeeder::class);

        $ptj1 = Ptj::query()->create(['nama_ptj' => 'Pejabat Pentadbiran']);
        $ptj2 = Ptj::query()->create(['nama_ptj' => 'Bahagian Teknologi Maklumat']);

        $j1 = Jawatan::query()->create(['desc_jawatan' => 'Pegawai Tadbir']);
        $j2 = Jawatan::query()->create(['desc_jawatan' => 'Pembantu Tadbir']);
        $j3 = Jawatan::query()->create(['desc_jawatan' => 'Juruteknik Komputer']);

        $g1 = Gred::query()->create(['desc_gred' => 'N41']);
        $g2 = Gred::query()->create(['desc_gred' => 'N29']);
        $g3 = Gred::query()->create(['desc_gred' => 'N22']);

        $ptjIds = [$ptj1->id, $ptj2->id];
        $jawatanIds = [$j1->id, $j2->id, $j3->id];
        $gredIds = [$g1->id, $g2->id, $g3->id];

        Pegawai::factory()
            ->count(100)
            ->sequence(function (Sequence $sequence): array {
                $seat = $sequence->index + 1;

                return [
                    'sesi_majlis_id' => null,
                    'no_kerusi' => $seat,
                    'no_sijil' => (string) $seat,
                    'rsvp' => $sequence->index < 90,
                    'no_panggilan_lewat' => null,
                    'no_meja' => null,
                    'is_attend' => false,
                    'is_late' => false,
                    's_kehadiran' => $sequence->index < 50
                        ? Pegawai::S_KEHADIRAN_PAGI
                        : Pegawai::S_KEHADIRAN_PETANG,
                ];
            })
            ->state(fn () => [
                'ptj_id' => fake()->randomElement($ptjIds),
                'jawatan_id' => fake()->randomElement($jawatanIds),
                'gred_id' => fake()->randomElement($gredIds),
            ])
            ->create();

    }
}
