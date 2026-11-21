<?php

declare(strict_types=1);

namespace App\Modules\Infrastructure\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * 基础服务类
 *
 * 提供统一的服务层功能：
 * - 事务管理
 * - 数据验证
 * - 日志记录
 * - 错误处理
 */
abstract class BaseService
{
    /**
     * 在事务中执行操作
     */
    protected function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    /**
     * 创建记录
     */
    protected function create(Model $model, array $data): Model
    {
        return $model->create($data);
    }

    /**
     * 批量创建记录
     */
    protected function createMany(Model $model, array $dataList): int
    {
        $count = 0;

        foreach ($dataList as $data) {
            $model->create($data);
            $count++;
        }

        return $count;
    }

    /**
     * 更新记录
     */
    protected function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    /**
     * 删除记录
     */
    protected function delete(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * 批量删除记录
     */
    protected function deleteMany($query, array $ids): int
    {
        return $query->whereIn('id', $ids)->delete();
    }

    /**
     * 根据 ID 查找记录
     */
    protected function findById(string $modelClass, int $id): ?Model
    {
        return $modelClass::find($id);
    }

    /**
     * 根据 ID 查找记录（不存在则抛异常）
     */
    protected function findByIdOrFail(string $modelClass, int $id): Model
    {
        return $modelClass::findOrFail($id);
    }

    /**
     * 检查记录是否存在
     */
    protected function exists(string $modelClass, array $conditions): bool
    {
        return $modelClass::where($conditions)->exists();
    }

    /**
     * 获取分页数据
     */
    protected function paginate($query, int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $query->paginate($perPage, $columns);
    }

    /**
     * 批量更新
     */
    protected function bulkUpdate($query, array $data): int
    {
        return $query->update($data);
    }

    /**
     * 软删除恢复
     */
    protected function restore(string $modelClass, int $id): bool
    {
        $model = $modelClass::withTrashed()->find($id);

        if (! $model) {
            return false;
        }

        return $model->restore();
    }

    /**
     * 获取统计数量
     */
    protected function count($query): int
    {
        return $query->count();
    }

    /**
     * 获取汇总数据
     */
    protected function sum($query, string $column): float
    {
        return (float) $query->sum($column);
    }

    /**
     * 获取平均值
     */
    protected function avg($query, string $column): float
    {
        return (float) $query->avg($column);
    }

    /**
     * 获取最大值
     */
    protected function max($query, string $column): mixed
    {
        return $query->max($column);
    }

    /**
     * 获取最小值
     */
    protected function min($query, string $column): mixed
    {
        return $query->min($column);
    }
}
