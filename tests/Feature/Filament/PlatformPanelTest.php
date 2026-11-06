<?php

use App\Models\User;
use App\Modules\Tenancy\Domain\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

/** 平台 Panel 访问边界（双 Panel 基线；FilamentUser::canAccessPanel 为安全边界）。 */
test('unauthenticated requests to the platform panel redirect to login', function (): void {
    /** @var TestCase $this */
    $this->get('/platform')
        ->assertRedirect(route('filament.platform.auth.login'));
});

test('a platform user can access the platform panel', function (): void {
    /** @var TestCase $this */
    $this->actingAs(User::factory()->platform()->create())
        ->get('/platform')
        ->assertOk();
});

test('a tenant user cannot access the platform panel', function (): void {
    /** @var TestCase $this */
    $tenant = Tenant::factory()->create();

    $this->actingAs(User::factory()->tenantUser($tenant)->create())
        ->get('/platform')
        ->assertForbidden();
});
