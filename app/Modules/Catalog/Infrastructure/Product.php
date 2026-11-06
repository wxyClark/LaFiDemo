<?php

namespace App\Modules\Catalog\Infrastructure;

use App\Modules\Catalog\Domain\ProductStatus;
use App\Modules\Tenancy\Domain\Tenant;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 商品 SPU（Catalog 模块；blueprint §5.2）。承载展示信息，租户维度唯一 slug。
 *
 * 模块归属：Catalog（ADR-0001 a 条）；跨模块写入只能经 Catalog Application
 * 公开接口，其他模块禁止直接操作本模型（ADR-0001 e 条）。
 * 本基线不定义跨模块 Eloquent 关系（避免跨模块依赖对方内部模型）；
 * 唯一的例外是 tenant() 只读归属投影（工厂绑定与归属校验用途，只读）。
 */
/**
 * @property int $id
 * @property int $tenant_id
 * @property string $slug
 * @property string $title
 * @property string|null $description
 * @property ProductStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'slug', 'title', 'description', 'status'])]
#[UseFactory(ProductFactory::class)]
class Product extends Model
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
            'status' => ProductStatus::class,
        ];
    }

    /**
     * 所属租户（只读归属投影：工厂 for($tenant) 绑定与归属校验用途）。
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
