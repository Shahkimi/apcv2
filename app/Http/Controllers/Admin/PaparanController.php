<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Kehadiran\AbstractPaparanController;

class PaparanController extends AbstractPaparanController
{
    protected function bladeNamespace(): string
    {
        return 'admin';
    }
}
