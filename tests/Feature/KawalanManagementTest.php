<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KawalanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_kawalan_ptj_index(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user)
            ->get(route('admin.kawalan.ptj.index'))
            ->assertForbidden();
    }

    public function test_admin_can_access_kawalan_ptj_index(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.kawalan.ptj.index'))
            ->assertOk();
    }

    public function test_admin_can_access_kawalan_meja_index(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.kawalan.meja.index'))
            ->assertOk();
    }

    public function test_admin_can_access_kawalan_sesi_majlis_index(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.kawalan.sesi-majlis.index'))
            ->assertOk();
    }
}
