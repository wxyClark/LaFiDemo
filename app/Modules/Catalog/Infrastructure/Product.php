<?php

namespace App\Modules\Catalog\Infrastructure;

use App\Modules\Catalog\Domain\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 商品 SPU（Catalog 模块；blueprint §5.2）。承载展示信息，租户维度唯一 slug。
 *
 * 模块归属：Catalog（ADR-0001 a 条）；跨模块写入只能经 Catalog Application
 * 公开接口，其他模块禁止直接操作本模型（ADR-0001 e 条）。
 * 本基线不定义跨模块 Eloquent 关系（避免跨模块依赖对方内部模型）。
 */
#[Fillable(['tenant_id', 'slug', 'title', 'description', 'status'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
        ];
    }
}
