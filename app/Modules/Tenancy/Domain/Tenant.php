<?php

namespace App\Modules\Tenancy\Domain;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * SaaS 商户/租户（Tenancy 模块自有实体；blueprint §5.1）。
 *
 * 基线最小字段：name / status / 时间戳（UTC）。slug、timezone、default_currency、
 * settings_json 等字段随产品需求与后续 expand 迁移补充；密钥类配置一律走 Secret 管理，
 * 不落入本表（blueprint §5.1 说明列）。
 *
 * 模块归属：Tenancy（ADR-0001 a 条）。Eloquent 持久化实体放在模块 Domain 层
 * （本模块无独立 Infrastructure 持久化边界，按 ADR-0001 b 条"简单模块可省略"）。
 */
#[Fillable(['name', 'status'])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
        ];
    }

    public function isEnabled(): bool
    {
        return $this->status === TenantStatus::Active->value;
    }
}
