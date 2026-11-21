<?php

declare(strict_types=1);

namespace App\Modules\Domains\Logging\Enums;

enum LogLevel: string
{
    case DEBUG = 'debug';
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::DEBUG => '调试',
            self::INFO => '信息',
            self::WARNING => '警告',
            self::ERROR => '错误',
            self::CRITICAL => '严重',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DEBUG => 'gray',
            self::INFO => 'info',
            self::WARNING => 'warning',
            self::ERROR => 'danger',
            self::CRITICAL => 'danger',
        };
    }
}
