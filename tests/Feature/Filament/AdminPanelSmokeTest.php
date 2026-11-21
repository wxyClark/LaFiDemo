<?php

use App\Models\User;
use App\Modules\Models\Admin;
use App\Modules\Tenancy\Domain\Tenant;
use App\Modules\Tenancy\Domain\TenantStatus;
use App\Modules\Tenancy\Domain\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

/**
 * Integration smoke test: the three Filament panels render for the demo
 * accounts, and the super_admin can open every Admin resource index + create
 * page. Guards against Filament 3 -> 5 migration regressions (moved action
 * classes, renamed layout components, missing tables/columns, etc.).
 */
test('three panels render and admin resource pages open', function (): void {
    /** @var TestCase $this */

    // ---- seed accounts (mirrors DemoAccountsSeeder) ----
    $admin = Admin::create(['name' => 'Super Admin', 'email' => 'admin@local.dev', 'password' => 'admin123456']);
    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'admin'], ['guard_name' => 'admin']);
    $admin->assignRole($role);
    foreach (['role.view', 'role.create', 'role.edit', 'role.delete'] as $perm) {
        $p = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'admin'], ['guard_name' => 'admin']);
        if (! $role->hasPermissionTo($p)) {
            $role->givePermissionTo($p);
        }
    }
    $admin->forgetCachedPermissions();

    $platform = User::create(['name' => 'Platform Operator', 'email' => 'platform@local.dev', 'password' => 'platform123', 'role' => UserRole::Platform]);
    $tenant = Tenant::create(['name' => 'Demo Tenant', 'status' => TenantStatus::Active]);
    $tenantUser = User::create(['name' => 'Tenant Owner', 'email' => 'tenant@local.dev', 'password' => 'tenant123456', 'role' => UserRole::Tenant, 'tenant_id' => $tenant->id]);

    // ---- panel dashboards ----
    $checks = [
        ['admin', 'admin',  '/admin'],
        ['platform', 'web',   '/platform'],
        ['tenant', 'web',    '/tenant'],
    ];
    foreach ($checks as [$guard, $actorGuard, $path]) {
        $actor = $guard === 'admin' ? $admin : ($guard === 'platform' ? $platform : $tenantUser);
        $this->flushSession();          // isolate sessions like a fresh login
        $this->actingAs($actor, $actorGuard);
        $code = $this->get($path)->getStatusCode();
        echo sprintf("  panel %-10s %s\n", $guard, $code);
        expect($code)->toBe(200);
    }

    // ---- every admin resource index + create page ----
    $routes = app('router')->getRoutes();
    $index = collect($routes)->filter(fn ($r) => str_starts_with($r->getName() ?? '', 'filament.admin.resources.') && str_ends_with($r->getName(), '.index'))
        ->map(fn ($r) => trim($r->uri(), '/'))->unique()->values()->all();
    $create = collect($routes)->filter(fn ($r) => str_starts_with($r->getName() ?? '', 'filament.admin.resources.') && str_ends_with($r->getName(), '.create'))
        ->map(fn ($r) => trim($r->uri(), '/'))->unique()->values()->all();

    $this->actingAs($admin, 'admin');
    foreach (array_merge($index, $create) as $path) {
        try {
            $code = $this->get('/'.$path)->getStatusCode();
        } catch (Throwable $e) {
            $code = 'EXC:'.get_class($e).':'.substr($e->getMessage(), 0, 120);
        }
        expect($code)->toBe(200);
    }
});
