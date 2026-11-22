<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Product\Models;

use App\Modules\CrossSell\Product\Enums\ProductStatus;
use App\Modules\CrossSell\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 商品模型
 */
class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'cross_sell_products';

    protected $fillable = [
        'tenant_id',
        'category_id',
        'title',
        'description',
        'brand',
        'status',
        'main_image',
        'images',
        'source_url',
        'source_id',
        'tags',
    ];

    protected $casts = [
        'status' => ProductStatus::class,
        'images' => 'array',
        'tags' => 'array',
    ];

    /**
     * 关联租户
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * 关联分类
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * 关联SKU
     */
    public function skus(): HasMany
    {
        return $this->hasMany(SKU::class);
    }

    /**
     * 关联翻译
     */
    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    /**
     * 获取指定语言的标题
     */
    public function getTranslatedTitle(string $language = 'en'): string
    {
        $translation = $this->translations->firstWhere('language', $language);

        return $translation->title ?? $this->title;
    }

    /**
     * 获取指定语言的描述
     */
    public function getTranslatedDescription(string $language = 'en'): ?string
    {
        $translation = $this->translations->firstWhere('language', $language);

        return $translation->description ?? $this->description;
    }

    /**
     * 同步指定语言的翻译（供 Filament 表单 title_en / description_en 落库）
     */
    public function syncTranslation(string $language, ?string $title, ?string $description): void
    {
        $title = $title === '' ? null : $title;
        $description = $description === '' ? null : $description;

        if ($title === null && $description === null) {
            $this->translations()->where('language', $language)->delete();

            return;
        }

        $this->translations()->updateOrCreate(
            ['language' => $language],
            ['title' => $title, 'description' => $description],
        );
    }
}
