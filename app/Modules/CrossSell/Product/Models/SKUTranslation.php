<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SKU翻译模型
 */
class SKUTranslation extends Model
{
    use HasFactory;

    protected $table = 'cross_sell_sku_translations';

    protected $fillable = [
        'sku_id',
        'language',
        'name',
    ];

    /**
     * 关联SKU
     */
    public function sku(): BelongsTo
    {
        return $this->belongsTo(SKU::class);
    }
}
