<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Kehadiran\AbstractKehadiranController;

class KehadiranController extends AbstractKehadiranController
{
    protected function bladeNamespace(): string
    {
        return 'user';
    }
}
