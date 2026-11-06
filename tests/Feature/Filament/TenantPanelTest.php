<?php

use App\Models\User;
use App\Modules\Tenancy\Domain\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

/** 租户 Panel 访问边界（canAccessPanel + ResolveTenantContext 可信解析）。 */
test('unauthenticated requests to the tenant panel redirect to login', function (): void {
    /** @var TestCase $this */
    $this->get('/tenant')
        ->assertRedirect(route('filament.tenant.auth.login'));
});

test('a tenant user with an active tenant can access the tenant panel', function (): void {
    /** @var TestCase $this */
    $tenant = Tenant::factory()->create();

    $this->actingAs(User::factory()->tenantUser($tenant)->create())
        ->get('/tenant')
        ->assertOk();
});

test('a tenant user without a bound tenant cannot access the tenant panel', function (): void {
    /** @var TestCase $this */
    $this->actingAs(User::factory()->tenantUser()->create())
        ->get('/tenant')
        ->assertForbidden();
});

test('a platform user cannot access the tenant panel', function (): void {
    /** @var TestCase $this */
    $this->actingAs(User::factory()->platform()->create())
        ->get('/tenant')
        ->assertForbidden();
});

test('a tenant user of a disabled tenant cannot access the tenant panel', function (): void {
    /** @var TestCase $this */
    $tenant = Tenant::factory()->disabled()->create();

    $this->actingAs(User::factory()->tenantUser($tenant)->create())
        ->get('/tenant')
        ->assertForbidden();
});
