<?php

declare(strict_types=1);

namespace App\Modules\Infrastructure\Support\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * API 响应标准化 Trait
 *
 * 提供统一的 API 响应格式，包括：
 * - 成功响应
 * - 错误响应
 * - 分页响应
 * - 资源响应
 * - 列表响应
 */
trait ApiResponse
{
    /**
     * 成功响应
     */
    protected function success(mixed $data = null, string $message = 'success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * 创建成功响应（201）
     */
    protected function created(mixed $data = null, string $message = '创建成功'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * 更新成功响应
     */
    protected function updated(mixed $data = null, string $message = '更新成功'): JsonResponse
    {
        return $this->success($data, $message);
    }

    /**
     * 删除成功响应
     */
    protected function deleted(string $message = '删除成功'): JsonResponse
    {
        return $this->success(null, $message);
    }

    /**
     * 无内容响应（204）
     */
    protected function noContent(string $message = '操作成功'): JsonResponse
    {
        return $this->success(null, $message, 204);
    }

    /**
     * 错误响应
     */
    protected function error(string $message = '请求失败', int $code = 400, ?string $errorCode = null, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errorCode !== null) {
            $response['error_code'] = $errorCode;
        }

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * 未找到响应（404）
     */
    protected function notFound(string $message = '资源不存在', ?string $errorCode = 'NOT_FOUND'): JsonResponse
    {
        return $this->error($message, 404, $errorCode);
    }

    /**
     * 未授权响应（401）
     */
    protected function unauthorized(string $message = '未授权', ?string $errorCode = 'UNAUTHORIZED'): JsonResponse
    {
        return $this->error($message, 401, $errorCode);
    }

    /**
     * 禁止访问响应（403）
     */
    protected function forbidden(string $message = '禁止访问', ?string $errorCode = 'FORBIDDEN'): JsonResponse
    {
        return $this->error($message, 403, $errorCode);
    }

    /**
     * 验证错误响应（422）
     */
    protected function validationError(mixed $errors, string $message = '验证失败'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * 服务器错误响应（500）
     */
    protected function serverError(string $message = '服务器内部错误', ?string $errorCode = 'SERVER_ERROR'): JsonResponse
    {
        return $this->error($message, 500, $errorCode);
    }

    /**
     * 请求过于频繁响应（429）
     */
    protected function tooManyRequests(string $message = '请求过于频繁', ?string $errorCode = 'RATE_LIMITED'): JsonResponse
    {
        return $this->error($message, 429, $errorCode);
    }

    /**
     * 分页响应
     */
    protected function paginated(LengthAwarePaginator $paginator, string $message = 'success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * 资源响应（单个）
     */
    protected function resource(JsonResource $resource, string $message = 'success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resource,
        ]);
    }

    /**
     * 资源集合响应（列表）
     */
    protected function resourceCollection($collection, string $message = 'success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $collection,
        ]);
    }

    /**
     * 批量操作响应
     */
    protected function batch(array $results, string $message = '批量操作完成'): JsonResponse
    {
        $successCount = count(array_filter($results, fn ($r) => $r['success']));
        $failCount = count($results) - $successCount;

        return response()->json([
            'success' => $failCount === 0,
            'message' => $message,
            'data' => [
                'total' => count($results),
                'success' => $successCount,
                'failed' => $failCount,
                'details' => $results,
            ],
        ]);
    }
}
