<?php

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Infrastructure\ProductVariant;
use Illuminate\Database\Eloquent\Collection;

/**
 * Catalog 模块对外的只读查询接口（ADR-0001 e 条读方向：跨模块读
 * 必须经对方 Application 公开的只读投影，不得直接依赖对方
 * Infrastructure 模型；Tenant 范围由方法参数显式传入并强制限定，
 * 不接受全局租户状态）。
 *
 * 用途：库存调整等跨模块用例校验 "variant 归属租户"、列表/导出
 * 数据源。写操作一律经 Catalog 公开写用例（本基线未建）。
 */
final class ProductVariantQueries
{
    /**
     * 该 variant 是否归属于指定租户（跨租户写拒绝检查的依据）。
     */
    public function variantBelongsTo(int $tenantId, int $variantId): bool
    {
        return ProductVariant::query()
            ->whereKey($variantId)
            ->where('tenant_id', $tenantId)
            ->exists();
    }

    /**
     * 租户的 variant 只读投影（列表/导出维度）。
     *
     * @return Collection<int, ProductVariant>
     */
    public function variantsOf(int $tenantId): Collection
    {
        return ProductVariant::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->get();
    }
}
