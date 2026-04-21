<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Pegawai;
use App\Models\Ptj;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $ptj1 = Ptj::query()->create(['nama_ptj' => 'Pejabat Pentadbiran']);
        $ptj2 = Ptj::query()->create(['nama_ptj' => 'Bahagian Teknologi Maklumat']);

        $j1 = Jawatan::query()->create(['desc_jawatan' => 'Pegawai Tadbir']);
        $j2 = Jawatan::query()->create(['desc_jawatan' => 'Pembantu Tadbir']);
        $j3 = Jawatan::query()->create(['desc_jawatan' => 'Juruteknik Komputer']);

        $g1 = Gred::query()->create(['desc_gred' => 'N41']);
        $g2 = Gred::query()->create(['desc_gred' => 'N29']);
        $g3 = Gred::query()->create(['desc_gred' => 'N22']);

        Pegawai::query()->create([
            'nama' => 'Ahmad bin Abdullah',
            'no_kp' => '800101015555',
            'ptj_id' => $ptj1->id,
            'jawatan_id' => $j1->id,
            'gred_id' => $g1->id,
            'rsvp' => true,
            'no_kerusi' => 1,
            'no_sijil' => '10',
            'is_attend' => true,
        ]);

        Pegawai::query()->create([
            'nama' => 'Siti binti Hassan',
            'no_kp' => '850505106666',
            'ptj_id' => $ptj2->id,
            'jawatan_id' => $j3->id,
            'gred_id' => $g3->id,
            'rsvp' => false,
            'no_kerusi' => 2,
            'no_sijil' => '20',
            'is_attend' => false,
        ]);

        Pegawai::query()->create([
            'nama' => 'Lim Wei Ming',
            'no_kp' => '900909077777',
            'ptj_id' => $ptj1->id,
            'jawatan_id' => $j2->id,
            'gred_id' => $g2->id,
            'rsvp' => false,
            'no_kerusi' => null,
            'no_sijil' => null,
            'is_attend' => false,
        ]);

    }
}
