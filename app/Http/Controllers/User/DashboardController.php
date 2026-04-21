<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('user::dashboard', [
            'title' => __('User Dashboard'),
            'stats' => [
                ['label' => __('My requests'), 'value' => '12', 'hint' => __('Open items')],
                ['label' => __('Completed'), 'value' => '48', 'hint' => __('This year')],
                ['label' => __('Hours saved'), 'value' => '36h', 'hint' => __('Est.')],
                ['label' => __('Account'), 'value' => __('Active'), 'hint' => __('Status')],
            ],
            'recentRows' => [
                ['#1042', __('Document review'), '2026-04-18', __('Done')],
                ['#1041', __('Access request'), '2026-04-17', __('Pending')],
                ['#1040', __('Support ticket'), '2026-04-16', __('Open')],
            ],
            'chartData' => [
                'sales' => [
                    'categories' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    'series' => [12, 18, 15, 22, 19, 24],
                ],
                'category' => [
                    'labels' => [__('Tasks'), __('Requests'), __('Other')],
                    'series' => [44, 32, 24],
                ],
                'traffic' => [
                    'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                    'values' => [12, 19, 14, 22, 18],
                ],
                'distribution' => [
                    'labels' => [__('Web'), __('Mobile'), __('Desk')],
                    'values' => [55, 25, 20],
                ],
            ],
            'quickActions' => [
                ['label' => __('New request'), 'icon' => 'ri-add-line'],
                ['label' => __('View profile'), 'icon' => 'ri-user-line'],
                ['label' => __('Help'), 'icon' => 'ri-question-line'],
            ],
        ]);
    }
}
