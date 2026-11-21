<?php

declare(strict_types=1);

namespace App\Modules\Domains\Catalog\Models;

use App\Modules\Domains\Catalog\Database\Factories\ProductFactory;
use App\Modules\Domains\Catalog\Enums\ProductStatus;
use App\Modules\Domains\Catalog\Enums\ProductType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read int $id
 * @property string $name
 * @property string $type
 * @property string $status
 * @property int|null $tenant_id
 * @property int|null $category_id
 * @property string|null $description
 */
class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    protected $table = 'products';

    protected $fillable = [
        'name',
        'tenant_id',
        'category_id',
        'type',
        'status',
        'description',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'category_id' => 'integer',
    ];

    public function skus(): HasMany
    {
        return $this->hasMany(Sku::class, 'product_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::ACTIVE->value);
    }

    public function isVirtual(): bool
    {
        return $this->type === ProductType::VIRTUAL->value;
    }

    public function typeEnum(): ProductType
    {
        return ProductType::from($this->type);
    }
}
