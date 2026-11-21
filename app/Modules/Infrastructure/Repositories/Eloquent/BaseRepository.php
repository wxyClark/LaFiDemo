<?php

declare(strict_types=1);

namespace App\Modules\Infrastructure\Repositories\Eloquent;

use App\Modules\Infrastructure\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 基础仓储实现
 *
 * 提供 Eloquent 模型的标准 CRUD 操作
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * 模型实例
     */
    protected Model $model;

    /**
     * 构造函数
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * 获取模型实例
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * 获取所有记录
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->query()->get($columns);
    }

    /**
     * 获取分页数据
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->query()->paginate($perPage, $columns);
    }

    /**
     * 根据 ID 查找
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->model->query()->find($id, $columns);
    }

    /**
     * 根据 ID 查找（不存在则抛异常）
     */
    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->model->query()->findOrFail($id, $columns);
    }

    /**
     * 根据条件查找第一条
     */
    public function findBy(string $column, mixed $value, array $columns = ['*']): ?Model
    {
        return $this->model->query()->where($column, $value)->first($columns);
    }

    /**
     * 根据条件查找（不存在则抛异常）
     */
    public function findByOrFail(string $column, mixed $value, array $columns = ['*']): Model
    {
        return $this->model->query()->where($column, $value)->firstOrFail($columns);
    }

    /**
     * 根据条件查找多条
     */
    public function findByMany(string $column, array $values, array $columns = ['*']): Collection
    {
        return $this->model->query()->whereIn($column, $values)->get($columns);
    }

    /**
     * 创建记录
     */
    public function create(array $data): Model
    {
        return $this->model->query()->create($data);
    }

    /**
     * 批量创建
     */
    public function createMany(array $dataList): Collection
    {
        $models = new Collection;

        foreach ($dataList as $data) {
            $models->push($this->create($data));
        }

        return $models;
    }

    /**
     * 更新记录
     */
    public function update(int $id, array $data): bool
    {
        $model = $this->find($id);

        if (! $model) {
            return false;
        }

        return $model->update($data);
    }

    /**
     * 删除记录
     */
    public function delete(int $id): bool
    {
        $model = $this->find($id);

        if (! $model) {
            return false;
        }

        return $model->delete();
    }

    /**
     * 批量删除
     */
    public function deleteMany(array $ids): int
    {
        return $this->model->query()->whereIn('id', $ids)->delete();
    }

    /**
     * 检查记录是否存在
     */
    public function exists(array $conditions): bool
    {
        return $this->model->query()->where($conditions)->exists();
    }

    /**
     * 统计数量
     */
    public function count(array $conditions = []): int
    {
        $query = $this->model->query();

        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        return $query->count();
    }

    /**
     * 获取汇总
     */
    public function sum(string $column, array $conditions = []): float
    {
        $query = $this->model->query();

        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        return (float) $query->sum($column);
    }

    /**
     * 获取平均值
     */
    public function avg(string $column, array $conditions = []): float
    {
        $query = $this->model->query();

        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        return (float) $query->avg($column);
    }

    /**
     * 获取最大值
     */
    public function max(string $column, array $conditions = []): mixed
    {
        $query = $this->model->query();

        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        return $query->max($column);
    }

    /**
     * 获取最小值
     */
    public function min(string $column, array $conditions = []): mixed
    {
        $query = $this->model->query();

        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        return $query->min($column);
    }

    /**
     * 软删除恢复
     */
    public function restore(int $id): bool
    {
        $model = $this->model->withTrashed()->find($id);

        if (! $model) {
            return false;
        }

        return $model->restore();
    }

    /**
     * 获取查询构建器
     */
    public function query(): Builder
    {
        return $this->model->query();
    }

    /**
     * 根据条件获取查询构建器
     */
    protected function where(array $conditions): Builder
    {
        return $this->model->query()->where($conditions);
    }

    /**
     * 根据条件获取查询构建器（带 OR）
     */
    protected function whereOr(array $conditions): Builder
    {
        $query = $this->model->query();

        foreach ($conditions as $column => $value) {
            $query->orWhere($column, $value);
        }

        return $query;
    }

    /**
     * 搜索查询
     */
    protected function search(string $column, string $keyword): Builder
    {
        return $this->model->query()->where($column, 'like', "%{$keyword}%");
    }

    /**
     * 批量搜索查询
     */
    protected function searchMultiple(array $columns, string $keyword): Builder
    {
        $query = $this->model->query();

        $query->where(function (Builder $q) use ($columns, $keyword) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', "%{$keyword}%");
            }
        });

        return $query;
    }
}
