<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kehadiran\Concerns;

use App\Models\Pegawai;

trait RendersOfficerCell
{
    protected function renderOfficerCell(Pegawai $pegawai, bool $showKp = true): string
    {
        $nama = e($pegawai->nama);
        $kpLine = '';
        if ($showKp) {
            $kp = e((string) ($pegawai->no_kp ?? '—'));
            $kpLabel = e(__('No. KP'));
            $kpLine = '<p class="kawalan-dt-officer-kp-line mt-1">'
                .'<span class="kawalan-dt-officer-kp-label">'.$kpLabel.'</span>'
                .'<span class="kawalan-dt-officer-kp">'.$kp.'</span>'
                .'</p>';
        }

        $jawatanLine = '';
        $descJawatan = $pegawai->jawatan?->desc_jawatan;
        if (filled($descJawatan)) {
            $jawatanLine = '<p class="kawalan-dt-officer-jawatan">'.e((string) $descJawatan).'</p>';
        }

        return '<div class="kawalan-dt-officer flex max-w-[20rem] items-start gap-3">'
            .'<div class="min-w-0 flex-1">'
            .'<p class="kawalan-dt-officer-name leading-snug">'.$nama.'</p>'
            .$kpLine
            .$jawatanLine
            .'</div>'
            .'</div>';
    }
}
