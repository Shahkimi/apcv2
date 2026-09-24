<?php

declare(strict_types=1);

use App\Models\SystemSetting;
use App\Services\EventModeService;

it('defaults to apc event mode', function (): void {
    expect(app(EventModeService::class)->current())->toBe(EventModeService::MODE_APC);
    expect(app(EventModeService::class)->isJasamu())->toBeFalse();
});

it('lets admin switch event mode to jasamu', function (): void {
    $admin = adminUser();

    $this->actingAs($admin)
        ->postJson(route('admin.kawalan.sesi-majlis.event-mode'), ['mode' => 'jasamu'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('mode', 'jasamu');

    expect(SystemSetting::getValue('event.mode'))->toBe('jasamu');
});

it('rejects an invalid event mode', function (): void {
    $admin = adminUser();

    $this->actingAs($admin)
        ->postJson(route('admin.kawalan.sesi-majlis.event-mode'), ['mode' => 'foo'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['mode']);
});

it('forbids non admin from switching event mode', function (): void {
    $this->actingAs(plainUser())
        ->postJson(route('admin.kawalan.sesi-majlis.event-mode'), ['mode' => 'jasamu'])
        ->assertForbidden();
});

it('shows the mod acara tab and bersara link on the sesi majlis index', function (): void {
    $admin = adminUser();

    $this->actingAs($admin)
        ->get(route('admin.kawalan.sesi-majlis.index'))
        ->assertOk()
        ->assertSee('Mod Acara')
        ->assertSee(route('admin.kawalan.bersara.index'), false);
});
