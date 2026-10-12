<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class IdCounter
{
    /**
     * Next id becomes $max + 1. Call after the transaction commits: MySQL ALTER TABLE would implicitly commit mid-transaction.
     */
    public static function realign(string $table, int $max): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE `'.$table.'` AUTO_INCREMENT = '.($max + 1));
        } elseif ($driver === 'sqlite') {
            DB::table('sqlite_sequence')->where('name', $table)->update(['seq' => $max]);
        }
    }
}
