<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Warehouse\Models;

use App\Modules\CrossSell\Tenant\Models\Tenant;
use App\Modules\CrossSell\Warehouse\Enums\PlatformType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 平台配置模型
 */
class PlatformConfig extends Model
{
    use HasFactory;

    protected $table = 'cross_sell_platform_configs';

    protected $fillable = [
        'tenant_id',
        'platform',
        'shop_name',
        'api_key',
        'api_secret',
        'access_token',
        'sync_enabled',
        'sync_interval',
        'last_sync_at',
        'status',
        'error_message',
    ];

    protected $hidden = [
        'api_secret',
        'access_token',
    ];

    protected $casts = [
        'platform' => PlatformType::class,
        'sync_enabled' => 'boolean',
        'sync_interval' => 'integer',
        'last_sync_at' => 'datetime',
    ];

    /**
     * 关联租户
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * 检查是否可以同步更多平台
     */
    public function canSync(): bool
    {
        return $this->sync_enabled && $this->status === 'active';
    }
}
