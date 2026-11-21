<?php

declare(strict_types=1);

namespace App\Modules\Domains\Base\Logging\Models;

use App\Modules\Domains\Base\Logging\Database\Factories\RequestLogFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * API 请求日志
 *
 * 记录每次 API 请求的完整信息，支持 requestId 追踪
 */
class RequestLog extends Model
{
    use HasFactory;

    protected $table = 'request_logs';

    protected static function newFactory(): RequestLogFactory
    {
        return RequestLogFactory::new();
    }

    protected $fillable = [
        'request_id',
        'method',
        'path',
        'controller',
        'action',
        'user_type',
        'user_id',
        'user_name',
        'ip_address',
        'client_type',
        'user_agent',
        'request_headers',
        'request_body',
        'query_params',
        'response_status',
        'response_body',
        'response_time',
        'memory_usage',
        'exception_class',
        'exception_message',
        'exception_trace',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'query_params' => 'array',
        'response_body' => 'array',
        'response_time' => 'integer',
        'memory_usage' => 'integer',
    ];

    protected $hidden = [
        'request_headers',
        'request_body',
        'response_body',
        'exception_trace',
    ];

    /**
     * 关联业务日志
     */
    public function businessLogs(): HasMany
    {
        return $this->hasMany(BusinessLog::class, 'request_id', 'request_id');
    }

    /**
     * 获取简短状态文本
     */
    public function getStatusLabelAttribute(): string
    {
        return match (true) {
            $this->response_status >= 200 && $this->response_status < 300 => '成功',
            $this->response_status >= 300 && $this->response_status < 400 => '重定向',
            $this->response_status >= 400 && $this->response_status < 500 => '客户端错误',
            $this->response_status >= 500 => '服务器错误',
            default => '未知',
        };
    }

    /**
     * 获取状态颜色
     */
    public function getStatusColorAttribute(): string
    {
        return match (true) {
            $this->response_status >= 200 && $this->response_status < 300 => 'success',
            $this->response_status >= 300 && $this->response_status < 400 => 'warning',
            $this->response_status >= 400 && $this->response_status < 500 => 'danger',
            $this->response_status >= 500 => 'danger',
            default => 'gray',
        };
    }

    /**
     * 判断是否为异常请求
     */
    public function isError(): bool
    {
        return $this->response_status >= 400 || $this->exception_class !== null;
    }

    /**
     * 根据 requestId 查找日志
     */
    public static function findByRequestId(string $requestId): ?self
    {
        return static::where('request_id', $requestId)->first();
    }

    /**
     * 获取指定时间范围的日志
     */
    public static function getRecent(int $minutes = 60): Collection
    {
        return static::where('created_at', '>=', now()->subMinutes($minutes))
            ->orderByDesc('created_at')
            ->get();
    }
}
