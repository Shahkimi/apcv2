<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Dashboard\AbstractDashboardController;

class DashboardController extends AbstractDashboardController
{
    protected function role(): string
    {
        return 'user';
    }

    protected function title(): string
    {
        return __('User Dashboard');
    }

    protected function subtitle(): string
    {
        return __('Ringkasan pengesahan kehadiran');
    }

    protected function payload(?int $sesiId): array
    {
        return $this->dashboardStats->forUser($sesiId);
    }

    protected function layout(): array
    {
        return [
            'hero' => ['key' => 'hadir', 'label' => __('Kehadiran'), 'icon' => 'ri-user-follow-line'],
            'cards' => [
                ['key' => 'rsvp', 'label' => __('RSVP'), 'icon' => 'ri-mail-check-line'],
                ['key' => 'belum_hadir', 'label' => __('Belum hadir'), 'icon' => 'ri-user-unfollow-line'],
                ['key' => 'lewat', 'label' => __('Lewat'), 'icon' => 'ri-time-line'],
            ],
            'charts' => [
                ['key' => 'timeline', 'title' => __('Ketibaan mengikut masa (15 minit)'), 'size' => 'lg'],
                ['key' => 'sesi', 'title' => __('Kehadiran mengikut sesi'), 'size' => 'sm'],
            ],
            'tables' => [
                [
                    'key' => 'checkins',
                    'title' => __('Pengesahan kehadiran terkini'),
                    'columns' => [__('Nama'), __('PTJ'), __('Sesi'), __('Masa')],
                ],
            ],
            'statusRows' => [
                ['key' => 'sesi', 'label' => __('Sesi di udara')],
                ['key' => 'late', 'label' => __('Jenis sesi')],
                ['key' => 'next_late', 'label' => __('No. panggilan lewat seterusnya')],
            ],
            'actions' => [
                ['label' => __('Pengesahan kehadiran'), 'icon' => 'ri-user-follow-line', 'href' => route('user.kehadiran.index')],
            ],
        ];
    }
}
