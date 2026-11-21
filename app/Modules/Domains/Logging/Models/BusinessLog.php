<?php

declare(strict_types=1);

namespace App\Modules\Domains\Logging\Models;

use App\Modules\Domains\Logging\Enums\LogLevel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 业务逻辑日志
 *
 * 记录业务逻辑中的 info/warning/error 等信息
 */
class BusinessLog extends Model
{
    protected $table = 'business_logs';

    protected $fillable = [
        'request_id',
        'level',
        'channel',
        'message',
        'context',
        'extra',
        'file',
        'line',
        'trace',
    ];

    protected $casts = [
        'level' => LogLevel::class,
        'context' => 'array',
        'extra' => 'array',
    ];

    protected $hidden = [
        'trace',
    ];

    /**
     * 关联请求日志
     */
    public function requestLog(): BelongsTo
    {
        return $this->belongsTo(RequestLog::class, 'request_id', 'request_id');
    }

    /**
     * 写入信息日志
     */
    public static function info(string $message, array $context = [], ?string $requestId = null): self
    {
        return static::createLog(LogLevel::INFO, $message, $context, $requestId);
    }

    /**
     * 写入警告日志
     */
    public static function warning(string $message, array $context = [], ?string $requestId = null): self
    {
        return static::createLog(LogLevel::WARNING, $message, $context, $requestId);
    }

    /**
     * 写入错误日志
     */
    public static function error(string $message, array $context = [], ?string $requestId = null): self
    {
        return static::createLog(LogLevel::ERROR, $message, $context, $requestId);
    }

    /**
     * 写入严重错误日志
     */
    public static function critical(string $message, array $context = [], ?string $requestId = null): self
    {
        return static::createLog(LogLevel::CRITICAL, $message, $context, $requestId);
    }

    /**
     * 创建日志记录
     */
    protected static function createLog(
        LogLevel $level,
        string $message,
        array $context = [],
        ?string $requestId = null
    ): self {
        // 如果没有传入 requestId，尝试从请求中获取
        if ($requestId === null) {
            $requestId = request()->header('X-Request-ID') ?? request()->attributes->get('request_id');
        }

        $file = $context['file'] ?? null;
        $line = $context['line'] ?? null;

        if ($file === null || $line === null) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $caller = collect($trace)->firstWhere('function', static function ($item) {
                return ! in_array($item['function'], ['createLog', 'info', 'warning', 'error', 'critical', 'debug']);
            });
            $file ??= $caller['file'] ?? null;
            $line ??= $caller['line'] ?? null;
        }

        return static::create([
            'request_id' => $requestId,
            'level' => $level,
            'channel' => $context['channel'] ?? 'default',
            'message' => $message,
            'context' => $context,
            'extra' => $context['extra'] ?? null,
            'file' => $caller['file'] ?? null,
            'line' => $caller['line'] ?? null,
            'trace' => $context['trace'] ?? null,
        ]);
    }

    /**
     * 根据 requestId 查找日志
     */
    public static function findByRequestId(string $requestId): Collection
    {
        return static::where('request_id', $requestId)->orderBy('created_at')->get();
    }

    /**
     * 按级别统计
     */
    public static function countByLevel(int $hours = 24): array
    {
        return static::where('created_at', '>=', now()->subHours($hours))
            ->selectRaw('level, COUNT(*) as count')
            ->groupBy('level')
            ->pluck('count', 'level')
            ->toArray();
    }
}
