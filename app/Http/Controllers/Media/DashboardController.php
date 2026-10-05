<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Dashboard\AbstractDashboardController;

class DashboardController extends AbstractDashboardController
{
    protected function role(): string
    {
        return 'media';
    }

    protected function title(): string
    {
        return __('Media Dashboard');
    }

    protected function subtitle(): string
    {
        return __('Kemajuan pengumuman pegawai');
    }

    protected function payload(?int $sesiId): array
    {
        return $this->dashboardStats->forMedia($sesiId);
    }

    protected function layout(): array
    {
        return [
            'hero' => ['key' => 'announced', 'label' => __('Telah diumumkan'), 'icon' => 'ri-megaphone-line'],
            'cards' => [
                ['key' => 'hadir', 'label' => __('Hadir'), 'icon' => 'ri-user-follow-line'],
                ['key' => 'baki', 'label' => __('Baki diumumkan'), 'icon' => 'ri-hourglass-line'],
                ['key' => 'progress', 'label' => __('Kemajuan'), 'icon' => 'ri-line-chart-line'],
            ],
            'charts' => [
                ['key' => 'announce_timeline', 'title' => __('Pengumuman mengikut masa (15 minit)'), 'size' => 'lg'],
                ['key' => 'announce_split', 'title' => __('Diumumkan berbanding baki'), 'size' => 'sm'],
            ],
            'tables' => [
                [
                    'key' => 'announcements',
                    'title' => __('Pengumuman terkini'),
                    'columns' => [__('Nama'), __('PTJ'), __('Masa')],
                ],
            ],
            'statusRows' => [
                ['key' => 'sesi', 'label' => __('Sesi di udara')],
                ['key' => 'late', 'label' => __('Jenis sesi')],
                ['key' => 'next_late', 'label' => __('No. panggilan lewat seterusnya')],
            ],
            'actions' => [
                ['label' => __('Persembahan senarai'), 'icon' => 'ri-slideshow-3-line', 'href' => route('media.senarai.present')],
                ['label' => __('Paparan'), 'icon' => 'ri-slideshow-line', 'href' => route('media.paparan.index')],
                ['label' => __('Analitik senarai'), 'icon' => 'ri-line-chart-line', 'href' => route('media.senarai.analytics')],
                ['label' => __('Tetapan persembahan'), 'icon' => 'ri-palette-line', 'href' => route('media.kawalan.presentation.index')],
            ],
        ];
    }
}
