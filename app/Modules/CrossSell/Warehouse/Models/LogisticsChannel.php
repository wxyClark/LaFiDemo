<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Warehouse\Models;

use App\Modules\CrossSell\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 物流渠道模型
 */
class LogisticsChannel extends Model
{
    use HasFactory;

    protected $table = 'cross_sell_logistics_channels';

    protected $fillable = [
        'tenant_id',
        'name',
        'carrier',
        'code',
        'supported_countries',
        'supported_weight',
        'price_formula',
        'base_price',
        'price_per_kg',
        'estimated_days_min',
        'estimated_days_max',
        'rating',
        'is_active',
    ];

    protected $casts = [
        'supported_countries' => 'array',
        'supported_weight' => 'array',
        'base_price' => 'decimal:2',
        'price_per_kg' => 'decimal:2',
        'estimated_days_min' => 'integer',
        'estimated_days_max' => 'integer',
        'rating' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * 关联租户（NULL表示系统默认渠道）
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * 计算运费
     */
    public function calculatePrice(float $weight): float
    {
        $basePrice = (float) $this->base_price;
        $pricePerKg = (float) $this->price_per_kg;

        return $basePrice + ($weight * $pricePerKg);
    }

    /**
     * 检查是否支持指定国家
     */
    public function supportsCountry(string $country): bool
    {
        $supported = $this->supported_countries ?? [];

        return in_array($country, $supported);
    }
}
