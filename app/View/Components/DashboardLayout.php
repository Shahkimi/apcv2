<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class DashboardLayout extends Component
{
    public function __construct(
        public string $title = '',
        public string $role = 'user',
    ) {}

    public function render(): View
    {
        return view('layouts.dashboard');
    }
}
