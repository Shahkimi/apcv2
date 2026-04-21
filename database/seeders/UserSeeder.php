<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['username' => 'user'],
            [
                'name' => 'Test User',
                'email' => 'user@example.com',
                'password' => Hash::make('password'),
                'role' => User::ROLE_USER,
            ]
        );

        User::query()->updateOrCreate(
            ['username' => 'media'],
            [
                'name' => 'Test Media',
                'email' => 'media@example.com',
                'password' => Hash::make('password'),
                'role' => User::ROLE_MEDIA,
            ]
        );

        User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Test Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
            ]
        );
    }
}
