<?php

declare(strict_types=1);

namespace App\Modules\Domains\Logging\Services;

use App\Modules\Domains\Logging\Enums\LogLevel;
use App\Modules\Domains\Logging\Models\BusinessLog;
use App\Modules\Domains\Logging\Models\RequestLog;

/**
 * 日志服务
 *
 * 提供统一的日志记录接口
 */
class LogService
{
    /**
     * 记录信息日志
     */
    public static function info(string $message, array $context = []): BusinessLog
    {
        return self::log(LogLevel::INFO, $message, $context);
    }

    /**
     * 记录警告日志
     */
    public static function warning(string $message, array $context = []): BusinessLog
    {
        return self::log(LogLevel::WARNING, $message, $context);
    }

    /**
     * 记录错误日志
     */
    public static function error(string $message, array $context = []): BusinessLog
    {
        return self::log(LogLevel::ERROR, $message, $context);
    }

    /**
     * 记录严重错误日志
     */
    public static function critical(string $message, array $context = []): BusinessLog
    {
        return self::log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * 记录调试日志
     */
    public static function debug(string $message, array $context = []): BusinessLog
    {
        return self::log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * 创建日志记录
     */
    protected static function log(LogLevel $level, string $message, array $context = []): BusinessLog
    {
        $requestId = request()->attributes->get('request_id')
            ?? request()->header('X-Request-ID');

        return BusinessLog::create([
            'request_id' => $requestId,
            'level' => $level,
            'channel' => $context['channel'] ?? 'default',
            'message' => $message,
            'context' => $context,
            'extra' => $context['extra'] ?? null,
            'file' => $context['file'] ?? null,
            'line' => $context['line'] ?? null,
            'trace' => $context['trace'] ?? null,
        ]);
    }

    /**
     * 获取指定请求的所有日志
     */
    public static function getLogsByRequestId(string $requestId): array
    {
        $requestLog = RequestLog::findByRequestId($requestId);
        $businessLogs = BusinessLog::findByRequestId($requestId);

        return [
            'request' => $requestLog,
            'business_logs' => $businessLogs,
        ];
    }

    /**
     * 获取日志统计
     */
    public static function getStats(int $hours = 24): array
    {
        $requestStats = RequestLog::where('created_at', '>=', now()->subHours($hours))
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN response_status >= 200 AND response_status < 300 THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN response_status >= 400 AND response_status < 500 THEN 1 ELSE 0 END) as client_error,
                SUM(CASE WHEN response_status >= 500 THEN 1 ELSE 0 END) as server_error,
                AVG(response_time) as avg_response_time
            ')
            ->first();

        $businessLogStats = BusinessLog::countByLevel($hours);

        return [
            'requests' => $requestStats,
            'business_logs' => $businessLogStats,
        ];
    }
}
