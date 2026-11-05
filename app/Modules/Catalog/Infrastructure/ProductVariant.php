<?php

namespace App\Modules\Catalog\Infrastructure;

use App\Modules\Catalog\Domain\VariantStatus;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * SKU 变体（Catalog 模块；blueprint §5.2）。SKU 是库存单位。
 *
 * 模块归属：Catalog（ADR-0001 a 条）；跨模块写入只能经 Catalog Application
 * 公开接口，其他模块禁止直接操作本模型（ADR-0001 e 条）。
 */
#[Fillable(['tenant_id', 'product_id', 'sku', 'status', 'attributes_json', 'barcode'])]
#[UseFactory(ProductVariantFactory::class)]
class ProductVariant extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attributes_json' => 'array',
            'status' => VariantStatus::class,
        ];
    }
}
