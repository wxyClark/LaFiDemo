<?php

namespace App\Modules\Tenancy\Application;

/**
 * 当前请求的可信租户上下文持有器（Tenant 面板请求作用域）。
 *
 * 职责：在可信解析（认证用户归属）完成后，把 TenantContext 供本请求内的
 * 资源/用例统一取用；未经解析的请求必须拒绝（AGENTS.md §1：租户 ID 只来自
 * 可信来源，不信任请求体/路由参数/客户端头）。
 *
 * 解析来源：TenantPanel 的 ResolveTenantContext 中间件（认证用户 tenant_id）——
 * 非认证请求、平台角色、无 tenant_id 均不会进入此持有器。
 */
final class CurrentTenant
{
    private ?TenantContext $context = null;

    /**
     * 由可信解析入口设置当前请求租户上下文。
     */
    public function set(TenantContext $context): void
    {
        $this->context = $context;
    }

    /**
     * 要求租户上下文已解析，否则抛未解析异常（默认拒绝）。
     */
    public function required(): TenantContext
    {
        if ($this->context === null) {
            throw new TenantContextUnresolvedException(
                'No tenant context resolved for the current request.'
            );
        }

        return $this->context;
    }

    /**
     * 当前租户 ID（业务范围限定的统一入口）。
     */
    public function id(): int
    {
        return $this->required()->id();
    }

    /**
     * 请求结束后清空（防止长驻 worker/测试间泄漏）。
     */
    public function clear(): void
    {
        $this->context = null;
    }
}
