<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Dashboard\AbstractDashboardController;
use App\Services\EventModeService;

class DashboardController extends AbstractDashboardController
{
    protected function role(): string
    {
        return 'admin';
    }

    protected function title(): string
    {
        return __('Admin Dashboard');
    }

    protected function subtitle(): string
    {
        return __('Ringkasan kehadiran, pengumuman dan sesi majlis');
    }

    protected function payload(?int $sesiId): array
    {
        return $this->dashboardStats->forAdmin($sesiId);
    }

    protected function layout(): array
    {
        $groupTitle = app(EventModeService::class)->isJasamu()
            ? __('Kehadiran mengikut jenis bersara')
            : __('10 PTJ teratas mengikut kehadiran');

        return [
            'hero' => ['key' => 'hadir', 'label' => __('Kehadiran'), 'icon' => 'ri-user-follow-line'],
            'cards' => [
                ['key' => 'total', 'label' => __('Jumlah pegawai'), 'icon' => 'ri-team-line'],
                ['key' => 'rsvp', 'label' => __('RSVP'), 'icon' => 'ri-mail-check-line'],
                ['key' => 'belum_hadir', 'label' => __('Belum hadir'), 'icon' => 'ri-user-unfollow-line'],
                ['key' => 'lewat', 'label' => __('Lewat'), 'icon' => 'ri-time-line'],
                ['key' => 'announced', 'label' => __('Telah diumumkan'), 'icon' => 'ri-megaphone-line'],
            ],
            'charts' => [
                ['key' => 'timeline', 'title' => __('Ketibaan mengikut masa (15 minit)'), 'size' => 'lg'],
                ['key' => 'status', 'title' => __('Status kehadiran'), 'size' => 'sm'],
                ['key' => 'sesi', 'title' => __('Kehadiran mengikut sesi'), 'size' => 'md'],
                ['key' => 'group', 'title' => $groupTitle, 'size' => 'md'],
            ],
            'tables' => [
                [
                    'key' => 'checkins',
                    'title' => __('Pengesahan kehadiran terkini'),
                    'columns' => [__('Nama'), __('PTJ'), __('Sesi'), __('Masa')],
                ],
            ],
            'statusRows' => [
                ['key' => 'mode', 'label' => __('Mod acara')],
                ['key' => 'sesi', 'label' => __('Sesi di udara')],
                ['key' => 'late', 'label' => __('Jenis sesi')],
                ['key' => 'next_late', 'label' => __('No. panggilan lewat seterusnya')],
                ['key' => 'users', 'label' => __('Pengguna')],
            ],
            'actions' => [
                ['label' => __('Kehadiran'), 'icon' => 'ri-user-follow-line', 'href' => route('admin.kehadiran.index')],
                ['label' => __('Paparan'), 'icon' => 'ri-slideshow-line', 'href' => route('admin.paparan.index')],
                ['label' => __('Analitik senarai'), 'icon' => 'ri-line-chart-line', 'href' => route('admin.senarai.analytics')],
                ['label' => __('Laporan'), 'icon' => 'ri-file-chart-line', 'href' => route('admin.report.index')],
                ['label' => __('Sesi majlis'), 'icon' => 'ri-calendar-event-line', 'href' => route('admin.kawalan.sesi-majlis.index')],
                ['label' => __('Pengguna'), 'icon' => 'ri-team-line', 'href' => route('admin.kawalan.user.index')],
            ],
        ];
    }
}
