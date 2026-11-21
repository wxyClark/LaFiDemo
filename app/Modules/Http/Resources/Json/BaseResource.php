<?php

declare(strict_types=1);

namespace App\Modules\Http\Resources\Json;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * 基础 API 资源类
 *
 * 提供统一的 API 资源格式
 *
 * @property-read int $id
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 */
abstract class BaseResource extends JsonResource
{
    /**
     * 将资源转换为数组
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            ...$this->getResourceData($request),
        ];
    }

    /**
     * 获取资源特定数据
     *
     * 子类必须实现此方法
     */
    abstract protected function getResourceData($request): array;

    /**
     * 获取格式化的状态
     */
    protected function getStatusLabel(): string
    {
        return match ($this->attributes['status'] ?? null) {
            'active', 'enabled' => '启用',
            'inactive', 'disabled' => '停用',
            'pending' => '待处理',
            'approved' => '已通过',
            'rejected' => '已拒绝',
            'completed' => '已完成',
            'cancelled' => '已取消',
            default => $this->attributes['status'] ?? '未知',
        };
    }

    /**
     * 获取格式化的日期
     */
    protected function formatDate($date): ?string
    {
        return $date?->format('Y-m-d H:i:s');
    }

    /**
     * 获取格式化的金额
     */
    protected function formatAmount($amount, string $currency = 'CNY'): string
    {
        return number_format((float) $amount, 2, '.', '').' '.$currency;
    }

    /**
     * 在请求中添加额外字段
     *
     * @param  array<string, mixed>  $fields
     */
    protected function withFields(array $fields): static
    {
        $this->resource->additional($fields);

        return $this;
    }
}
