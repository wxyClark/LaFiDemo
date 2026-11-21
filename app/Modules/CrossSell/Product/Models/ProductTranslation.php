<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 商品翻译模型
 */
class ProductTranslation extends Model
{
    use HasFactory;

    protected $table = 'cross_sell_product_translations';

    protected $fillable = [
        'product_id',
        'language',
        'title',
        'description',
    ];

    /**
     * 关联商品
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
