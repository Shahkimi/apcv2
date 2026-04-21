<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('media::dashboard', [
            'title' => __('Media Dashboard'),
            'stats' => [
                ['label' => __('Total files'), 'value' => '1,248', 'hint' => __('Library')],
                ['label' => __('Storage'), 'value' => '186 GB', 'hint' => __('Used')],
                ['label' => __('Uploads (7d)'), 'value' => '42', 'hint' => __('Recent')],
                ['label' => __('Processing'), 'value' => '3', 'hint' => __('Queue')],
            ],
            'recentRows' => [
                ['IMG-902', 'hero-banner.jpg', '2026-04-19', __('Published')],
                ['VID-441', 'promo-cut.mp4', '2026-04-18', __('Encoding')],
                ['DOC-118', 'press-kit.pdf', '2026-04-17', __('Draft')],
            ],
            'chartData' => [
                'sales' => [
                    'categories' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    'series' => [28, 35, 42, 38, 45, 52],
                ],
                'category' => [
                    'labels' => [__('Images'), __('Video'), __('Docs')],
                    'series' => [52, 28, 20],
                ],
                'traffic' => [
                    'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                    'values' => [22, 28, 24, 30, 26],
                ],
                'distribution' => [
                    'labels' => [__('Public'), __('Internal'), __('Archive')],
                    'values' => [45, 35, 20],
                ],
            ],
            'quickActions' => [
                ['label' => __('Upload'), 'icon' => 'ri-upload-cloud-line'],
                ['label' => __('Gallery'), 'icon' => 'ri-gallery-line'],
                ['label' => __('Reports'), 'icon' => 'ri-bar-chart-line'],
            ],
        ]);
    }
}
