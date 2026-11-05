<?php

use App\Models\User;
use App\Modules\Tenancy\Application\TenantContext;
use App\Modules\Tenancy\Application\TenantContextResolver;
use App\Modules\Tenancy\Application\TenantContextUnresolvedException;
use App\Modules\Tenancy\Domain\Tenant;
use App\Modules\Tenancy\Domain\TenantStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

uses(RefreshDatabase::class);

/** 可信解析（矩阵 3 / AGENTS.md §1）：租户 ID 只来自显式 API 与应用配置，
 *  不读取请求体、路由参数或客户端头（成员关系解析留给后续认证模块）。 */
test('the explicit api resolves a tenant context without request input', function (): void {
    $context = (new TenantContextResolver)->forTenant(42);

    expect($context)->toBeInstanceOf(TenantContext::class)
        ->and($context->id())->toBe(42);
});

test('tenant context rejects non-positive tenant ids', function (): void {
    expect(fn () => TenantContext::forTenant(0))->toThrow(InvalidArgumentException::class)
        ->and(fn () => TenantContext::forTenant(-1))->toThrow(InvalidArgumentException::class);
});

test('config resolves a deployment-level tenant context when configured', function (): void {
    config(['tenancy.current_tenant_id' => 7]);

    expect((new TenantContextResolver)->fromConfig()?->id())->toBe(7);
});

test('config returns null when no deployment tenant is configured (default deny)', function (): void {
    config(['tenancy.current_tenant_id' => null]);
    $resolver = new TenantContextResolver;

    expect($resolver->fromConfig())->toBeNull();

    expect(fn () => $resolver->requireFromConfig())->toThrow(TenantContextUnresolvedException::class);
});

/** Tenant Policy 基线默认全拒绝（testing-security.md §3 最小权限；
 *  Filament 隐藏按钮/页面不构成授权，跨租户管理需显式平台权限落地后放宽）。 */
test('tenant policy denies all baseline actions by default', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    expect($user->cannot('viewAny', Tenant::class))->toBeTrue()
        ->and($user->cannot('view', $tenant))->toBeTrue()
        ->and($user->cannot('update', $tenant))->toBeTrue()
        ->and($user->cannot('disable', $tenant))->toBeTrue()
        ->and($tenant->status)->toBe(TenantStatus::Active, 'default deny must not mutate tenant facts');
});

/** 租户状态受控：Tenant 模型经枚举 cast，disabled 租户可识别（后续
 *  membership 活性检查的前置基线）。 */
test('tenant status is a controlled enum value', function (): void {
    $active = Tenant::factory()->create();
    $disabled = Tenant::factory()->disabled()->create();

    expect($active->status)->toBe(TenantStatus::Active)
        ->and($active->isEnabled())->toBeTrue()
        ->and($disabled->status)->toBe(TenantStatus::Disabled)
        ->and($disabled->isEnabled())->toBeFalse();
});
