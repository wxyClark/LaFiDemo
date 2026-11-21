<?php

declare(strict_types=1);

namespace App\Modules\Domains\Base\ApiTesting\Enums;

enum TestStatus: string
{
    case PASS = 'pass';
    case FAIL = 'fail';
    case ERROR = 'error';
    case SKIP = 'skip';

    public function label(): string
    {
        return match ($this) {
            self::PASS => '通过',
            self::FAIL => '失败',
            self::ERROR => '错误',
            self::SKIP => '跳过',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PASS => 'success',
            self::FAIL => 'danger',
            self::ERROR => 'warning',
            self::SKIP => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PASS => 'heroicon-o-check-circle',
            self::FAIL => 'heroicon-o-x-circle',
            self::ERROR => 'heroicon-o-exclamation-triangle',
            self::SKIP => 'heroicon-o-minus-circle',
        };
    }
}
