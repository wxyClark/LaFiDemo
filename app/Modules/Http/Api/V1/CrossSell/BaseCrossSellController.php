<?php

declare(strict_types=1);

namespace App\Modules\Http\Api\V1\CrossSell;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CrossSell 基础控制器
 *
 * 提供 CrossSell 模块的通用功能：
 * - 租户 ID 获取
 * - 模型查找
 * - 验证规则
 */
abstract class BaseCrossSellController extends Controller
{
    /**
     * 获取当前用户的租户 ID
     */
    protected function getTenantId(Request $request): int
    {
        return $request->user()->tenant_id;
    }

    /**
     * 根据 ID 查找模型（不存在则返回 404）
     */
    protected function findOrFail(string $modelClass, int|string $id, string $message = '资源不存在'): Model
    {
        $model = $modelClass::find($id);

        if (! $model) {
            abort(404, $message);
        }

        return $model;
    }

    /**
     * 根据 ID 查找模型（不存在则返回错误响应）
     */
    protected function findByIdOrError(string $modelClass, int|string $id, string $message = '资源不存在'): ?Model
    {
        return $modelClass::find($id);
    }

    /**
     * 创建模型
     */
    protected function createModel(string $modelClass, array $data, string $message = '创建成功'): JsonResponse
    {
        $model = $modelClass::create($data);

        return $this->created($model->toArray(), $message);
    }

    /**
     * 更新模型
     */
    protected function updateModel(Model $model, array $data, string $message = '更新成功'): JsonResponse
    {
        $model->update($data);

        return $this->success($model->toArray(), $message);
    }

    /**
     * 删除模型
     */
    protected function deleteModel(Model $model, string $message = '删除成功'): JsonResponse
    {
        $model->delete();

        return $this->deleted($message);
    }

    /**
     * 执行状态变更操作
     */
    protected function performAction(Model $model, string $action, string $successMessage, string $errorMessage): JsonResponse
    {
        $result = $model->{$action}();

        if (! $result) {
            return $this->error($errorMessage, 422, 'INVALID_STATUS');
        }

        return $this->success($model->toArray(), $successMessage);
    }

    /**
     * 获取公共验证规则
     */
    protected function getCommonRules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort' => 'nullable|string|in:created_at,updated_at,id',
            'direction' => 'nullable|string|in:asc,desc',
        ];
    }

    /**
     * 获取分页参数
     */
    protected function getCrossSellPaginationParams(Request $request): array
    {
        return [
            'per_page' => $request->input('per_page', 15),
            'page' => $request->input('page', 1),
        ];
    }

    /**
     * 获取排序参数
     */
    protected function getCrossSellSortParams(Request $request, string $defaultColumn = 'created_at'): array
    {
        return [
            'column' => $request->input('sort', $defaultColumn),
            'direction' => $request->input('direction', 'desc'),
        ];
    }
}
