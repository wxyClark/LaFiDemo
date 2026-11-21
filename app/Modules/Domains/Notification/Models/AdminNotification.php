<?php

declare(strict_types=1);

namespace App\Modules\Domains\Notification\Models;

use App\Modules\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminNotification extends Model
{
    protected $table = 'admin_notifications';

    protected $fillable = [
        'admin_id',
        'type',
        'title',
        'message',
        'subject_type',
        'subject_id',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'order_status_changed' => '订单状态变更',
            'low_stock' => '库存预警',
            'system' => '系统通知',
            default => $this->type,
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'order_status_changed' => 'info',
            'low_stock' => 'warning',
            'system' => 'gray',
            default => 'gray',
        };
    }

    public static function notifyOrderStatusChanged(
        int $adminId,
        string $orderNo,
        string $oldStatus,
        string $newStatus,
    ): self {
        return static::create([
            'admin_id' => $adminId,
            'type' => 'order_status_changed',
            'title' => "订单 {$orderNo} 状态变更",
            'message' => "订单 {$orderNo} 状态从 {$oldStatus} 变更为 {$newStatus}",
            'data' => [
                'order_no' => $orderNo,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
        ]);
    }

    public static function notifyLowStock(
        int $adminId,
        string $productName,
        string $skuCode,
        int $currentStock,
        int $threshold = 10,
    ): self {
        return static::create([
            'admin_id' => $adminId,
            'type' => 'low_stock',
            'title' => "库存预警: {$productName}",
            'message' => "商品 {$productName} (SKU: {$skuCode}) 库存不足，当前库存: {$currentStock}，预警阈值: {$threshold}",
            'data' => [
                'product_name' => $productName,
                'sku_code' => $skuCode,
                'current_stock' => $currentStock,
                'threshold' => $threshold,
            ],
        ]);
    }

    public static function notifySystem(
        int $adminId,
        string $title,
        string $message,
        ?array $data = null,
    ): self {
        return static::create([
            'admin_id' => $adminId,
            'type' => 'system',
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
