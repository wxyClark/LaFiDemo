<?php

namespace App\Modules\Tenancy\Application;

use Illuminate\Support\Facades\Config;

/**
 * 可信租户解析基线（AGENTS.md §1：租户 ID 必须来自可信来源，不信任请求体、
 * 路由参数或客户端头）。
 *
 * 当前阶段只有两条可信入口：
 *   1. fromConfig() —— 应用配置 TENANCY_CURRENT_TENANT_ID（部署级上下文，
 *      适用于按租户部署的任务 worker / CLI）；
 *   2. forTenant() —— 显式 API，由已完成租户解析的入口（命令、队列消费者、
 *      测试）在调用业务用例前构造。
 *
 * 后续认证模块落地后（遗留事项）：租户解析改为从认证会话中已验证的
 * tenant_memberships 有效成员关系得出，并检查 membership 活性
 * （docs/rules/testing-security.md "身份、授权与多租户"）；届时本类删除
 * 配置/显式入口中不安全的使用方式，成员关系解析实现另在此模块补齐。
 */
final class TenantContextResolver
{
    public function forTenant(int $tenantId): TenantContext
    {
        return TenantContext::forTenant($tenantId);
    }

    /**
     * 从应用配置解析部署级租户上下文；未配置时返回 null（由调用方决定拒绝策略）。
     */
    public function fromConfig(): ?TenantContext
    {
        $configured = Config::get('tenancy.current_tenant_id');

        if ($configured === null) {
            return null;
        }

        if (! is_int($configured) || $configured <= 0) {
            throw new TenantContextUnresolvedException('tenancy.current_tenant_id must be a positive integer.');
        }

        return new TenantContext($configured);
    }

    /**
     * 要求配置上下文已解析，否则抛异常（命令/任务入口使用，默认拒绝）。
     */
    public function requireFromConfig(): TenantContext
    {
        $context = $this->fromConfig();

        if ($context === null) {
            throw new TenantContextUnresolvedException(
                'No trusted tenant context: set TENANCY_CURRENT_TENANT_ID or resolve membership via the auth module.'
            );
        }

        return $context;
    }
}
