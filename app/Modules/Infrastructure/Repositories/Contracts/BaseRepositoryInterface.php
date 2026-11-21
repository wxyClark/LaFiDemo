<?php

declare(strict_types=1);

namespace App\Modules\Infrastructure\Repositories\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 基础仓储接口
 *
 * 定义所有仓储必须实现的方法
 */
interface BaseRepositoryInterface
{
    /**
     * 获取所有记录
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * 获取分页数据
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * 根据 ID 查找
     */
    public function find(int $id, array $columns = ['*']): ?Model;

    /**
     * 根据 ID 查找（不存在则抛异常）
     */
    public function findOrFail(int $id, array $columns = ['*']): Model;

    /**
     * 根据条件查找第一条
     */
    public function findBy(string $column, mixed $value, array $columns = ['*']): ?Model;

    /**
     * 根据条件查找（不存在则抛异常）
     */
    public function findByOrFail(string $column, mixed $value, array $columns = ['*']): Model;

    /**
     * 根据条件查找多条
     */
    public function findByMany(string $column, array $values, array $columns = ['*']): Collection;

    /**
     * 创建记录
     */
    public function create(array $data): Model;

    /**
     * 批量创建
     */
    public function createMany(array $dataList): Collection;

    /**
     * 更新记录
     */
    public function update(int $id, array $data): bool;

    /**
     * 删除记录
     */
    public function delete(int $id): bool;

    /**
     * 批量删除
     */
    public function deleteMany(array $ids): int;

    /**
     * 检查记录是否存在
     */
    public function exists(array $conditions): bool;

    /**
     * 统计数量
     */
    public function count(array $conditions = []): int;

    /**
     * 获取汇总
     */
    public function sum(string $column, array $conditions = []): float;

    /**
     * 获取平均值
     */
    public function avg(string $column, array $conditions = []): float;

    /**
     * 获取最大值
     */
    public function max(string $column, array $conditions = []): mixed;

    /**
     * 获取最小值
     */
    public function min(string $column, array $conditions = []): mixed;

    /**
     * 软删除恢复
     */
    public function restore(int $id): bool;

    /**
     * 获取查询构建器
     */
    public function query(): Builder;
}
