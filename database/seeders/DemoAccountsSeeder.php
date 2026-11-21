<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Models\Admin;
use App\Modules\Tenancy\Domain\Tenant;
use App\Modules\Tenancy\Domain\TenantStatus;
use App\Modules\Tenancy\Domain\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Demo / local accounts for the three Filament panels.
 *
 *   /admin    admin@local.dev      admin123456     (admins table, "admin" guard, super_admin role)
 *   /platform platform@local.dev   platform123     (users, role=platform)
 *   /tenant   tenant@local.dev     tenant123456    (users, role=tenant + active tenant)
 *
 * The admin also gets the `super_admin` role (guard `admin`): the Admin panel
 * gates every resource behind permission checks (`HasResourcePermission`), and
 * that role bypasses them, so the demo account can open all resource pages.
 *
 * Idempotent: uses firstOrCreate, safe to run after `migrate:fresh`.
 */
class DemoAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Admin::firstOrCreate(
            ['email' => 'admin@local.dev'],
            ['name' => 'Super Admin', 'password' => 'admin123456'],
        );

        $role = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'admin'],
            ['guard_name' => 'admin'],
        );

        if (! $admin->hasRole($role->name, 'admin')) {
            $admin->assignRole($role);
        }
        $admin->forgetCachedPermissions();

        // RoleResource checks `can('role.*')` directly without a super_admin
        // bypass, so grant those permissions to the super_admin role too.
        foreach (['role.view', 'role.create', 'role.edit', 'role.delete'] as $perm) {
            $p = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'admin'], ['guard_name' => 'admin']);
            if (! $role->hasPermissionTo($p)) {
                $role->givePermissionTo($p);
            }
        }
        $role->forgetCachedPermissions();

        User::firstOrCreate(
            ['email' => 'platform@local.dev'],
            ['name' => 'Platform Operator', 'password' => 'platform123', 'role' => UserRole::Platform],
        );

        $tenant = Tenant::firstOrCreate(
            ['name' => 'Demo Tenant'],
            ['status' => TenantStatus::Active],
        );

        User::firstOrCreate(
            ['email' => 'tenant@local.dev'],
            [
                'name' => 'Tenant Owner',
                'password' => 'tenant123456',
                'role' => UserRole::Tenant,
                'tenant_id' => $tenant->id,
            ],
        );
    }
}
