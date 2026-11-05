<?php

namespace App\Modules\Tenancy\Application;

/**
 * 可信租户上下文值对象（ADR-0001 / AGENTS.md §1；architecture.md §4）。
 *
 * 只承载"已解析、已信任"的租户 ID，不提供来源细节；所有业务用例必须从
 * TenantContext 取租户范围，不接受未经解析的客户端租户输入。
 */
final class TenantContext
{
    /**
     * @param  int  $tenantId  已解析且已信任的租户 ID（来源见 TenantContextResolver）
     */
    public function __construct(
        private readonly int $tenantId,
    ) {}

    /**
     * 显式 API 入口：队列任务 / Artisan 命令 / 测试 / 命令式入口在解析完成后构造。
     */
    public static function forTenant(int $tenantId): self
    {
        if ($tenantId <= 0) {
            throw new \InvalidArgumentException('Tenant id must be a positive integer.');
        }

        return new self($tenantId);
    }

    public function id(): int
    {
        return $this->tenantId;
    }
}
