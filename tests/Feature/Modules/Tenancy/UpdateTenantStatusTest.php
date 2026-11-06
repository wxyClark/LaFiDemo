<?php

use App\Models\User;
use App\Modules\Tenancy\Application\UpdateTenantStatus;
use App\Modules\Tenancy\Domain\Tenant;
use App\Modules\Tenancy\Domain\TenantStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** UpdateTenantStatus：平台高危操作，写经用例 + 结构化审计日志。 */
test('the use case updates a tenant status and persists the change', function (): void {
    $tenant = Tenant::factory()->create();
    $operator = User::factory()->platform()->create();

    $updated = (new UpdateTenantStatus)(
        (int) $tenant->id,
        TenantStatus::Disabled,
        '合规问题',
        (int) $operator->id,
    );

    expect($updated->id)->toBe($tenant->id)
        ->and($updated->status)->toBe(TenantStatus::Disabled)
        ->and($updated->fresh()->status)->toBe(TenantStatus::Disabled)
        ->and($updated->fresh()->isEnabled())->toBeFalse();
});

test('the use case is a no-op when the status is unchanged', function (): void {
    $tenant = Tenant::factory()->create();

    (new UpdateTenantStatus)(
        (int) $tenant->id,
        TenantStatus::Active,
        '无变更',
        1,
    );

    expect($tenant->fresh()->status)->toBe(TenantStatus::Active);
});

/** TenantPolicy 平台分支：仅平台管理员可管理租户（RBAC 落地后收窄）。 */
test('only platform admins can manage tenants via the policy', function (): void {
    $tenant = Tenant::factory()->create();
    $platform = User::factory()->platform()->create();
    $tenantUser = User::factory()->tenantUser($tenant)->create();

    expect($platform->can('viewAny', Tenant::class))->toBeTrue()
        ->and($platform->can('disable', $tenant))->toBeTrue()
        ->and($tenantUser->can('viewAny', Tenant::class))->toBeFalse()
        ->and($tenantUser->can('disable', $tenant))->toBeFalse();
});
