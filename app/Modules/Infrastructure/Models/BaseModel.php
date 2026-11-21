<?php

declare(strict_types=1);

namespace App\Modules\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * 基础模型类
 *
 * 提供统一的模型功能：
 * - 自动时间戳
 * - 软删除
 * - 自动填充
 * - 访问器
 * - 工厂支持
 */
abstract class BaseModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * 自动时间戳
     */
    public $timestamps = true;

    /**
     * 获取模型的表名
     *
     * 自动将 CamelCase 转换为 snake_case 复数形式
     */
    public function getTable(): string
    {
        if (isset($this->table)) {
            return $this->table;
        }

        return Str::snake(Str::plural(class_basename($this)));
    }

    /**
     * 获取路由键名
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * 获取模型的可搜索字段
     *
     * 子类可以覆盖此方法以定义可搜索字段
     */
    public function getSearchableFields(): array
    {
        return $this->fillable ?? [];
    }

    /**
     * 获取模型的可排序字段
     *
     * 子类可以覆盖此方法以定义可排序字段
     */
    public function getSortableFields(): array
    {
        return ['id', 'created_at', 'updated_at'];
    }

    /**
     * 获取模型的状态标签
     *
     * 子类可以覆盖此方法以定义状态标签
     */
    public function getStatusLabelAttribute(): string
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
     * 获取模型的状态颜色
     *
     * 子类可以覆盖此方法以定义状态颜色
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->attributes['status'] ?? null) {
            'active', 'enabled', 'approved', 'completed', 'success' => 'success',
            'inactive', 'disabled', 'cancelled', 'failed' => 'danger',
            'pending', 'processing', 'warning' => 'warning',
            default => 'gray',
        };
    }

    /**
     * 格式化时间字段
     */
    public function getFormattedCreatedAtAttribute(): ?string
    {
        return $this->created_at?->format('Y-m-d H:i:s');
    }

    /**
     * 格式化更新时间字段
     */
    public function getFormattedUpdatedAtAttribute(): ?string
    {
        return $this->updated_at?->format('Y-m-d H:i:s');
    }

    /**
     * 检查记录是否为今天创建
     */
    public function isCreatedToday(): bool
    {
        return $this->created_at?->isToday() ?? false;
    }

    /**
     * 检查记录是否为今天更新
     */
    public function isUpdatedToday(): bool
    {
        return $this->updated_at?->isToday() ?? false;
    }

    /**
     * 获取记录年龄（天数）
     */
    public function getAgeInDays(): int
    {
        return $this->created_at?->diffInDays(now()) ?? 0;
    }

    /**
     * 转换为 API 响应数组
     *
     * 子类可以覆盖此方法以自定义 API 响应格式
     */
    public function toApiResponse(): array
    {
        $array = $this->toArray();

        // 添加格式化的时间字段
        $array['created_at_formatted'] = $this->formatted_created_at;
        $array['updated_at_formatted'] = $this->formatted_updated_at;

        // 添加状态标签和颜色
        if (isset($this->attributes['status'])) {
            $array['status_label'] = $this->status_label;
            $array['status_color'] = $this->status_color;
        }

        return $array;
    }

    /**
     * 批量更新
     */
    public static function bulkUpdate(array $conditions, array $data): int
    {
        return static::query()->where($conditions)->update($data);
    }

    /**
     * 批量删除
     */
    public static function bulkDelete(array $ids): int
    {
        return static::query()->whereIn('id', $ids)->delete();
    }

    /**
     * 获取统计信息
     */
    public static function getStatistics(): array
    {
        $model = new static;

        return [
            'total' => $model->count(),
            'today' => $model->whereDate('created_at', today())->count(),
            'this_week' => $model->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => $model->whereMonth('created_at', now()->month)->count(),
        ];
    }
}
