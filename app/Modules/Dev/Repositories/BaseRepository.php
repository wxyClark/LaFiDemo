<?php

namespace App\Modules\Dev\Repositories;

use App\Modules\Dev\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Created by PhpStorm
 * Author: wxyClark
 * Date: 2025/11/29
 * Time: 14:38
 * Email: C18666211369@outlook.com
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    /** @var Model 主表 */
    protected Model $model;

    //  精准匹配 where
    protected $inTermFields = [];

    //  支持时间范围查询的字段
    protected $timeRangeFields = [];

    //  支持时间范围查询的字段
    protected $numberRangeFields = [];

    public function __construct()
    {
        $this->makeModel();
    }

    protected function makeModel(): void
    {
        $model = app($this->model());

        if (! $model instanceof Model) {
            throw new \RuntimeException("Class {$this->model()} 必须基础自 Model 基类");
        }

        $this->model = $model;
    }

    abstract protected function model(): string;

    /**
     * 获取多条记录
     *
     * @return array
     *
     * @author wxyClark
     *
     * @create 2025/11/29 14:44
     *
     * @version 1.0
     */
    public function all($params, $columns = ['id'], $orderBy = ['id' => 'desc'])
    {
        $query = $this->conditions($params);

        if (! empty($orderBy)) {
            foreach ($orderBy as $column => $type) {
                $query->orderBy($column, $type);
            }
        }

        if (! empty($params['page']) && ! empty($params['page_size'])) {
            $offset = ($params['page'] - 1) * $params['page_size'];

            return $query->offset($offset, $params['page']);
        }
        $list = $this->conditions($params)->select($columns)->get();

        return empty($list) ? [] : $list->toArray();
    }

    /**
     * 获取单条记录
     *
     * @return array
     *
     * @author wxyClark
     *
     * @create 2025/11/29 15:04
     *
     * @version 1.0
     */
    public function one($params, $columns = ['id'])
    {
        $record = $this->conditions($params)->select($columns)->first();

        return empty($record) ? [] : $record->toArray();
    }

    /**
     * 统计总数
     *
     * @return mixed
     *
     * @author wxyClark
     *
     * @create 2025/11/29 15:02
     *
     * @version 1.0
     */
    public function total($params)
    {
        return $this->conditions($params)->count();
    }

    /**
     * 创建
     *
     * @return mixed
     *
     * @author wxyClark
     *
     * @create 2025/11/29 15:02
     *
     * @version 1.0
     */
    public function create(array $data)
    {
        return $this->model->insert($data);
    }

    //  如需批量更新，可引入 mavinoo/laravel-batch 组件

    /**
     * 更新
     *
     * @return mixed
     *
     * @author wxyClark
     *
     * @create 2025/11/29 15:12
     *
     * @version 1.0
     */
    public function update(array $conditions, array $data)
    {
        return $this->conditions($conditions)->update($data);
    }

    /**
     * 批量
     *
     * @return false
     *
     * @author wxyClark
     *
     * @create 2025/11/29 15:10
     *
     * @version 1.0
     */
    public function batchDelete(array $ids)
    {
        if (empty($ids)) {
            return false;
        }

        return $this->model->whereIn('id', $ids)->delete();
    }

    /**
     * 删除单条记录
     *
     * @return false
     *
     * @author wxyClark
     *
     * @create 2025/11/29 15:11
     *
     * @version 1.0
     */
    public function delete(int $id)
    {
        if (empty($id)) {
            return false;
        }

        return $this->model->where('id', $id)->delete();
    }

    /**
     * 通用查询
     *
     * @return mixed
     *
     * @author wxyClark
     *
     * @create 2025/11/29 11:44
     *
     * @version 1.0
     */
    protected function conditions($params)
    {
        $query = $this->model->newQuery();

        //  精准匹配，兼容 单值、数组；
        foreach ($this->inTermFields as $field) {
            if (! empty($params[$field])) {
                $query->whereIn($field, (array) $params[$field]);
            }
        }

        //  数值范围查询
        if (! empty($params['number_type']) && in_array($params['number_type'], $this->numberRangeFields)) {
            if (isset($params['number_min'])) {
                $query->where($params['number_type'], '>=', $params['number_min']);
            }
            if (! empty($params['number_max'])) {
                $query->where($params['number_type'], '<', $params['number_max']);
            }
        }

        //  时间范围查询
        if (! empty($params['time_type']) && in_array($params['time_type'], $this->timeRangeFields)) {
            if (! empty($params['time_start'])) {
                $query->where($params['time_type'], '>=', $params['time_start']);
            }
            if (! empty($params['time_end'])) {
                $query->where($params['time_type'], '<=', $params['time_end']);
            }
        }

        return $query;
    }
}
