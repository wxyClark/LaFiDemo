<?php

declare(strict_types=1);

namespace App\Modules\Infrastructure\Http\Middleware;

use App\Modules\Domains\Logging\Models\RequestLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * 请求日志中间件
 *
 * 记录每次 API 请求的完整信息
 */
class RequestLogging
{
    /**
     * 无需记录的路径
     */
    protected array $exceptPaths = [
        'telescope',
        'horizon',
        'sanctum/health',
    ];

    /**
     * 无需记录的 Content-Type
     */
    protected array $exceptContentTypes = [
        'text/html',
        'text/css',
        'application/javascript',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // 跳过不需要记录的路径
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        // 生成或获取 requestId
        $requestId = $request->header('X-Request-ID') ?? Str::uuid()->toString();
        $request->attributes->set('request_id', $requestId);
        $request->headers->set('X-Request-ID', $requestId);

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $response = $next($request);

        $this->logRequest($request, $response, $requestId, $startTime, $startMemory);

        // 在响应头中返回 requestId
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    /**
     * 记录请求日志
     */
    protected function logRequest(
        Request $request,
        Response $response,
        string $requestId,
        float $startTime,
        int $startMemory
    ): void {
        $responseTime = (int) ((microtime(true) - $startTime) * 1000);
        $memoryUsage = memory_get_usage(true) - $startMemory;

        $route = $request->route();
        $controller = null;
        $action = null;

        if ($route) {
            $actionName = $route->getActionName();
            if ($actionName && $actionName !== 'Closure') {
                [$controller, $action] = explode('@', $actionName);
            }
        }

        // 获取用户信息
        $user = $request->user();
        $userType = null;
        $userId = null;
        $userName = null;

        if ($user) {
            $userType = get_class($user);
            $userId = $user->id ?? null;
            $userName = $user->name ?? $user->email ?? null;
        }

        // 获取客户端类型
        $clientType = $this->detectClientType($request);

        // 处理响应 Body
        $responseBody = null;
        try {
            $content = $response->getContent();
            if ($content) {
                $responseBody = json_decode($content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $responseBody = ['raw' => Str::limit($content, 1000)];
                }
            }
        } catch (\Throwable $e) {
            $responseBody = ['error' => 'Failed to decode response'];
        }

        // 处理异常信息
        $exceptionClass = null;
        $exceptionMessage = null;
        $exceptionTrace = null;

        if ($response->getStatusCode() >= 500) {
            $exception = $request->attributes->get('exception');
            if ($exception) {
                $exceptionClass = get_class($exception);
                $exceptionMessage = $exception->getMessage();
                $exceptionTrace = $exception->getTraceAsString();
            }
        }

        try {
            RequestLog::create([
                'request_id' => $requestId,
                'method' => $request->method(),
                'path' => Str::limit($request->path(), 500),
                'controller' => $controller,
                'action' => $action,
                'user_type' => $userType,
                'user_id' => $userId,
                'user_name' => $userName,
                'ip_address' => $request->ip(),
                'client_type' => $clientType,
                'user_agent' => Str::limit($request->userAgent(), 500),
                'request_headers' => $this->sanitizeHeaders($request->headers->all()),
                'request_body' => $this->sanitizeBody($request->all()),
                'query_params' => $request->query(),
                'response_status' => $response->getStatusCode(),
                'response_body' => $responseBody,
                'response_time' => $responseTime,
                'memory_usage' => $memoryUsage,
                'exception_class' => $exceptionClass,
                'exception_message' => $exceptionMessage,
                'exception_trace' => $exceptionTrace,
            ]);
        } catch (\Throwable $e) {
            // 日志记录失败不应影响请求
            report($e);
        }
    }

    /**
     * 检测客户端类型
     */
    protected function detectClientType(Request $request): string
    {
        $userAgent = strtolower($request->userAgent() ?? '');

        if (str_contains($userAgent, 'postman')) {
            return 'postman';
        }

        if (str_contains($userAgent, 'insomnia')) {
            return 'insomnia';
        }

        if (str_contains($userAgent, 'curl')) {
            return 'curl';
        }

        if (str_contains($userAgent, 'android')) {
            return 'android';
        }

        if (str_contains($userAgent, 'iphone') || str_contains($userAgent, 'ipad')) {
            return 'ios';
        }

        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return 'ajax';
        }

        if ($request->expectsJson()) {
            return 'api';
        }

        return 'web';
    }

    /**
     * 清理 Headers (移除敏感信息)
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-csrf-token'];

        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), $sensitiveHeaders)) {
                $headers[$key] = '[REDACTED]';
            }
        }

        return $headers;
    }

    /**
     * 清理 Body (移除敏感字段)
     */
    protected function sanitizeBody(array $body): array
    {
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'secret', 'api_key'];

        foreach ($body as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $body[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $body[$key] = $this->sanitizeBody($value);
            }
        }

        return $body;
    }

    /**
     * 判断是否跳过记录
     */
    protected function shouldSkip(Request $request): bool
    {
        $path = $request->path();

        foreach ($this->exceptPaths as $exceptPath) {
            if (str_starts_with($path, $exceptPath)) {
                return true;
            }
        }

        $contentType = $request->header('Content-Type', '');
        foreach ($this->exceptContentTypes as $exceptType) {
            if (str_contains($contentType, $exceptType)) {
                return true;
            }
        }

        return false;
    }
}
