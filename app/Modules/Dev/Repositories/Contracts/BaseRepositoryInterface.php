<?php

namespace App\Modules\Dev\Repositories\Contracts;

interface BaseRepositoryInterface
{
    // 获取所有记录（支持排序和分页）
    public function all(array $params, array $columns = ['id'], array $orderBy = ['id' => 'desc']);

    // 获取单记录
    public function one(array $params, array $columns = ['*']);

    // 按条件查询（支持链式条件）
    public function total(array $params);

    // 创建记录（支持事务）
    public function create(array $data);

    // 更新记录
    public function update(array $conditions, array $data);

    // 删除记录
    public function delete(int $id);

    // 批量删除
    public function batchDelete(array $ids);
}
