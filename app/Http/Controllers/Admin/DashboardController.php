<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin::dashboard', [
            'title' => __('Admin Dashboard'),
            'stats' => [
                ['label' => __('Users'), 'value' => '1,024', 'hint' => __('Registered')],
                ['label' => __('Revenue'), 'value' => 'RM 452k', 'hint' => __('YTD')],
                ['label' => __('Uptime'), 'value' => '99.98%', 'hint' => __('30d')],
                ['label' => __('Sessions'), 'value' => '312', 'hint' => __('Active')],
            ],
            'recentRows' => [
                ['#8821', __('New user signup'), '2026-04-19', __('Info')],
                ['#8820', __('Role change'), '2026-04-19', __('Audit')],
                ['#8819', __('Backup completed'), '2026-04-18', __('OK')],
            ],
            'chartData' => [
                'sales' => [
                    'categories' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    'series' => [120, 190, 160, 240, 210, 280],
                ],
                'category' => [
                    'labels' => [__('Product'), __('Services'), __('Licenses')],
                    'series' => [48, 30, 22],
                ],
                'traffic' => [
                    'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                    'values' => [40, 48, 42, 55, 50],
                ],
                'distribution' => [
                    'labels' => [__('APAC'), __('EMEA'), __('AMER')],
                    'values' => [40, 35, 25],
                ],
            ],
            'quickActions' => [
                ['label' => __('Users'), 'icon' => 'ri-team-line'],
                ['label' => __('Reports'), 'icon' => 'ri-file-chart-line'],
                ['label' => __('Settings'), 'icon' => 'ri-settings-3-line'],
            ],
        ]);
    }
}
